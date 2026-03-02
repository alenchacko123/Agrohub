<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config.php';

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';
$user_id = intval($input['user_id'] ?? $_GET['user_id'] ?? 0);

try {
    $conn = getDBConnection();

    switch ($action) {

        // ---------- GET SINGLE USER ----------
        case 'get_user':
            if (!$user_id) throw new Exception("User ID required");
            $stmt = $conn->prepare("SELECT id, name, email, user_type, phone, created_at, is_suspended FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            if (!$user) throw new Exception("User not found");
            echo json_encode(['success' => true, 'user' => $user]);
            break;

        // ---------- DELETE USER ----------
        case 'delete_user':
            if (!$user_id) throw new Exception("User ID required");
            // Prevent deleting self or admin
            $check = $conn->prepare("SELECT user_type FROM users WHERE id = ?");
            $check->bind_param("i", $user_id);
            $check->execute();
            $row = $check->get_result()->fetch_assoc();
            if (!$row) throw new Exception("User not found");
            if ($row['user_type'] === 'admin') throw new Exception("Cannot delete admin accounts");

            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            break;

        // ---------- SUSPEND / UNSUSPEND USER ----------
        case 'suspend_user':
            if (!$user_id) throw new Exception("User ID required");
            $suspend = intval($input['suspend'] ?? 1); // 1=suspend, 0=unsuspend

            // Ensure column exists
            $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_suspended TINYINT(1) DEFAULT 0");

            $stmt = $conn->prepare("UPDATE users SET is_suspended = ? WHERE id = ?");
            $stmt->bind_param("ii", $suspend, $user_id);
            $stmt->execute();
            $msg = $suspend ? 'User suspended successfully' : 'User unsuspended successfully';
            echo json_encode(['success' => true, 'message' => $msg]);
            break;

        // ---------- CHANGE USER ROLE ----------
        case 'change_role':
            if (!$user_id) throw new Exception("User ID required");
            $new_role = $input['role'] ?? '';
            $allowed_roles = ['farmer', 'owner', 'worker', 'admin'];
            if (!in_array($new_role, $allowed_roles)) throw new Exception("Invalid role");

            $stmt = $conn->prepare("UPDATE users SET user_type = ? WHERE id = ?");
            $stmt->bind_param("si", $new_role, $user_id);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => "User role changed to $new_role"]);
            break;

        // ---------- RESET PASSWORD (Send Reset Link Logic) ----------
        case 'reset_password':
            if (!$user_id) throw new Exception("User ID required");
            $new_password = $input['password'] ?? '';
            if (strlen($new_password) < 8) throw new Exception("Password must be at least 8 characters");

            $hashed = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed, $user_id);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Password reset successfully']);
            break;

        // ---------- SEND NOTIFICATION TO USER ----------
        case 'send_notification':
            if (!$user_id) throw new Exception("User ID required");
            $message = trim($input['message'] ?? '');
            if (empty($message)) throw new Exception("Message cannot be empty");

            // Ensure notifications table has user_id support
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, created_at, is_read) VALUES (?, ?, 'admin', NOW(), 0)");
            $stmt->bind_param("is", $user_id, $message);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Notification sent successfully']);
            break;

        // ---------- GET ALL USERS WITH FILTERS ----------
        case 'get_users':
            $role_filter = $input['role'] ?? $_GET['role'] ?? '';
            $search = $input['search'] ?? $_GET['search'] ?? '';
            $status_filter = $input['status'] ?? $_GET['status'] ?? '';

            // Ensure column exists
            $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_suspended TINYINT(1) DEFAULT 0");

            $where = [];
            $params = [];
            $types = '';

            if ($role_filter && $role_filter !== 'all') {
                $where[] = "user_type = ?";
                $params[] = $role_filter;
                $types .= 's';
            }
            if ($search) {
                $like = "%$search%";
                $where[] = "(name LIKE ? OR email LIKE ?)";
                $params[] = $like;
                $params[] = $like;
                $types .= 'ss';
            }
            if ($status_filter === 'suspended') {
                $where[] = "is_suspended = 1";
            } elseif ($status_filter === 'active') {
                $where[] = "is_suspended = 0";
            }

            $sql = "SELECT id, name, email, user_type, phone, created_at, is_suspended FROM users";
            if ($where) $sql .= " WHERE " . implode(" AND ", $where);
            $sql .= " ORDER BY created_at DESC";

            $stmt = $conn->prepare($sql);
            if ($params) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $users = [];
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
            echo json_encode(['success' => true, 'users' => $users, 'count' => count($users)]);
            break;

        default:
            throw new Exception("Invalid action: $action");
    }

    $conn->close();

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
