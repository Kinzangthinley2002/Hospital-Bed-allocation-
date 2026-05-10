<?php
require_once 'db.php';
$response = ['status'=>'success','message'=>'Patient allocated successfully!'];

$patient_id = $_POST['patient_id'];
$ward_id = $_POST['ward_id'];
$bed_number = $_POST['bed_number'];

// Check if bed is free
$check = $conn->query("SELECT * FROM allocations WHERE ward_id=$ward_id AND bed_number=$bed_number");
if($check->num_rows>0){
    $response = ['status'=>'danger','message'=>'Bed already occupied!'];
    echo json_encode($response);
    exit;
}

// Update allocation
$stmt = $conn->prepare("UPDATE allocations SET ward_id=?, bed_number=? WHERE patient_id=?");
$stmt->bind_param("iii",$ward_id,$bed_number,$patient_id);
if(!$stmt->execute()){
    $response = ['status'=>'danger','message'=>'Error allocating patient: '.$conn->error];
}

echo json_encode($response);
?>
