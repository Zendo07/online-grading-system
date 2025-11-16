<?php
error_reporting(0);
ini_set('display_errors', 0);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once __DIR__ . '/../database/connection.php';

// Initialize database connection
$database = new Database();
$conn = $database->connect();

// Define base URL - CHANGE THIS TO YOUR PROJECT URL
define('BASE_URL', 'http://localhost/online-grading-system/');

// Define paths
define('ASSETS_PATH', BASE_URL . 'assets/');
define('CSS_PATH', ASSETS_PATH . 'css/');
define('JS_PATH', ASSETS_PATH . 'js/');
define('IMG_PATH', ASSETS_PATH . 'images/');
define('CSS_PATH', BASE_URL . 'assets/css/');

// Security settings
define('PASSWORD_MIN_LENGTH', 8);
define('SESSION_TIMEOUT', 3600); 

// Teacher invitation code (can be changed)
define('TEACHER_INVITATION_CODE', 'TEAC2025');

// Timezone
date_default_timezone_set('Asia/Manila');

// Error reporting (DISABLE IN PRODUCTION)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>