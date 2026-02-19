<?php
header('Content-Type: application/json');
require_once 'config.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id']) || !isset($input['type'])) {
    echo json_encode(['success' => false, 'message' => 'Missing ID or Type']);
    exit;
}

$id = intval($input['id']);
$type = $input['type'];
$conn = getDBConnection();
$response = ['success' => false];

try {
    if ($type === 'rental_request') {
        $stmt = $conn->prepare("UPDATE rental_requests SET is_dismissed = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $response['success'] = true;
    } elseif ($type === 'notification' || $type === 'agreement_notification') {
        $stmt = $conn->prepare("UPDATE notifications SET is_deleted = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $response['success'] = true;
    } else {
        $response['message'] = 'Invalid type';
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
