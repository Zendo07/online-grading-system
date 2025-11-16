<?php
session_start();
require_once dirname(__DIR__) . '/includes/config.php';

$student_id = $_SESSION['user_id'];

echo "<h1>Debug: Finding Your Professors</h1>";
echo "<p>Student ID: $student_id</p>";
echo "<hr>";

// Step 1: Check enrollments
echo "<h2>Step 1: Your Enrollments</h2>";
try {
    $query = "SELECT * FROM enrollments WHERE student_id = :student_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->execute();
    $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Found " . count($enrollments) . " enrollments</strong></p>";
    if (!empty($enrollments)) {
        echo "<pre>";
        print_r($enrollments);
        echo "</pre>";
        
        // Get column names
        if (isset($enrollments[0])) {
            echo "<p><strong>Enrollment columns:</strong> " . implode(', ', array_keys($enrollments[0])) . "</p>";
        }
    } else {
        echo "<p style='color:red;'>No enrollments found!</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red;'>ERROR: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Step 2: Check classes table
echo "<h2>Step 2: Classes Table Check</h2>";
try {
    $query = "SELECT * FROM classes LIMIT 3";
    $stmt = $conn->query($query);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Sample classes:</strong></p>";
    echo "<pre>";
    print_r($classes);
    echo "</pre>";
    
    if (!empty($classes)) {
        echo "<p><strong>Classes columns:</strong> " . implode(', ', array_keys($classes[0])) . "</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red;'>ERROR: " . $e->getMessage() . "</p>";
    echo "<p>Maybe table is called 'subjects' or 'courses' instead of 'classes'?</p>";
}

echo "<hr>";

// Step 3: Check users table
echo "<h2>Step 3: Users/Instructors Table Check</h2>";
try {
    $query = "SELECT user_id, full_name, email, role FROM users WHERE role = 'instructor' LIMIT 3";
    $stmt = $conn->query($query);
    $instructors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Sample instructors:</strong></p>";
    echo "<pre>";
    print_r($instructors);
    echo "</pre>";
} catch (PDOException $e) {
    echo "<p style='color:red;'>ERROR: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Step 4: Try to JOIN them
echo "<h2>Step 4: Trying to JOIN Tables</h2>";
try {
    // Try with classes table
    $query = "
        SELECT 
            e.*,
            c.*,
            u.full_name as instructor_name,
            u.email as instructor_email
        FROM enrollments e
        LEFT JOIN classes c ON e.class_id = c.class_id
        LEFT JOIN users u ON c.instructor_id = u.user_id
        WHERE e.student_id = :student_id
        LIMIT 5
    ";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->execute();
    $joined = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Joined data (enrollments + classes + users):</strong></p>";
    if (!empty($joined)) {
        echo "<pre>";
        print_r($joined);
        echo "</pre>";
    } else {
        echo "<p style='color:red;'>JOIN returned no results!</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red;'>JOIN ERROR: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Step 5: Show all tables in database
echo "<h2>Step 5: All Tables in Database</h2>";
try {
    $query = "SHOW TABLES";
    $stmt = $conn->query($query);
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p><strong>Available tables:</strong></p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
} catch (PDOException $e) {
    echo "<p style='color:red;'>ERROR: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>📋 Summary</h2>";
echo "<p>Please check the output above and tell me:</p>";
echo "<ol>";
echo "<li>What is the EXACT name of your subjects/classes table?</li>";
echo "<li>What column in enrollments links to your subjects? (class_id? subject_id? course_id?)</li>";
echo "<li>What column in your subjects table links to instructor? (instructor_id? teacher_id? user_id?)</li>";
echo "<li>Is the 'status' column in enrollments set to 'active'?</li>";
echo "</ol>";

?>

<style>
body {
    font-family: Arial, sans-serif;
    padding: 20px;
    background: #f5f5f5;
}
h1, h2 {
    color: #7b2d26;
}
pre {
    background: white;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    overflow-x: auto;
}
hr {
    margin: 30px 0;
    border: none;
    border-top: 2px solid #7b2d26;
}
</style>