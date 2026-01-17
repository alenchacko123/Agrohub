<?php
/**
 * Job Portal API
 * Handles job portal applications for general employees
 */

require_once 'config.php';
require_once 'logger.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$conn = getDBConnection();

// GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    if ($action === 'get_applications') {
        $email = sanitize($_GET['email'] ?? '');
        
        if (empty($email)) {
            jsonResponse(false, 'Email is required');
        }

        try {
            $stmt = $conn->prepare("
                SELECT * FROM job_portal_applications 
                WHERE email = ? 
                ORDER BY applied_at DESC
            ");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $applications = [];
            while ($row = $result->fetch_assoc()) {
                $applications[] = $row;
            }
            
            jsonResponse(true, 'Applications retrieved successfully', ['applications' => $applications]);
        } catch (Exception $e) {
            logMessage("Error fetching applications: " . $e->getMessage());
            jsonResponse(false, 'Failed to fetch applications');
        }
    } else {
        jsonResponse(false, 'Invalid action');
    }
}

// POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    if ($action === 'submit_application') {
        // Validate required fields
        $requiredFields = ['fullName', 'email', 'phone', 'positionApplied'];
        foreach ($requiredFields as $field) {
            if (empty($input[$field])) {
                jsonResponse(false, "Missing required field: $field");
            }
        }

        // Sanitize inputs
        $fullName = sanitize($input['fullName']);
        $email = filter_var(sanitize($input['email']), FILTER_VALIDATE_EMAIL);
        $phone = sanitize($input['phone']);
        $location = sanitize($input['location'] ?? '');
        $positionApplied = sanitize($input['positionApplied']);
        $experienceYears = intval($input['experienceYears'] ?? 0);
        $coverLetter = sanitize($input['coverLetter'] ?? '');
        $skills = sanitize($input['skills'] ?? '');
        $education = sanitize($input['education'] ?? '');

        if (!$email) {
            jsonResponse(false, 'Invalid email address');
        }

        try {
            // Check if already applied for this position
            $checkStmt = $conn->prepare("
                SELECT id FROM job_portal_applications 
                WHERE email = ? AND position_applied = ? AND application_status != 'rejected'
            ");
            $checkStmt->bind_param("ss", $email, $positionApplied);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                jsonResponse(false, 'You have already applied for this position');
            }

            // Insert application
            $stmt = $conn->prepare("
                INSERT INTO job_portal_applications 
                (full_name, email, phone, location, position_applied, experience_years, cover_letter, skills, education, application_status, applied_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'submitted', NOW())
            ");
            
            $stmt->bind_param(
                "sssssisss",
                $fullName,
                $email,
                $phone,
                $location,
                $positionApplied,
                $experienceYears,
                $coverLetter,
                $skills,
                $education
            );

            if ($stmt->execute()) {
                $applicationId = $conn->insert_id;
                logMessage("New job portal application: $email for $positionApplied (ID: $applicationId)");
                
                jsonResponse(true, 'Application submitted successfully!', [
                    'applicationId' => $applicationId,
                    'message' => 'We will review your application and contact you soon.'
                ]);
            } else {
                throw new Exception("Database error: " . $stmt->error);
            }
        } catch (Exception $e) {
            logMessage("Job portal application error: " . $e->getMessage());
            jsonResponse(false, 'Failed to submit application. Please try again.');
        }
    } 
    elseif ($action === 'withdraw_application') {
        $applicationId = intval($input['applicationId'] ?? 0);
        
        if ($applicationId <= 0) {
            jsonResponse(false, 'Invalid application ID');
        }

        try {
            $stmt = $conn->prepare("
                DELETE FROM job_portal_applications 
                WHERE id = ? AND application_status = 'submitted'
            ");
            $stmt->bind_param("i", $applicationId);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                logMessage("Application withdrawn: ID $applicationId");
                jsonResponse(true, 'Application withdrawn successfully');
            } else {
                jsonResponse(false, 'Cannot withdraw this application');
            }
        } catch (Exception $e) {
            logMessage("Error withdrawing application: " . $e->getMessage());
            jsonResponse(false, 'Failed to withdraw application');
        }
    }
    else {
        jsonResponse(false, 'Invalid action');
    }
}

$conn->close();
?>
