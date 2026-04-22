<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST,GET,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);
require_once __DIR__ . '/../config/database.php';
$pdo = getDB();
$action = $_GET['action'] ?? 'detect';
switch ($action) {
    case 'detect':    handleDetect($pdo);   break;
    case 'entries':   handleEntries($pdo);  break;
    case 'exit':      handleExit($pdo);     break;
    case 'approve':   handleApprove($pdo);  break;
    case 'simulate':  handleSimulate($pdo); break;
    case 'stats':     handleStats($pdo);    break;
    case 'locations': handleLocations($pdo); break;
    default: echo json_encode(['error'=>'Unknown action']);
}
function handleDetect($pdo) {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw,true);
    if (!$data) $data=['plate'=>strtoupper(trim($_GET['plate']??'')),'confidence'=>floatval($_GET['confidence']??90),'gate'=>$_GET['gate']??'Main Gate','make'=>$_GET['make']??'','colour'=>$_GET['colour']??''];
    $plate = strtoupper(trim($data['plate']??''));
    if (!$plate) { echo json_encode(['error'=>'No plate provided']); return; }
    $bl = $pdo->prepare("SELECT * FROM vehicle_blacklist WHERE plate=? AND active=1 AND (expires_at IS NULL OR expires_at>NOW())");
    $bl->execute([$plate]); $blacklisted = $bl->fetch();
    $veh = $pdo->prepare("SELECT * FROM vehicles WHERE plate=? LIMIT 1");
    $veh->execute([$plate]); $known = $veh->fetch();
    $entry = $pdo->prepare("INSERT INTO vehicle_entries (plate,make,colour,confidence,gate,entry_time,flagged,flag_reason,status) VALUES (?,?,?,?,?,NOW(),?,?,'pending')");
    $entry->execute([$plate,$data['make']??($known['make']??''),$data['colour']??($known['colour']??''),$data['confidence']??0,$data['gate']??'Main Gate',$blacklisted?1:0,$blacklisted?$blacklisted['reason']:null]);
    $entryId = $pdo->lastInsertId();
    echo json_encode(['success'=>true,'entry_id'=>$entryId,'plate'=>$plate,'confidence'=>$data['confidence']??0,'flagged'=>(bool)$blacklisted,'flag_reason'=>$blacklisted?$blacklisted['reason']:null,'known'=>(bool)$known,'owner'=>$known?$known['owner_name']:null,'make'=>$known?$known['make'].' '.$known['model']:($data['make']??'Unknown'),'colour'=>$known?$known['colour']:($data['colour']??'Unknown'),'category'=>$known?$known['category']:'visitor','alert_level'=>$blacklisted?'DANGER':($known?'OK':'UNKNOWN')]);
}
function handleSimulate($pdo) {
    $plates=[['plate'=>'UAB 123D','confidence'=>94.2,'make'=>'Toyota Corolla','colour'=>'Silver'],['plate'=>'UBF 007X','confidence'=>97.8,'make'=>'Honda Civic','colour'=>'Red'],['plate'=>'UAN 456B','confidence'=>98.1,'make'=>'Toyota Hilux','colour'=>'White'],['plate'=>'UCC 111A','confidence'=>91.4,'make'=>'Nissan Navara','colour'=>'Blue'],['plate'=>'UBB 789C','confidence'=>95.6,'make'=>'Nissan X-Trail','colour'=>'Black'],['plate'=>'UAC 550J','confidence'=>88.3,'make'=>'Unknown','colour'=>'Unknown']];
    $idx = $_GET['plate_index']??null;
    $data = ($idx!==null&&isset($plates[$idx]))?$plates[$idx]:$plates[array_rand($plates)];
    $data['gate']='Main Gate (Simulated)';
    $_GET['plate']=$data['plate'];$_GET['confidence']=$data['confidence'];$_GET['gate']=$data['gate'];$_GET['make']=$data['make'];$_GET['colour']=$data['colour'];
    handleDetect($pdo);
}
function handleEntries($pdo) {
    $limit=intval($_GET['limit']??20); $status=$_GET['status']??null;
    $sql="SELECT e.*, TIMESTAMPDIFF(MINUTE,e.entry_time,NOW()) AS minutes_on_site FROM vehicle_entries e ";
    $params=[];
    if($status){$sql.="WHERE e.status=? ";$params[]=$status;}
    $sql.="ORDER BY e.entry_time DESC LIMIT ?"; $params[]=$limit;
    $stmt=$pdo->prepare($sql);$stmt->execute($params);$rows=$stmt->fetchAll();
    foreach($rows as &$r){$r['overstay']=($r['status']==='active'&&$r['minutes_on_site']>4320);$r['long_stay']=($r['status']==='active'&&$r['minutes_on_site']>2880);}
    $counts=$pdo->query("SELECT COUNT(CASE WHEN status='active' THEN 1 END) AS active, COUNT(CASE WHEN status='pending' THEN 1 END) AS pending, COUNT(CASE WHEN flagged=1 THEN 1 END) AS flagged_today, COUNT(CASE WHEN status='exited' AND DATE(entry_time)=CURDATE() THEN 1 END) AS exited_today, COUNT(CASE WHEN DATE(entry_time)=CURDATE() THEN 1 END) AS total_today FROM vehicle_entries")->fetch();
    echo json_encode(['entries'=>$rows,'counts'=>$counts]);
}
function handleExit($pdo) {
    $id=intval($_GET['id']??0); if(!$id){echo json_encode(['error'=>'No ID']);return;}
    $pdo->prepare("UPDATE vehicle_entries SET exit_time=NOW(),status='exited' WHERE id=?")->execute([$id]);
    echo json_encode(['success'=>true,'message'=>'Exit recorded.']);
}
function handleApprove($pdo) {
    $raw=file_get_contents('php://input');$data=json_decode($raw,true)?:[];
    $id=intval($data['entry_id']??$_GET['id']??0); if(!$id){echo json_encode(['error'=>'No ID']);return;}
    $pdo->prepare("UPDATE vehicle_entries SET driver_name=?,driver_nid=?,purpose=?,person_visiting=?,destination_quarter=?,destination_house=?,phone=?,items_declared=?,guard_id=?,guard_notes=?,status='active' WHERE id=?")
        ->execute([$data['driver_name']??null,$data['driver_nid']??null,$data['purpose']??null,$data['person_visiting']??null,$data['destination_quarter']??null,$data['destination_house']??null,$data['phone']??null,$data['items_declared']??null,$data['guard_id']??'Guard',$data['notes']??null,$id]);
    echo json_encode(['success'=>true,'message'=>'Entry approved. Gate can open.']);
}
function handleStats($pdo) {
    $today=$pdo->query("SELECT COUNT(*) AS total_today, SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) AS on_site, SUM(CASE WHEN flagged=1 THEN 1 ELSE 0 END) AS flagged_today, SUM(CASE WHEN status='exited' THEN 1 ELSE 0 END) AS exited FROM vehicle_entries WHERE DATE(entry_time)=CURDATE()")->fetch();
    echo json_encode(['today'=>$today]);
}
function handleLocations($pdo) {
    // Returns all quarters and their houses for the gate destination dropdown
    try {
        $quarters = $pdo->query("SELECT q.id, q.name, q.block, q.category, GROUP_CONCAT(CONCAT(h.id,':',h.house_number,':',h.status) ORDER BY h.house_number SEPARATOR '|') AS houses FROM quarters q JOIN houses h ON h.quarter_id=q.id WHERE q.active=1 GROUP BY q.id ORDER BY q.category,q.name,q.block")->fetchAll();
        echo json_encode($quarters);
    } catch(Exception $e) {
        echo json_encode([]);
    }
}
?>
