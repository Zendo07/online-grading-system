<?php
/**
 * DEBUG SCRIPT - Test Join Class Functionality
 * Place this in your root directory and access via browser
 * URL: http://yoursite.com/test-join-class.php?code=YOUR_CLASS_CODE
 */

require_once 'includes/config.php';

// Get class code from URL
$class_code = isset($_GET['code']) ? strtoupper(trim($_GET['code'])) : '';

echo "<!DOCTYPE html><html><head><title>Join Class Debug</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5;}";
echo ".success{color:green;background:#d4edda;padding:10px;margin:10px 0;border:1px solid green;}";
echo ".error{color:red;background:#f8d7da;padding:10px;margin:10px 0;border:1px solid red;}";
echo ".info{color:blue;background:#d1ecf1;padding:10px;margin:10px 0;border:1px solid blue;}";
echo "pre{background:white;padding:15px;border:1px solid #ddd;overflow:auto;}</style></head><body>";

echo "<h1>Join Class Debug Tool</h1>";
echo "<p>Current Class Code: <strong>" . htmlspecialchars($class_code) . "</strong></p>";

if (empty($class_code)) {
    echo "<div class='error'>No class code provided. Add ?code=YOUR_CODE to URL</div>";
    echo "<p>Example: test-join-class.php?code=PSU-ABC123</p>";
    exit();
}

try {
    echo "<div class='info'>Database Connection: OK</div>";
    
    // Test 1: Check if class exists
    echo "<h2>Test 1: Class Lookup</h2>";
    $stmt = $conn->prepare("
        SELECT 
            c.*,
            u.full_name as teacher_name
        FROM classes c
        LEFT JOIN users u ON c.teacher_id = u.user_id
        WHERE c.class_code = ?
    ");
    $stmt->execute([$class_code]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($class) {
        echo "<div class='success'>Class found!</div>";
        echo "<pre>" . print_r($class, true) . "</pre>";
        
        if ($class['status'] !== 'active') {
            echo "<div class='error'>WARNING: Class status is '" . $class['status'] . "' (not active)</div>";
        }
    } else {
        echo "<div class='error'>Class NOT found with code: " . $class_code . "</div>";
        
        // Check all classes
        $stmt = $conn->query("SELECT class_id, class_code, class_name, status FROM classes ORDER BY created_at DESC LIMIT 10");
        $all_classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Recent Classes in Database:</h3>";
        echo "<pre>" . print_r($all_classes, true) . "</pre>";
        exit();
    }
    
    // Test 2: Check enrollments table structure
    echo "<h2>Test 2: Enrollments Table Structure</h2>";
    $stmt = $conn->query("DESCRIBE enrollments");
    $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($structure, true) . "</pre>";
    
    // Test 3: Check existing enrollments for this class
    echo "<h2>Test 3: Current Enrollments</h2>";
    $stmt = $conn->prepare("
        SELECT e.*, u.full_name as student_name 
        FROM enrollments e
        LEFT JOIN users u ON e.student_id = u.user_id
        WHERE e.class_id = ?
        ORDER BY e.enrolled_at DESC
        LIMIT 10
    ");
    $stmt->execute([$class['class_id']]);
    $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($enrollments) {
        echo "<div class='success'>Found " . count($enrollments) . " enrollment(s)</div>";
        echo "<pre>" . print_r($enrollments, true) . "</pre>";
    } else {
        echo "<div class='info'>No enrollments yet for this class</div>";
    }
    
    // Test 4: Check if we're logged in (if session exists)
    session_start();
    echo "<h2>Test 4: Session Check</h2>";
    if (isset($_SESSION['user_id'])) {
        echo "<div class='success'>User logged in: ID=" . $_SESSION['user_id'] . "</div>";
        
        // Check if already enrolled
        $stmt = $conn->prepare("
            SELECT * FROM enrollments 
            WHERE student_id = ? AND class_id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $class['class_id']]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            echo "<div class='info'>Already enrolled with status: " . $existing['status'] . "</div>";
            echo "<pre>" . print_r($existing, true) . "</pre>";
        } else {
            echo "<div class='info'>Not yet enrolled in this class</div>";
        }
    } else {
        echo "<div class='error'>Not logged in - Session user_id not found</div>";
    }
    
    // Test 5: Simulate enrollment
    echo "<h2>Test 5: Simulate Enrollment (DRY RUN)</h2>";
    echo "<div class='info'>This is a simulation - no data will be inserted</div>";
    
    $test_student_id = $_SESSION['user_id'] ?? 999999; // Use logged in user or fake ID
    
    echo "<p>Would insert: student_id=$test_student_id, class_id={$class['class_id']}</p>";
    
    $sql = "INSERT INTO enrollments (student_id, class_id, status, enrolled_at, updated_at) 
            VALUES (?, ?, 'active', NOW(), NOW())";
    echo "<p>SQL: <code>$sql</code></p>";
    
    echo "<div class='success'>All tests completed!</div>";
    echo "<h3>Next Steps:</h3>";
    echo "<ul>";
    echo "<li>If class found: Try joining via the normal form</li>";
    echo "<li>If class not found: Check class code spelling</li>";
    echo "<li>Check error logs for detailed info</li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<div class='error'>Database Error!</div>";
    echo "<pre>";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "</pre>";
}

echo "</body></html>";
?>