<?php
require_once 'db.php';
$response = ['status'=>'success','message'=>'Patients allocated using graph coloring successfully!', 'unallocated'=>[]];

// Fetch wards and initialize ward info
$wardsRes = $conn->query("SELECT * FROM wards");
$wardList = [];
while($w = $wardsRes->fetch_assoc()){
    // Count already allocated patients in this ward
    $usedRes = $conn->query("SELECT COUNT(*) AS used_count FROM allocations WHERE ward_id={$w['id']}");
    $usedCount = $usedRes->fetch_assoc()['used_count'];
    
    // Get existing priorities in this ward
    $prioritiesRes = $conn->query("SELECT p.priority FROM allocations a JOIN patients p ON a.patient_id=p.id WHERE a.ward_id={$w['id']}");
    $existingPriorities = [];
    while($pr = $prioritiesRes->fetch_assoc()){
        $existingPriorities[] = $pr['priority'];
    }

    $wardList[$w['id']] = [
        'capacity' => $w['capacity'],
        'used' => $usedCount,
        'priorities' => $existingPriorities
    ];
}

// Fetch unallocated patients sorted by priority
$patientsRes = $conn->query("SELECT * FROM patients WHERE id NOT IN (SELECT patient_id FROM allocations) ORDER BY FIELD(priority,'High','Medium','Low')");

// Allocate patients using graph coloring logic
while($patient = $patientsRes->fetch_assoc()){
    $allocated = false;

    // Try to place in a ward without same priority
    foreach($wardList as $wardId => &$ward){
        if($ward['used'] < $ward['capacity'] && !in_array($patient['priority'], $ward['priorities'])){
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

    // If not allocated, place in any ward with space
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

    // If still not allocated (all wards full), log patient
    if(!$allocated){
        $response['unallocated'][] = [
            'id' => $patient['id'],
            'name' => $patient['name'],
            'priority' => $patient['priority']
        ];
    }
}

// Modify message if some patients couldn’t be allocated
if(count($response['unallocated']) > 0){
    $response['status'] = 'warning';
    $names = implode(", ", array_column($response['unallocated'],'name'));
    $response['message'] .= " However, the following patients could not be allocated due to full wards: $names.";
}

echo json_encode($response);
?>
