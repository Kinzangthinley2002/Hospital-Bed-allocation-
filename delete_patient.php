<?php
require_once 'db.php';
$response = ['status'=>'success','message'=>'Patient deleted successfully!'];

if(isset($_POST['patient_id'])){
    $patient_id = intval($_POST['patient_id']);

    // Delete allocation if exists
    $conn->query("DELETE FROM allocations WHERE patient_id = $patient_id");

    // Delete patient
    $stmt = $conn->prepare("DELETE FROM patients WHERE id = ?");
    $stmt->bind_param("i", $patient_id);
    if(!$stmt->execute()){
        $response = ['status'=>'danger','message'=>'Error deleting patient: '.$conn->error];
    }
} else {
    $response = ['status'=>'danger','message'=>'No patient ID provided!'];
}

echo json_encode($response);
?>
