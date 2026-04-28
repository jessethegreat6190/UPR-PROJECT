<script>
// Initialize demo inmate data for booking system
(function() {
  const existing = localStorage.getItem('ups_inmate_records');
  if (!existing || existing === '[]') {
    const demoInmates = [
      {id: 1, no: 'INV/2025/4521', name: 'John Okello', gender: 'Male', crime: 'Theft', block: 'A', action: 'admission', location: 'Block A Cell 12', status: 'active', date: '2025-04-15'},
      {id: 2, no: 'INV/2025/4522', name: 'Mary Atwoki', gender: 'Female', crime: 'Fraud', block: 'Female', action: 'admission', location: 'Female Wing', status: 'active', date: '2025-04-20'},
      {id: 3, no: 'INV/2024/3301', name: 'Peter Ssali', gender: 'Male', crime: 'Assault', block: 'B', action: 'admission', location: 'Block B Cell 5', status: 'active', date: '2024-12-10'},
      {id: 4, no: 'INV/2024/8712', name: 'Okello Patrick', gender: 'Male', crime: 'Robbery', block: 'C', action: 'admission', location: 'Block C Cell 8', status: 'active', date: '2024-09-18'},
      {id: 5, no: 'INV/2025/1234', name: 'Kato Ronald', gender: 'Male', crime: 'Burglary', block: 'A', action: 'admission', location: 'Block A Cell 3', status: 'active', date: '2025-01-30'},
      {id: 6, no: 'INV/2024/5521', name: 'Sarah Namubiru', gender: 'Female', crime: 'Theft', block: 'Female', action: 'admission', location: 'Female Wing', status: 'active', date: '2024-11-05'},
    ];
    localStorage.setItem('ups_inmate_records', JSON.stringify(demoInmates));
    console.log('Demo inmates loaded');
  }
})();
</script>