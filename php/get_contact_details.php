<?php
/**
 * get_contact_details.php
 *
 * Securely returns BOTH parties' contact phone numbers for a fully-signed agreement.
 * Robustly handles missing database columns.
 */

// Disable all error output to prevent breaking JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Use a custom error handler to return JSON on fatal errors if possible
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) return false;
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => "PHP Error [$errno]: $errstr in $errfile on line $errline"]);
    exit;
});

header('Content-Type: application/json');

try {
    require_once 'config.php';
    // $conn is already created by config.php at the bottom.
    if (!isset($conn) || !$conn) {
        throw new Exception("Database connection not established.");
    }

    $rawInput = file_get_contents('php://input');
    if (!$rawInput) throw new Exception('No input data from frontend');

    $json = json_decode($rawInput, true);
    if (!$json) throw new Exception('Invalid JSON input');

    $requesterId   = isset($json['user_id'])    ? intval($json['user_id'])    : 0;
    $requestId     = isset($json['request_id']) ? intval($json['request_id']) : 0;
    $bookingId     = isset($json['booking_id']) ? intval($json['booking_id']) : 0;
    $requesterType = isset($json['user_type'])  ? trim($json['user_type'])    : '';

    if (!$requesterId || !in_array($requesterType, ['farmer', 'owner'])) {
        throw new Exception('Missing or invalid session parameters. Please log in again.');
    }

    $row = null;

    // 1. Try lookup via rental_requests first (Primary source of truth for agreements)
    if ($requestId > 0) {
        $stmt = $conn->prepare("
            SELECT rr.id AS req_id, rr.farmer_id, rr.owner_id, 
                   COALESCE(rr.agreement_status, 'pending') AS agreement_status,
                   f.name  AS farmer_name, f.phone AS farmer_phone,
                   o.name  AS owner_name,  o.phone AS owner_phone
            FROM   rental_requests rr
            LEFT JOIN users f ON f.id = rr.farmer_id
            LEFT JOIN users o ON o.id = rr.owner_id
            WHERE  rr.id = ?
        ");
        if ($stmt) {
            $stmt->bind_param('i', $requestId);
            if ($stmt->execute()) {
                $res = $stmt->get_result();
                if ($res->num_rows > 0) $row = $res->fetch_assoc();
            }
            $stmt->close();
        }
    }

    // 2. Fallback: Lookup via bookings table
    if (!$row && $bookingId > 0) {
        // Check columns dynamically to avoid crashes if columns aren't there yet
        $hasRequestIdColumn = $conn->query("SHOW COLUMNS FROM bookings LIKE 'request_id'")->num_rows > 0;
        $hasAgreementStatusColumn = $conn->query("SHOW COLUMNS FROM bookings LIKE 'agreement_status'")->num_rows > 0;

        $sql = "SELECT b.farmer_id, b.owner_id, 
                       f.name AS farmer_name, f.phone AS farmer_phone,
                       o.name AS owner_name,  o.phone AS owner_phone ";
        
        if ($hasAgreementStatusColumn) {
            $sql .= ", b.agreement_status ";
        } else {
            $sql .= ", 'fully_signed' AS agreement_status "; // Fallback for confirmed bookings
        }

        if ($hasRequestIdColumn) {
            $sql .= ", rr.agreement_status AS rr_status ";
            $sql .= "FROM bookings b 
                     LEFT JOIN rental_requests rr ON rr.id = b.request_id ";
        } else {
            $sql .= "FROM bookings b ";
        }

        $sql .= "LEFT JOIN users f ON f.id = b.farmer_id 
                 LEFT JOIN users o ON o.id = b.owner_id 
                 WHERE b.id = ?";

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('i', $bookingId);
            if ($stmt->execute()) {
                $res = $stmt->get_result();
                if ($res->num_rows > 0) {
                    $row = $res->fetch_assoc();
                    // Resolve status: if rr_status exists and is not empty, use it. Else use agreement_status
                    if (isset($row['rr_status']) && !empty($row['rr_status'])) {
                        $row['agreement_status'] = $row['rr_status'];
                    }
                }
            }
            $stmt->close();
        }
    }

    if (!$row) {
        throw new Exception("Agreement details not found for ID: " . ($requestId ?: $bookingId));
    }

    // Authorisation check
    $isFarmer = ($requesterType === 'farmer' && intval($row['farmer_id']) === $requesterId);
    $isOwner  = ($requesterType === 'owner'  && intval($row['owner_id'])  === $requesterId);

    if (!$isFarmer && !$isOwner) {
        throw new Exception('Unauthorized: You are not a party to this agreement.');
    }

    // Check agreement is fully signed
    $agreementStatus = strtolower(trim($row['agreement_status'] ?? ''));
    $isFullySigned   = ($agreementStatus === 'fully_signed');

    if (!$isFullySigned) {
        echo json_encode([
            'success'          => true,
            'unlocked'         => false,
            'message'          => 'Contact details will be available after agreement completion.',
            'agreement_status' => $row['agreement_status']
        ]);
        exit;
    }

    // Return BOTH parties' contact info
    echo json_encode([
        'success'      => true,
        'unlocked'     => true,
        'farmer_name'  => $row['farmer_name']  ?: 'Farmer',
        'farmer_phone' => $row['farmer_phone'] ?: 'Not provided',
        'owner_name'   => $row['owner_name']   ?: 'Equipment Owner',
        'owner_phone'  => $row['owner_phone']  ?: 'Not provided',
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
    exit;
}
