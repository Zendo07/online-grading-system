<?php
ob_start();

require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/email-config.php';

session_start();

ob_end_clean();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

if (!isset($_SESSION['reset_email'])) {
    echo json_encode(['success' => false, 'message' => 'No session found']);
    exit();
}

$email = $_SESSION['reset_email'];

try {
    $stmt = $conn->prepare("SELECT user_id, full_name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }

    $reset_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires_at = date('Y-m-d H:i:s', strtotime('+30 minutes'));

    $conn->beginTransaction();

    $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE email = ? AND used = 0");
    $stmt->execute([$email]);

    $stmt = $conn->prepare("INSERT INTO password_resets (user_id, email, reset_code, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user['user_id'], $email, $reset_code, $expires_at]);

    $email_sent = sendPasswordResetEmail($email, $user['full_name'], $reset_code);

    if (!$email_sent) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Email failed']);
        exit();
    }

    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Code sent']);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Resend Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
exit();