<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST,GET,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);
require_once __DIR__ . '/../config/database.php';
$pdo = getDB();
$action = $_GET['action'] ?? 'quarters';
switch ($action) {
    case 'quarters':      getQuarters($pdo);     break;
    case 'houses':        getHouses($pdo);        break;
    case 'occupants':     getOccupants($pdo);     break;
    case 'search':        searchStaff($pdo);      break;
    case 'stats':         getStats($pdo);         break;
    case 'add_quarter':   addQuarter($pdo);       break;
    case 'add_house':     addHouse($pdo);         break;
    case 'assign':        assignOccupant($pdo);   break;
    case 'transfer':      transferOccupant($pdo); break;
    case 'vacate':        vacateHouse($pdo);      break;
    case 'transfers_log': getTransfers($pdo);     break;
    case 'vacancies':     getVacancies($pdo);     break;
    case 'house_detail':  getHouseDetail($pdo);   break;
    default: echo json_encode(['error' => 'Unknown action']);
}
function getQuarters($pdo) {
    $rows = $pdo->query("SELECT q.*, COUNT(DISTINCT h.id) AS house_count, SUM(h.status='occupied') AS occupied, SUM(h.status='vacant') AS vacant, SUM(h.status='maintenance') AS maintenance, SUM(h.status='reserved') AS reserved FROM quarters q LEFT JOIN houses h ON h.quarter_id=q.id WHERE q.active=1 GROUP BY q.id ORDER BY q.category,q.name,q.block")->fetchAll();
    echo json_encode($rows);
}
function getHouses($pdo) {
    $qid = intval($_GET['quarter_id'] ?? 0);
    $status = $_GET['status'] ?? null;
    $sql = "SELECT h.*, o.staff_name, o.service_no, o.rank, o.phone, o.move_in_date, o.id AS occupant_id FROM houses h LEFT JOIN occupants o ON o.house_id=h.id AND o.status='active' WHERE h.quarter_id=?";
    $params = [$qid];
    if ($status) { $sql .= " AND h.status=?"; $params[] = $status; }
    $sql .= " ORDER BY h.house_number";
    $s = $pdo->prepare($sql); $s->execute($params); echo json_encode($s->fetchAll());
}
function getOccupants($pdo) {
    $hid = intval($_GET['house_id'] ?? 0);
    if ($hid) { $s = $pdo->prepare("SELECT o.*, h.house_number, q.name AS quarter_name, q.block FROM occupants o JOIN houses h ON h.id=o.house_id JOIN quarters q ON q.id=h.quarter_id WHERE o.house_id=? ORDER BY o.move_in_date DESC"); $s->execute([$hid]); }
    else { $s = $pdo->query("SELECT o.*, h.house_number, q.name AS quarter_name, q.block FROM occupants o JOIN houses h ON h.id=o.house_id JOIN quarters q ON q.id=h.quarter_id WHERE o.status='active' ORDER BY q.name,q.block,h.house_number"); }
    echo json_encode($s->fetchAll());
}
function searchStaff($pdo) {
    $q = '%'.trim($_GET['q'] ?? '').'%';
    $s = $pdo->prepare("SELECT o.*, h.house_number, q.name AS quarter_name, q.block FROM occupants o JOIN houses h ON h.id=o.house_id JOIN quarters q ON q.id=h.quarter_id WHERE (o.staff_name LIKE ? OR o.service_no LIKE ? OR o.rank LIKE ?) AND o.status='active' ORDER BY o.staff_name LIMIT 20");
    $s->execute([$q,$q,$q]); echo json_encode($s->fetchAll());
}
function getStats($pdo) {
    $t = $pdo->query("SELECT COUNT(*) AS total_houses, SUM(status='occupied') AS occupied, SUM(status='vacant') AS vacant, SUM(status='maintenance') AS maintenance, SUM(status='reserved') AS reserved FROM houses")->fetch();
    $tr = $pdo->query("SELECT t.*, o.staff_name, o.rank, fh.house_number AS from_house, fq.name AS from_quarter, fq.block AS from_block, th.house_number AS to_house, tq.name AS to_quarter, tq.block AS to_block FROM housing_transfers t JOIN occupants o ON o.id=t.occupant_id LEFT JOIN houses fh ON fh.id=t.from_house_id LEFT JOIN quarters fq ON fq.id=fh.quarter_id JOIN houses th ON th.id=t.to_house_id JOIN quarters tq ON tq.id=th.quarter_id ORDER BY t.created_at DESC LIMIT 5")->fetchAll();
    echo json_encode(['totals'=>$t,'recent_transfers'=>$tr]);
}
function addQuarter($pdo) {
    $d = json_decode(file_get_contents('php://input'),true)?:[];
    if (empty($d['name'])) { echo json_encode(['error'=>'Name required']); return; }
    $pdo->prepare("INSERT INTO quarters (name,block,category,description,total_houses) VALUES (?,?,?,?,?)")->execute([$d['name'],$d['block']??null,$d['category']??'warders',$d['description']??null,intval($d['total_houses']??0)]);
    echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId()]);
}
function addHouse($pdo) {
    $d = json_decode(file_get_contents('php://input'),true)?:[];
    if (empty($d['quarter_id'])||empty($d['house_number'])) { echo json_encode(['error'=>'Required fields missing']); return; }
    try { $pdo->prepare("INSERT INTO houses (quarter_id,house_number,house_type,bedrooms) VALUES (?,?,?,?)")->execute([$d['quarter_id'],strtoupper($d['house_number']),$d['house_type']??'single',intval($d['bedrooms']??1)]); echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId()]); }
    catch (PDOException $e) { echo json_encode(['error'=>'House number already exists in this quarter']); }
}
function assignOccupant($pdo) {
    $d = json_decode(file_get_contents('php://input'),true)?:[];
    if (empty($d['house_id'])||empty($d['staff_name'])) { echo json_encode(['error'=>'Required fields missing']); return; }
    $h = $pdo->query("SELECT status FROM houses WHERE id=".intval($d['house_id']))->fetch();
    if ($h && !in_array($h['status'],['vacant','reserved'])) { echo json_encode(['error'=>'House is not vacant']); return; }
    $pdo->beginTransaction();
    try {
        $pdo->prepare("INSERT INTO occupants (house_id,staff_name,service_no,rank,department,phone,national_id,move_in_date,allocated_by,notes) VALUES (?,?,?,?,?,?,?,?,?,?)")->execute([$d['house_id'],$d['staff_name'],$d['service_no']??null,$d['rank']??null,$d['department']??null,$d['phone']??null,$d['national_id']??null,$d['move_in_date']??date('Y-m-d'),$d['allocated_by']??'Admin',$d['notes']??null]);
        $pdo->prepare("UPDATE houses SET status='occupied' WHERE id=?")->execute([$d['house_id']]);
        $pdo->commit(); echo json_encode(['success'=>true,'message'=>$d['staff_name'].' assigned successfully.']);
    } catch (Exception $e) { $pdo->rollBack(); echo json_encode(['error'=>$e->getMessage()]); }
}
function transferOccupant($pdo) {
    $d = json_decode(file_get_contents('php://input'),true)?:[];
    if (empty($d['occupant_id'])||empty($d['to_house_id'])) { echo json_encode(['error'=>'Required fields missing']); return; }
    $to = $pdo->query("SELECT status FROM houses WHERE id=".intval($d['to_house_id']))->fetch();
    if (!$to||$to['status']!=='vacant') { echo json_encode(['error'=>'Destination house is not vacant']); return; }
    $occ = $pdo->query("SELECT * FROM occupants WHERE id=".intval($d['occupant_id']))->fetch();
    if (!$occ) { echo json_encode(['error'=>'Occupant not found']); return; }
    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE houses SET status='vacant' WHERE id=?")->execute([$occ['house_id']]);
        $pdo->prepare("UPDATE occupants SET status='transferred',move_out_date=? WHERE id=?")->execute([date('Y-m-d'),$occ['id']]);
        $pdo->prepare("INSERT INTO occupants (house_id,staff_name,service_no,rank,department,phone,national_id,move_in_date,allocated_by,notes) VALUES (?,?,?,?,?,?,?,?,?,?)")->execute([$d['to_house_id'],$occ['staff_name'],$occ['service_no'],$occ['rank'],$occ['department'],$occ['phone'],$occ['national_id'],date('Y-m-d'),$d['authorised_by']??'Admin',$d['notes']??null]);
        $newId = $pdo->lastInsertId();
        $pdo->prepare("UPDATE houses SET status='occupied' WHERE id=?")->execute([$d['to_house_id']]);
        $pdo->prepare("INSERT INTO housing_transfers (occupant_id,from_house_id,to_house_id,transfer_date,reason,authorised_by,notes) VALUES (?,?,?,?,?,?,?)")->execute([$newId,$occ['house_id'],$d['to_house_id'],date('Y-m-d'),$d['reason']??'Transfer',$d['authorised_by']??'Admin',$d['notes']??null]);
        $pdo->commit(); echo json_encode(['success'=>true,'message'=>$occ['staff_name'].' transferred successfully.']);
    } catch (Exception $e) { $pdo->rollBack(); echo json_encode(['error'=>$e->getMessage()]); }
}
function vacateHouse($pdo) {
    $d = json_decode(file_get_contents('php://input'),true)?:[];
    $oid = intval($d['occupant_id']??0);
    if (!$oid) { echo json_encode(['error'=>'occupant_id required']); return; }
    $occ = $pdo->query("SELECT * FROM occupants WHERE id=$oid")->fetch();
    if (!$occ) { echo json_encode(['error'=>'Occupant not found']); return; }
    $pdo->prepare("UPDATE occupants SET status='departed',move_out_date=? WHERE id=?")->execute([date('Y-m-d'),$oid]);
    $pdo->prepare("UPDATE houses SET status='vacant' WHERE id=?")->execute([$occ['house_id']]);
    echo json_encode(['success'=>true,'message'=>$occ['staff_name'].' — house vacated.']);
}
function getTransfers($pdo) {
    $rows = $pdo->query("SELECT t.*, o.staff_name, o.rank, o.service_no, fh.house_number AS from_house, fq.name AS from_quarter, fq.block AS from_block, th.house_number AS to_house, tq.name AS to_quarter, tq.block AS to_block FROM housing_transfers t JOIN occupants o ON o.id=t.occupant_id LEFT JOIN houses fh ON fh.id=t.from_house_id LEFT JOIN quarters fq ON fq.id=fh.quarter_id JOIN houses th ON th.id=t.to_house_id JOIN quarters tq ON tq.id=th.quarter_id ORDER BY t.created_at DESC LIMIT 50")->fetchAll();
    echo json_encode($rows);
}
function getVacancies($pdo) {
    $rows = $pdo->query("SELECT h.*, q.name AS quarter_name, q.block, q.category FROM houses h JOIN quarters q ON q.id=h.quarter_id WHERE h.status='vacant' ORDER BY q.category,q.name,q.block,h.house_number")->fetchAll();
    echo json_encode($rows);
}
function getHouseDetail($pdo) {
    $hid = intval($_GET['house_id']??0);
    $house = $pdo->query("SELECT h.*, q.name AS quarter_name, q.block, q.category FROM houses h JOIN quarters q ON q.id=h.quarter_id WHERE h.id=$hid")->fetch();
    $s = $pdo->prepare("SELECT * FROM occupants WHERE house_id=? ORDER BY move_in_date DESC"); $s->execute([$hid]);
    echo json_encode(['house'=>$house,'history'=>$s->fetchAll()]);
}
?>
