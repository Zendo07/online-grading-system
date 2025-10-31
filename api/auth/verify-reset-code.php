<?php
// Remove all output before this
ob_start();

require_once '../../includes/config.php';
require_once '../../includes/functions.php';

session_start();

// Clear any previous output
ob_end_clean();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!isset($_SESSION['reset_email'])) {
    echo json_encode(['success' => false, 'message' => 'No reset session found']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit();
}

$code = isset($input['code']) ? trim($input['code']) : '';

if (empty($code) || strlen($code) !== 6 || !ctype_digit($code)) {
    echo json_encode(['success' => false, 'message' => 'Invalid verification code']);
    exit();
}

$email = $_SESSION['reset_email'];

try {
    $stmt = $conn->prepare("
        SELECT reset_id, user_id, expires_at
        FROM password_resets
        WHERE email = ? 
        AND reset_code = ?
        AND used = 0
        ORDER BY created_at DESC
        LIMIT 1
    ");
    
    $stmt->execute([$email, $code]);
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reset) {
        echo json_encode(['success' => false, 'message' => 'Invalid verification code']);
        exit();
    }

    if (strtotime($reset['expires_at']) < time()) {
        echo json_encode(['success' => false, 'message' => 'Code has expired']);
        exit();
    }

    $_SESSION['reset_id'] = $reset['reset_id'];
    $_SESSION['reset_user_id'] = $reset['user_id'];

    echo json_encode(['success' => true, 'message' => 'Code verified']);

} catch (Exception $e) {
    error_log("Verify Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
exit();