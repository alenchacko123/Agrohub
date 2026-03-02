<?php
/**
 * job_messages API
 * GET  ?job_id=X&user_id=Y&since=Z   → fetch messages (marks worker/farmer messages as read)
 * POST { job_id, sender_id, sender_role, message } → insert message
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once 'config.php';

/* ── GET messages ─────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $job_id  = intval($_GET['job_id']  ?? 0);
    $user_id = intval($_GET['user_id'] ?? 0);
    $since   = isset($_GET['since']) ? intval($_GET['since']) : 0;

    if (!$job_id) { echo json_encode(['success'=>false,'message'=>'Missing job_id']); exit; }

    // Mark messages sent by the other party as read
    if ($user_id) {
        $stmt = $conn->prepare(
            "UPDATE job_messages SET is_read=1 WHERE job_id=? AND sender_id!=? AND is_read=0"
        );
        $stmt->bind_param('ii', $job_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    // Fetch messages (optionally only newer than a given id for polling)
    $sql  = "SELECT * FROM job_messages WHERE job_id=?";
    $args = [$job_id];
    $type = 'i';
    if ($since > 0) { $sql .= " AND id > ?"; $args[] = $since; $type .= 'i'; }
    $sql .= " ORDER BY created_at ASC LIMIT 200";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($type, ...$args);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Unread count for the REQUESTER (messages sent by others, unread)
    $unread = 0;
    if ($user_id) {
        $s = $conn->prepare("SELECT COUNT(*) AS cnt FROM job_messages WHERE job_id=? AND sender_id!=? AND is_read=0");
        $s->bind_param('ii', $job_id, $user_id);
        $s->execute();
        $unread = (int)$s->get_result()->fetch_assoc()['cnt'];
        $s->close();
    }

    echo json_encode(['success'=>true, 'messages'=>$rows, 'unread'=>$unread]);
    exit;
}

/* ── POST message ─────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);

    $job_id      = intval($body['job_id']      ?? 0);
    $sender_id   = intval($body['sender_id']   ?? 0);
    $sender_role = $body['sender_role']         ?? '';
    $message     = trim($body['message']        ?? '');

    if (!$job_id || !$sender_id || !in_array($sender_role, ['worker','farmer']) || $message === '') {
        echo json_encode(['success'=>false,'message'=>'Missing or invalid fields']); exit;
    }

    $stmt = $conn->prepare(
        "INSERT INTO job_messages (job_id, sender_id, sender_role, message) VALUES (?,?,?,?)"
    );
    $stmt->bind_param('iiss', $job_id, $sender_id, $sender_role, $message);

    if ($stmt->execute()) {
        echo json_encode(['success'=>true, 'id'=>$conn->insert_id]);
    } else {
        echo json_encode(['success'=>false, 'message'=>$conn->error]);
    }
    $stmt->close();
    $conn->close();
    exit;
}

echo json_encode(['success'=>false,'message'=>'Invalid request method']);
