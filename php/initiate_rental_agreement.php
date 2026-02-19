<?php
// Initiate Rental Agreement Workflow via "Sign & Pay"
// 1. Check availability for the request dates (Double Check)
// 2. Update status to "Initiated"
// 3. Generate Agreement Text

header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$response = ['success' => false, 'error' => 'Unknown error'];

try {
    $conn = getDBConnection();
    
    // Get Input
    $json = json_decode(file_get_contents('php://input'), true);
    $request_id = isset($json['request_id']) ? $json['request_id'] : null;
    $farmer_id = isset($json['user_id']) ? $json['user_id'] : null; // Validate user
    
    // 1. Validate Input
    if (!$request_id) throw new Exception("Request ID is required.");
    
    // Clean ID if prefixed
    $clean_id = str_replace('REQ-', '', $request_id);
    
    // 2. Fetch Request Details (Join with Equipment & Users)
    $sql = "SELECT r.*, e.equipment_name, e.price_per_day, e.owner_id, 
                   u_farmer.name as farmer_name, u_farmer.email as farmer_email, u_farmer.phone as farmer_phone,
                   u_owner.name as owner_name, u_owner.email as owner_email, u_owner.phone as owner_phone
            FROM rental_requests r
            JOIN equipment e ON r.equipment_id = e.id
            JOIN users u_farmer ON r.farmer_id = u_farmer.id
            JOIN users u_owner ON e.owner_id = u_owner.id
            WHERE r.id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $clean_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) throw new Exception("Rental request not found.");
    
    $request = $result->fetch_assoc();
    $stmt->close();
    
    // 3. Check Availability (Double Check before locking)
    // Check against Bookings
    $checkSql = "SELECT id FROM bookings 
                 WHERE equipment_id = ? 
                 AND status IN ('confirmed', 'active') 
                 AND (start_date <= ? AND end_date >= ?)";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("iss", $request['equipment_id'], $request['end_date'], $request['start_date']);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        throw new Exception("Equipment is no longer available for these dates.");
    }
    $checkStmt->close();
    
    // 4. Check for existing 'Initiated' or higher status requests that might conflict?
    // For now, we assume multiple requests can exist until one is PAID.
    
    // 5. Update Request Status to 'Initiated' (if not already paid)
    if ($request['status'] !== 'paid' && $request['status'] !== 'active') {
        $updateSql = "UPDATE rental_requests SET status = 'initiated' WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("i", $clean_id);
        $updateStmt->execute();
        $updateStmt->close();
    }
    
    // 6. Generate Agreement Text (Dynamic)
    // Fetch template from settings if available, else use default
    $template = "Rental Agreement\n\n" .
                "This agreement is made between [OWNER_NAME] (Owner) and [FARMER_NAME] (Renter).\n\n" .
                "Equipment: [EQUIPMENT_NAME]\n" .
                "Rental Period: [START_DATE] to [END_DATE]\n" .
                "Total Amount: [AMOUNT]\n\n" .
                "Terms & Conditions:\n" .
                "1. The Renter agrees to return the equipment in good condition.\n" .
                "2. Any damages incurred during the rental period are the responsibility of the Renter.\n" .
                "3. Late returns will incur a penalty fee.\n\n" .
                "By picking up the equipment or digitally signing this document, the Renter agrees to these terms.";
                
    // Replace placeholders
    $agreement_text = str_replace(
        ['[OWNER_NAME]', '[FARMER_NAME]', '[EQUIPMENT_NAME]', '[START_DATE]', '[END_DATE]', '[AMOUNT]'],
        [
            $request['owner_name'], 
            $request['farmer_name'], 
            $request['equipment_name'], 
            date('d M Y', strtotime($request['start_date'])), 
            date('d M Y', strtotime($request['end_date'])), 
            '₹' . number_format($request['total_amount'])
        ],
        $template
    );
    
    $response = [
        'success' => true,
        'agreement_html' => nl2br($agreement_text), // Simple format for display
        'request_details' => $request
    ];
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?>
