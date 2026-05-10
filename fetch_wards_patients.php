<?php
require_once 'db.php';

$wardsRes = $conn->query("SELECT * FROM wards");
$wards = [];

while($w = $wardsRes->fetch_assoc()){
    $wardId = $w['id'];
    // Fetch allocations in this ward
    $allocRes = $conn->query("SELECT a.bed_number, p.id, p.name, p.age, p.gender, p.priority 
                              FROM allocations a 
                              JOIN patients p ON a.patient_id = p.id 
                              WHERE a.ward_id = $wardId");
    $allocations = [];
    while($alloc = $allocRes->fetch_assoc()){
        $allocations[$alloc['bed_number']] = $alloc;
    }
    $used = count($allocations);
    $wards[] = [
        'id' => $wardId,
        'name' => $w['name'],
        'capacity' => $w['capacity'],
        'used' => $used,
        'allocations' => $allocations
    ];
}

// Return JSON
header('Content-Type: application/json');
echo json_encode(['wards'=>$wards]);
?>
