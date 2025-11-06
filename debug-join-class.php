<?php
// Create this file as: debug-join-class.php in your root folder
// Access it at: http://localhost/online-grading-system/debug-join-class.php

require_once 'includes/config.php';
require_once 'includes/functions.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Join Class</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #0f0; }
        .section { background: #000; padding: 15px; margin: 10px 0; border: 1px solid #0f0; }
        .error { color: #f00; }
        .success { color: #0f0; }
        .warning { color: #ff0; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #0f0; padding: 8px; text-align: left; }
        th { background: #003300; }
        input, button { background: #000; color: #0f0; border: 1px solid #0f0; padding: 10px; margin: 5px; }
        button { cursor: pointer; }
        button:hover { background: #003300; }
    </style>
</head>
<body>
    <h1>🔍 Join Class Debug Tool</h1>
    
    <?php
    // TEST 1: Check Database Connection
    echo '<div class="section">';
    echo '<h2>1. Database Connection</h2>';
    try {
        $conn->query('SELECT 1');
        echo '<p class="success">✅ Database connected successfully!</p>';
    } catch (Exception $e) {
        echo '<p class="error">❌ Database connection failed: ' . $e->getMessage() . '</p>';
    }
    echo '</div>';
    
    // TEST 2: Check Classes Table Structure
    echo '<div class="section">';
    echo '<h2>2. Classes Table Structure</h2>';
    try {
        $stmt = $conn->query("DESCRIBE classes");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo '<table>';
        echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>';
        foreach ($columns as $col) {
            echo '<tr>';
            echo '<td>' . $col['Field'] . '</td>';
            echo '<td>' . $col['Type'] . '</td>';
            echo '<td>' . $col['Null'] . '</td>';
            echo '<td>' . $col['Key'] . '</td>';
            echo '<td>' . ($col['Default'] ?? 'NULL') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        
        // Check for required columns
        $required_cols = ['class_id', 'class_code', 'status', 'class_name', 'subject', 'teacher_id'];
        $existing_cols = array_column($columns, 'Field');
        $missing = array_diff($required_cols, $existing_cols);
        
        if (empty($missing)) {
            echo '<p class="success">✅ All required columns exist!</p>';
        } else {
            echo '<p class="error">❌ Missing columns: ' . implode(', ', $missing) . '</p>';
        }
        
    } catch (Exception $e) {
        echo '<p class="error">❌ Error checking table structure: ' . $e->getMessage() . '</p>';
    }
    echo '</div>';
    
    // TEST 3: List All Active Classes
    echo '<div class="section">';
    echo '<h2>3. Active Classes in Database</h2>';
    try {
        $stmt = $conn->query("
            SELECT 
                c.class_id,
                c.class_code,
                c.class_name,
                c.subject,
                c.section,
                c.status,
                CONCAT(u.first_name, ' ', u.last_name) as teacher_name
            FROM classes c
            LEFT JOIN users u ON c.teacher_id = u.user_id
            ORDER BY c.created_at DESC
            LIMIT 20
        ");
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($classes)) {
            echo '<p class="warning">⚠️ No classes found in database!</p>';
            echo '<p>Teachers need to create classes first.</p>';
        } else {
            echo '<p class="success">✅ Found ' . count($classes) . ' classes</p>';
            echo '<table>';
            echo '<tr><th>ID</th><th>Code</th><th>Name</th><th>Subject</th><th>Section</th><th>Teacher</th><th>Status</th></tr>';
            foreach ($classes as $class) {
                $status_class = $class['status'] === 'active' ? 'success' : 'error';
                echo '<tr>';
                echo '<td>' . $class['class_id'] . '</td>';
                echo '<td><strong>' . $class['class_code'] . '</strong></td>';
                echo '<td>' . $class['class_name'] . '</td>';
                echo '<td>' . $class['subject'] . '</td>';
                echo '<td>' . $class['section'] . '</td>';
                echo '<td>' . ($class['teacher_name'] ?? 'N/A') . '</td>';
                echo '<td class="' . $status_class . '">' . strtoupper($class['status']) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    } catch (Exception $e) {
        echo '<p class="error">❌ Error fetching classes: ' . $e->getMessage() . '</p>';
    }
    echo '</div>';
    
    // TEST 4: Test Join Functionality
    echo '<div class="section">';
    echo '<h2>4. Test Join Class Function</h2>';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_code'])) {
        $test_code = strtoupper(trim($_POST['test_code']));
        
        echo '<h3>Testing Code: ' . htmlspecialchars($test_code) . '</h3>';
        
        // Test the query
        try {
            $stmt = $conn->prepare("
                SELECT 
                    c.*,
                    CONCAT(u.first_name, ' ', u.last_name) as teacher_name
                FROM classes c
                LEFT JOIN users u ON c.teacher_id = u.user_id
                WHERE c.class_code = ?
            ");
            $stmt->execute([$test_code]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                echo '<p class="success">✅ Class found!</p>';
                echo '<table>';
                foreach ($result as $key => $value) {
                    echo '<tr><td><strong>' . $key . '</strong></td><td>' . htmlspecialchars($value ?? 'NULL') . '</td></tr>';
                }
                echo '</table>';
                
                // Check status
                if ($result['status'] === 'active') {
                    echo '<p class="success">✅ Status is ACTIVE - Students can join!</p>';
                } else {
                    echo '<p class="error">❌ Status is "' . $result['status'] . '" - Students CANNOT join!</p>';
                    echo '<p>Fix: Run this SQL:</p>';
                    echo '<pre>UPDATE classes SET status = "active" WHERE class_code = "' . $test_code . '";</pre>';
                }
            } else {
                echo '<p class="error">❌ No class found with code: ' . $test_code . '</p>';
                echo '<p>Possible issues:</p>';
                echo '<ul>';
                echo '<li>Class code does not exist in database</li>';
                echo '<li>Case sensitivity issue (try uppercase)</li>';
                echo '<li>Extra spaces in code</li>';
                echo '</ul>';
            }
            
        } catch (Exception $e) {
            echo '<p class="error">❌ Query failed: ' . $e->getMessage() . '</p>';
        }
    }
    
    echo '<form method="POST">';
    echo '<label>Enter Class Code to Test:</label><br>';
    echo '<input type="text" name="test_code" placeholder="e.g., PSU123" required>';
    echo '<button type="submit">Test Code</button>';
    echo '</form>';
    echo '</div>';
    
    // TEST 5: Check Enrollments Table
    echo '<div class="section">';
    echo '<h2>5. Recent Enrollments</h2>';
    try {
        $stmt = $conn->query("
            SELECT 
                e.*,
                CONCAT(s.first_name, ' ', s.last_name) as student_name,
                c.class_name,
                c.class_code
            FROM enrollments e
            LEFT JOIN users s ON e.student_id = s.user_id
            LEFT JOIN classes c ON e.class_id = c.class_id
            ORDER BY e.created_at DESC
            LIMIT 10
        ");
        $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($enrollments)) {
            echo '<p class="warning">⚠️ No enrollments found</p>';
        } else {
            echo '<table>';
            echo '<tr><th>Student</th><th>Class</th><th>Code</th><th>Status</th><th>Date</th></tr>';
            foreach ($enrollments as $enroll) {
                echo '<tr>';
                echo '<td>' . ($enroll['student_name'] ?? 'N/A') . '</td>';
                echo '<td>' . ($enroll['class_name'] ?? 'N/A') . '</td>';
                echo '<td>' . ($enroll['class_code'] ?? 'N/A') . '</td>';
                echo '<td>' . $enroll['status'] . '</td>';
                echo '<td>' . $enroll['created_at'] . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    } catch (Exception $e) {
        echo '<p class="error">❌ Error: ' . $e->getMessage() . '</p>';
    }
    echo '</div>';
    
    // TEST 6: Fix Inactive Classes
    echo '<div class="section">';
    echo '<h2>6. Quick Fix Tools</h2>';
    
    if (isset($_POST['fix_all_status'])) {
        try {
            $stmt = $conn->exec("UPDATE classes SET status = 'active' WHERE status IS NULL OR status = ''");
            echo '<p class="success">✅ Fixed ' . $stmt . ' classes with NULL/empty status!</p>';
        } catch (Exception $e) {
            echo '<p class="error">❌ Error: ' . $e->getMessage() . '</p>';
        }
    }
    
    echo '<form method="POST">';
    echo '<button type="submit" name="fix_all_status">Fix All Classes with NULL Status</button>';
    echo '</form>';
    echo '<p class="warning">⚠️ This will set all classes with NULL or empty status to "active"</p>';
    echo '</div>';
    ?>
    
    <div class="section">
        <h2>7. Manual SQL Queries</h2>
        <p>Run these in phpMyAdmin if needed:</p>
        <pre>
-- Check all class codes
SELECT class_code, class_name, status FROM classes;

-- Fix all classes to active status
UPDATE classes SET status = 'active' WHERE status IS NULL OR status = '';

-- Check for duplicate codes
SELECT class_code, COUNT(*) as count 
FROM classes 
GROUP BY class_code 
HAVING count > 1;

-- View all enrollments
SELECT * FROM enrollments ORDER BY created_at DESC LIMIT 20;
        </pre>
    </div>
</body>
</html>