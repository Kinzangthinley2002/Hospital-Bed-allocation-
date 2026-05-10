<?php
require_once 'db.php';
$response = ['status'=>'success','message'=>'All patients reallocated successfully!', 'unallocated'=>[]];

// Fetch wards and initialize
$wardsRes = $conn->query("SELECT * FROM wards");
$wardList = [];
while($w = $wardsRes->fetch_assoc()){
    // Get current allocations
    $usedRes = $conn->query("SELECT COUNT(*) AS used_count FROM allocations WHERE ward_id={$w['id']}");
    $usedCount = $usedRes->fetch_assoc()['used_count'];

    // Get priorities already present
    $prioritiesRes = $conn->query("SELECT p.priority FROM allocations a JOIN patients p ON a.patient_id=p.id WHERE a.ward_id={$w['id']}");
    $existingPriorities = [];
    while($pr = $prioritiesRes->fetch_assoc()){ $existingPriorities[] = $pr['priority']; }

    $wardList[$w['id']] = [
        'capacity'=>$w['capacity'],
        'used'=>$usedCount,
        'priorities'=>$existingPriorities
    ];
}

// Fetch all patients
$patientsRes = $conn->query("SELECT * FROM patients ORDER BY FIELD(priority,'High','Medium','Low')");

// Clear previous allocations
$conn->query("TRUNCATE TABLE allocations");

// Allocate using graph coloring logic
while($patient = $patientsRes->fetch_assoc()){
    $allocated = false;
    foreach($wardList as $wardId => &$ward){
        if($ward['used'] < $ward['capacity'] && !in_array($patient['priority'],$ward['priorities'])){
            $bedNumber = $ward['used'] + 1;
            $stmt = $conn->prepare("INSERT INTO allocations(patient_id, ward_id, bed_number) VALUES (?,?,?)");
            $stmt->bind_param("iii",$patient['id'],$wardId,$bedNumber);
            $stmt->execute();
            $ward['used']++;
            $ward['priorities'][] = $patient['priority'];
            $allocated = true;
            break;
        }
    }

    if(!$allocated){
        foreach($wardList as $wardId => &$ward){
            if($ward['used'] < $ward['capacity']){
                $bedNumber = $ward['used'] + 1;
                $stmt = $conn->prepare("INSERT INTO allocations(patient_id, ward_id, bed_number) VALUES (?,?,?)");
                $stmt->bind_param("iii",$patient['id'],$wardId,$bedNumber);
                $stmt->execute();
                $ward['used']++;
                $ward['priorities'][] = $patient['priority'];
                $allocated = true;
                break;
            }
        }
    }

    if(!$allocated){
        $response['unallocated'][] = ['id'=>$patient['id'],'name'=>$patient['name'],'priority'=>$patient['priority']];
    }
}

if(count($response['unallocated'])>0){
    $names = implode(", ", array_column($response['unallocated'],'name'));
    $response['status'] = 'warning';
    $response['message'] .= " However, the following patients could not be allocated due to full wards: $names.";
}

echo json_encode($response);
?>
