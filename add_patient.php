<?php
require_once 'db.php';
$response = ['status'=>'success','message'=>'Patient added successfully!'];

$name = $_POST['name'];
$age = $_POST['age'];
$gender = $_POST['gender'];
$priority = $_POST['priority'];

$stmt = $conn->prepare("INSERT INTO patients(name,age,gender,priority) VALUES(?,?,?,?)");
$stmt->bind_param("siss",$name,$age,$gender,$priority);
if(!$stmt->execute()){
    $response = ['status'=>'danger','message'=>'Error adding patient: '.$conn->error];
}

echo json_encode($response);
?>
