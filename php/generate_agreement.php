<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);

$request_id = isset($data['request_id']) ? intval($data['request_id']) : 0;

if ($request_id === 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid request ID']);
    exit;
}

try {
    // Get rental request details
    $query = "SELECT rr.*, e.equipment_name, e.price_per_day, e.owner_id,
              fu.username as farmer_name, ou.username as owner_name
              FROM rental_requests rr
              LEFT JOIN equipment e ON rr.equipment_id = e.id
              LEFT JOIN users fu ON rr.farmer_id = fu.id
              LEFT JOIN users ou ON e.owner_id = ou.id
              WHERE rr.id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Request not found']);
        exit;
    }
    
    $request = $result->fetch_assoc();
    
    // Generate agreement number
    $agreement_number = 'AGR-' . date('Y') . '-' . str_pad($request_id, 6, '0', STR_PAD_LEFT);
    
    // Check if agreement already exists
    $check_query = "SELECT id FROM agreements WHERE request_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $request_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Agreement already exists
        $existing = $check_result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'message' => 'Agreement already exists',
            'agreement_id' => $existing['id']
        ]);
        exit;
    }
    
    // Create new agreement
    $insert_query = "INSERT INTO agreements (
        request_id,
        farmer_id,
        owner_id,
        equipment_id,
        agreement_number,
        agreement_status,
        total_amount,
        payment_status,
        insurance_included,
        generated_at
    ) VALUES (?, ?, ?, ?, ?, 'pending_approval', ?, 'pending', 0, NOW())";
    
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param(
        "iiiiss",
        $request_id,
        $request['farmer_id'],
        $request['owner_id'],
        $request['equipment_id'],
        $agreement_number,
        $request['total_amount']
    );
    
    if ($insert_stmt->execute()) {
        $agreement_id = $conn->insert_id;
        
        echo json_encode([
            'success' => true,
            'message' => 'Agreement generated successfully',
            'agreement_id' => $agreement_id,
            'agreement_number' => $agreement_number
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to generate agreement'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>
