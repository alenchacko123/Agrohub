<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);

$agreement_id = isset($data['agreement_id']) ? intval($data['agreement_id']) : 0;
$signer_type = isset($data['signer_type']) ? $data['signer_type'] : ''; // 'farmer' or 'owner'
$user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;

if ($agreement_id === 0 || empty($signer_type) || $user_id === 0) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

try {
    // Get agreement details
    $query = "SELECT * FROM agreements WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $agreement_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Agreement not found']);
        exit;
    }
    
    $agreement = $result->fetch_assoc();
    
    // Update signing status
    if ($signer_type === 'farmer' && $agreement['farmer_id'] == $user_id) {
        $update_query = "UPDATE agreements SET farmer_signed = 1, farmer_signed_at = NOW() WHERE id = ?";
    } else if ($signer_type === 'owner' && $agreement['owner_id'] == $user_id) {
        $update_query = "UPDATE agreements SET owner_signed = 1, owner_signed_at = NOW() WHERE id = ?";
    } else {
        echo json_encode(['success' => false, 'error' => 'Unauthorized to sign this agreement']);
        exit;
    }
    
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("i", $agreement_id);
    
    if ($update_stmt->execute()) {
        // Check if both parties have signed
        $check_query = "SELECT farmer_signed, owner_signed FROM agreements WHERE id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("i", $agreement_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $signed_status = $check_result->fetch_assoc();
        
        // If both signed, update status to 'signed'
        if ($signed_status['farmer_signed'] && $signed_status['owner_signed']) {
            $status_query = "UPDATE agreements SET agreement_status = 'signed', signed_at = NOW() WHERE id = ?";
            $status_stmt = $conn->prepare($status_query);
            $status_stmt->bind_param("i", $agreement_id);
            $status_stmt->execute();
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Agreement signed successfully',
            'both_signed' => ($signed_status['farmer_signed'] && $signed_status['owner_signed'])
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to sign agreement']);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>
