<?php
/**
 * Get Owner Earnings
 * Calculates earnings from equipment rentals and sales for a specific owner
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

try {
    $owner_id = isset($_GET['owner_id']) ? intval($_GET['owner_id']) : 0;
    
    if ($owner_id === 0) {
        throw new Exception('Owner ID is required');
    }
    
    $conn = getDBConnection();
    
    // Calculate total earnings from approved/completed bookings
    $earnings_sql = "SELECT 
                        COALESCE(SUM(CASE WHEN b.status IN ('approved', 'completed') THEN b.total_amount ELSE 0 END), 0) as total_earnings,
                        COALESCE(SUM(CASE WHEN b.status = 'approved' THEN b.total_amount ELSE 0 END), 0) as pending_payouts,
                        COALESCE(SUM(CASE WHEN b.status = 'completed' THEN b.total_amount ELSE 0 END), 0) as wallet_balance,
                        COUNT(CASE WHEN b.status IN ('approved', 'completed') THEN 1 END) as total_bookings,
                        COUNT(CASE WHEN b.status = 'approved' THEN 1 END) as active_rentals,
                        COUNT(CASE WHEN b.status = 'completed' THEN 1 END) as completed_rentals
                    FROM bookings b
                    INNER JOIN equipment e ON b.equipment_id = e.id
                    WHERE e.owner_id = ?";
    
    $stmt = $conn->prepare($earnings_sql);
    $stmt->bind_param("i", $owner_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $earnings_data = $result->fetch_assoc();
    
    // Get recent earnings transactions
    $transactions_sql = "SELECT 
                            b.id,
                            b.equipment_id,
                            b.farmer_name,
                            b.start_date,
                            b.end_date,
                            b.total_amount,
                            b.status,
                            b.created_at,
                            e.equipment_name,
                            DATEDIFF(b.end_date, b.start_date) as duration
                        FROM bookings b
                        INNER JOIN equipment e ON b.equipment_id = e.id
                        WHERE e.owner_id = ? 
                        AND b.status IN ('approved', 'completed')
                        ORDER BY b.created_at DESC
                        LIMIT 10";
    
    $stmt2 = $conn->prepare($transactions_sql);
    $stmt2->bind_param("i", $owner_id);
    $stmt2->execute();
    $transactions_result = $stmt2->get_result();
    
    $transactions = [];
    while ($row = $transactions_result->fetch_assoc()) {
        $transactions[] = $row;
    }
    
    // Calculate monthly earnings (last 30 days)
    $monthly_sql = "SELECT 
                        COALESCE(SUM(b.total_amount), 0) as monthly_earnings,
                        COUNT(*) as monthly_bookings
                    FROM bookings b
                    INNER JOIN equipment e ON b.equipment_id = e.id
                    WHERE e.owner_id = ? 
                    AND b.status IN ('approved', 'completed')
                    AND b.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    
    $stmt3 = $conn->prepare($monthly_sql);
    $stmt3->bind_param("i", $owner_id);
    $stmt3->execute();
    $monthly_result = $stmt3->get_result();
    // Removed duplicate execution
    $monthly_data = $monthly_result->fetch_assoc();
    
    // Get Active Listings Count (Total Equipment)
    $listings_sql = "SELECT COUNT(*) as count FROM equipment WHERE owner_id = ?";
    $stmt4 = $conn->prepare($listings_sql);
    $stmt4->bind_param("i", $owner_id);
    $stmt4->execute();
    $listings_result = $stmt4->get_result();
    $listings_data = $listings_result->fetch_assoc();
    $active_listings = $listings_data['count'];
    $stmt4->close();

    echo json_encode([
        'success' => true,
        'earnings' => [
            'active_listings' => intval($active_listings),
            'average_rating' => 0.0,
            'total_earnings' => floatval($earnings_data['total_earnings']),
            'wallet_balance' => floatval($earnings_data['wallet_balance']),
            'pending_payouts' => floatval($earnings_data['pending_payouts']),
            'total_bookings' => intval($earnings_data['total_bookings']),
            'active_rentals' => intval($earnings_data['active_rentals']),
            'completed_rentals' => intval($earnings_data['completed_rentals']),
            'monthly_earnings' => floatval($monthly_data['monthly_earnings']),
            'monthly_bookings' => intval($monthly_data['monthly_bookings'])
        ],
        'transactions' => $transactions
    ]);
    
    $stmt->close();
    $stmt2->close();
    $stmt3->close();
    $conn->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
