<?php
// Admin Settings API — GET (load) and POST (save)
require_once dirname(__DIR__) . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Default settings values (used when creating table for the first time)
$DEFAULTS = [
    'platform_name'              => 'AgroHub',
    'platform_logo'              => '',
    'support_email'              => 'support@agrohub.com',
    'registration_open'          => 'true',
    'maintenance_mode'           => 'false',
    'commission_percentage'      => '5',
    'currency'                   => 'INR',
    'rental_policy'              => "1. Equipment must be returned clean.\n2. Damages are the renter's responsibility.\n3. Late returns incur a fee.",
    'agreement_template'         => "RENTAL AGREEMENT\n\nThis agreement confirms the rental of [Equipment] for [Duration]...",
    'digital_signature_enabled'  => 'true',
    'password_min_length'        => '8',
    'email_verification_required'=> 'true',
    'session_timeout_minutes'    => '30',
    'max_equipment_listings'     => '10',
    'max_job_applications'       => '50',
    'terms_and_conditions'       => 'Standard platform terms apply.',
];

try {
    $conn = getDBConnection();

    // ── Auto-create system_settings table if missing ────────────
    $conn->query("CREATE TABLE IF NOT EXISTS system_settings (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        setting_key   VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // ── Seed defaults if table is empty ────────────────────────
    $count = $conn->query("SELECT COUNT(*) as c FROM system_settings")->fetch_assoc()['c'];
    if ($count == 0) {
        $ins = $conn->prepare("INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES (?, ?)");
        foreach ($DEFAULTS as $k => $v) {
            $ins->bind_param("ss", $k, $v);
            $ins->execute();
        }
        $ins->close();
    }

    // ══════════════════════════════════════════════════════════════
    // GET — return all settings as key→value map
    // ══════════════════════════════════════════════════════════════
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $res = $conn->query("SELECT setting_key, setting_value FROM system_settings ORDER BY setting_key");
        $settings = [];
        while ($row = $res->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        // Merge with defaults so missing keys are always present
        $settings = array_merge($DEFAULTS, $settings);

        echo json_encode(['success' => true, 'settings' => $settings]);
        $conn->close();
        exit;
    }

    // ══════════════════════════════════════════════════════════════
    // POST — save settings
    // ══════════════════════════════════════════════════════════════
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);

        if (!$data || !is_array($data)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid JSON body']);
            exit;
        }

        // Only save keys that are in our allowed list (security: ignore unknown keys)
        $allowed = array_keys($DEFAULTS);

        $stmt = $conn->prepare(
            "INSERT INTO system_settings (setting_key, setting_value)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()"
        );

        $saved = 0;
        foreach ($data as $key => $value) {
            if (!in_array($key, $allowed)) continue; // skip unknown keys
            $val = strval($value);
            $stmt->bind_param("ss", $key, $val);
            if ($stmt->execute()) $saved++;
        }
        $stmt->close();
        $conn->close();

        echo json_encode([
            'success' => true,
            'message' => "Settings saved successfully ($saved keys updated)",
            'saved'   => $saved,
        ]);
        exit;
    }

    // Unknown method
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
