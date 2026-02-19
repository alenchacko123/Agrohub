<?php
/**
 * Check Equipment Availability and Calculate Cost
 */

// 1. Suppress all output to ensure JSON validity
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');

// Variables for response
$response = ['success' => false, 'available' => false, 'error' => 'Unknown error'];

try {
    require_once 'config.php';
    
    // 2. Get Input
    $json = json_decode(file_get_contents('php://input'), true);
    
    $equipment_id = isset($_GET['equipment_id']) ? intval($_GET['equipment_id']) : ($json['equipment_id'] ?? 0);
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : ($json['start_date'] ?? '');
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : ($json['end_date'] ?? '');
    
    // 3. Validate Input
    if (empty($equipment_id)) throw new Exception('Equipment ID is required');
    if (empty($start_date)) throw new Exception('Start Date is required');
    if (empty($end_date)) throw new Exception('End Date is required');
    
    // 4. DB Connection
    $conn = getDBConnection();
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    // 5. Check Equipment Existence & Base Price
    $equipStmt = $conn->prepare("SELECT price_per_day, availability_status FROM equipment WHERE id = ?");
    if (!$equipStmt) throw new Exception("Prepare failed: " . $conn->error);
    
    $equipStmt->bind_param("i", $equipment_id);
    $equipStmt->execute();
    $equipResult = $equipStmt->get_result();
    
    if ($equipResult->num_rows === 0) {
        throw new Exception('Equipment not found');
    }
    
    $equipment = $equipResult->fetch_assoc();
    $equipStmt->close();
    
    if ($equipment['availability_status'] === 'maintenance' || $equipment['availability_status'] === 'rented') {
        ob_clean();
        echo json_encode([
            'success' => true, // Request succeeded, but result is unavailable
            'available' => false, 
            'reason' => 'Equipment is currently ' . $equipment['availability_status']
        ]);
        exit;
    }
    
    // 6. Check Overlaps in rental_requests
    // Logic: RequestStart <= QueryEnd AND RequestEnd >= QueryStart
    $sql = "
        SELECT id FROM rental_requests 
        WHERE equipment_id = ? 
        AND status IN ('pending_payment', 'approved', 'active', 'signed', 'paid') 
        AND (start_date <= ? AND end_date >= ?)
    ";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception("Prepare overlapping check failed: " . $conn->error);

    $stmt->bind_param("iss", $equipment_id, $end_date, $start_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    
    if ($result->num_rows > 0) {
        ob_clean();
        echo json_encode([
            'available' => false,
            'reason' => 'Selected dates overlap with an existing booking'
        ]);
        exit;
    }
    
    // 7. Check Overlaps in bookings table (Active Bookings)
    // Check if table exists first (or suppress error if query fails, but we assume it exists from previous steps)
    $bookingSql = "
        SELECT id FROM bookings 
        WHERE equipment_id = ? 
        AND status IN ('active', 'confirmed', 'paid')
        AND (start_date <= ? AND end_date >= ?)
    ";
    
    $stmt2 = $conn->prepare($bookingSql);
    if ($stmt2) {
        $stmt2->bind_param("iss", $equipment_id, $end_date, $start_date);
        $stmt2->execute();
        $bookingResult = $stmt2->get_result();
        $stmt2->close();
        
        if ($bookingResult->num_rows > 0) {
            ob_clean();
            echo json_encode([
                'available' => false,
                'reason' => 'Selected dates overlap with a confirmed booking'
            ]);
            exit;
        }
    }

    // 8. Calculate Cost
    try {
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        
        // Ensure accurate day count (including start and end day)
        $interval = $start->diff($end);
        $days = (int)$interval->format('%a') + 1;
        
        if ($end < $start) {
             throw new Exception('End date cannot be before start date');
        }
        
    } catch (Throwable $e) {
        throw new Exception('Invalid date format');
    }
    
    $pricePerDay = floatval($equipment['price_per_day']);
    $total_amount = $days * $pricePerDay;
    
    $response = [
        'success' => true,
        'available' => true,
        'days' => $days,
        'price_per_day' => $pricePerDay,
        'total_amount' => $total_amount
    ];
    
} catch (Throwable $e) {
    $response = [
        'success' => false,
        'available' => false,
        'error' => $e->getMessage()
    ];
}

// 9. Final Output
ob_clean(); // Discard any prior output
echo json_encode($response);
?>
