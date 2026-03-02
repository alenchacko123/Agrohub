<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once 'config.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $conn = getDBConnection();

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (empty($data)) {
        $data = $_POST;
    }

    if (empty($data['id']) || empty($data['owner_id'])) {
        throw new Exception('Missing id or owner_id');
    }

    $id = intval($data['id']);
    $owner_id = intval($data['owner_id']);

    $stmt = $conn->prepare("DELETE FROM secondhand_equipment WHERE id = ? AND owner_id = ?");
    $stmt->bind_param("ii", $id, $owner_id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Listing removed successfully.']);
    } else {
        throw new Exception('Could not delete listing or no permission.');
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
