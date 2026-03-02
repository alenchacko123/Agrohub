<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    $conn = getDBConnection();
    $sql = "SELECT * FROM insurance_plans ORDER BY price ASC";
    $result = $conn->query($sql);
    
    $plans = [];
    while ($row = $result->fetch_assoc()) {
        $row['features'] = json_decode($row['features']);
        $plans[] = $row;
    }
    
    echo json_encode(['success' => true, 'plans' => $plans]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
