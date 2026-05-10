<?php
require_once 'db.php';
$response = ['status'=>'success','message'=>'Ward added successfully!'];

$name = $_POST['name'];
$capacity = $_POST['capacity'];

$stmt = $conn->prepare("INSERT INTO wards(name,capacity) VALUES(?,?)");
$stmt->bind_param("si",$name,$capacity);
if(!$stmt->execute()){
    $response = ['status'=>'danger','message'=>'Error adding ward: '.$conn->error];
}

echo json_encode($response);
?>
