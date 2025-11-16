<?php
/**
 * Debug View Grades Page
 * File: teacher/debug-view-grades.php
 * Specific debugger for view-grades.php functionality
 * Compatible with PDO database connection
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>View Grades Debug Tool (PDO Compatible)</h1>";
echo "<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; background: #f5f5f5; }
    .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .success { color: #28a745; font-weight: bold; }
    .error { color: #dc3545; font-weight: bold; }
    .warning { color: #ffc107; font-weight: bold; }
    .info { color: #17a2b8; }
    pre { background: #f8f8f8; padding: 15px; border-radius: 4px; overflow-x: auto; border-left: 4px solid #007bff; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    table th, table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
    table th { background: #007bff; color: white; font-weight: 600; }
    .pass { background: #d4edda; }
    .fail { background: #f8d7da; }
    .warn { background: #fff3cd; }
    h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
    .step { background: #e9ecef; padding: 10px; border-radius: 4px; margin: 10px 0; }
</style>";

// Get class_id from URL
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : null;

echo "<div class='section'>";
echo "<h2>🎯 Testing Parameters</h2>";
if ($class_id) {
    echo "<p class='success'>✓ Testing with class_id: <strong>$class_id</strong></p>";
} else {
    echo "<p class='warning'>⚠ No class_id provided. Add ?class_id=30 to URL to test with a specific class</p>";
    echo "<p class='info'>Example: debug-view-grades.php?class_id=30</p>";
}
echo "</div>";

// Step 1: File paths check
echo "<div class='section'>";
echo "<h2>📁 Step 1: File Paths Verification</h2>";

$required_files = [
    'Config' => '../includes/config.php',
    'Session' => '../includes/session.php',
    'Functions' => '../includes/functions.php',
    'Handler' => '../api/teacher/view-grades-handler.php',
    'View Grades Page' => 'view-grades.php',
    'CSS' => '../assets/css/teacher-pages/view-grades.css',
    'JS' => '../assets/js/teacher-pages/view-grades.js'
];

echo "<table>";
echo "<tr><th>File</th><th>Status</th><th>Full Path</th></tr>";

$all_files_exist = true;
foreach ($required_files as $name => $path) {
    $exists = file_exists($path);
    $all_files_exist = $all_files_exist && $exists;
    $class = $exists ? 'pass' : 'fail';
    $status = $exists ? '✓ Found' : '✗ Missing';
    $full_path = $exists ? realpath($path) : 'File not found';
    
    echo "<tr class='$class'>";
    echo "<td><strong>$name</strong></td>";
    echo "<td>$status</td>";
    echo "<td style='font-size: 0.85em;'>$full_path</td>";
    echo "</tr>";
}
echo "</table>";

if ($all_files_exist) {
    echo "<p class='success'>✓ All required files exist</p>";
} else {
    echo "<p class='error'>✗ Some files are missing. Please check the paths above.</p>";
}
echo "</div>";

// Step 2: Load config and test connection
echo "<div class='section'>";
echo "<h2>🔌 Step 2: Database Connection Test (PDO)</h2>";

try {
    require_once '../includes/config.php';
    echo "<p class='success'>✓ Config file loaded successfully</p>";
    
    if (isset($conn) && $conn instanceof PDO) {
        echo "<p class='success'>✓ Database connection object created (PDO)</p>";
        
        try {
            // Test connection with PDO
            $conn->query('SELECT 1');
            echo "<p class='success'>✓ Database connection is ACTIVE</p>";
            echo "<div class='step'>";
            echo "<strong>Connection Details:</strong><br>";
            echo "• Type: PDO<br>";
            echo "• Database: " . DB_NAME . "<br>";
            echo "• Server: " . DB_SERVER . "<br>";
            $version = $conn->query('SELECT VERSION()')->fetchColumn();
            echo "• MySQL Version: " . $version . "<br>";
            echo "</div>";
        } catch (PDOException $e) {
            echo "<p class='error'>✗ Database connection test failed: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p class='error'>✗ Database connection object is invalid or not PDO</p>";
        if (isset($conn)) {
            echo "<p class='info'>Connection type: " . get_class($conn) . "</p>";
        }
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error loading config: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Step 3: Session check
echo "<div class='section'>";
echo "<h2>👤 Step 3: Session & Authentication</h2>";

try {
    require_once '../includes/session.php';
    echo "<p class='success'>✓ Session file loaded</p>";
    
    if (session_status() === PHP_SESSION_ACTIVE) {
        echo "<p class='success'>✓ Session is ACTIVE</p>";
        echo "<p class='info'>Session ID: " . session_id() . "</p>";
        
        if (isset($_SESSION['user_id'])) {
            echo "<div class='step'>";
            echo "<strong>Logged in User:</strong><br>";
            echo "• User ID: " . $_SESSION['user_id'] . "<br>";
            echo "• Role: " . ($_SESSION['role'] ?? 'Not set') . "<br>";
            echo "• Email: " . ($_SESSION['email'] ?? 'Not set') . "<br>";
            echo "</div>";
            
            if ($_SESSION['role'] === 'teacher') {
                echo "<p class='success'>✓ User is logged in as TEACHER</p>";
            } else {
                echo "<p class='error'>✗ User is not a teacher (Role: " . $_SESSION['role'] . ")</p>";
            }
        } else {
            echo "<p class='error'>✗ No user logged in. Please log in as a teacher first.</p>";
            echo "<p class='info'>Go to: <a href='../auth/login.php'>Login Page</a></p>";
        }
    } else {
        echo "<p class='error'>✗ Session is not active</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Session error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Step 4: Load handler and check functions
echo "<div class='section'>";
echo "<h2>⚙️ Step 4: Handler Functions Test</h2>";

try {
    require_once '../api/teacher/view-grades-handler.php';
    echo "<p class='success'>✓ Handler file loaded successfully</p>";
    
    $functions_to_check = [
        'getClassInfo' => 'Get class information',
        'getEnrolledStudents' => 'Get enrolled students list',
        'getClassGrades' => 'Get all grades for class',
        'getClassAttendance' => 'Get attendance records',
        'saveGrade' => 'Save/update grade',
        'saveAttendance' => 'Save/update attendance',
        'deleteGrade' => 'Delete a grade'
    ];
    
    echo "<table>";
    echo "<tr><th>Function</th><th>Description</th><th>Status</th></tr>";
    
    $all_functions_exist = true;
    foreach ($functions_to_check as $func => $desc) {
        $exists = function_exists($func);
        $all_functions_exist = $all_functions_exist && $exists;
        $class = $exists ? 'pass' : 'fail';
        $status = $exists ? '✓ Defined' : '✗ Missing';
        
        echo "<tr class='$class'>";
        echo "<td><code>$func()</code></td>";
        echo "<td>$desc</td>";
        echo "<td>$status</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if ($all_functions_exist) {
        echo "<p class='success'>✓ All handler functions are defined</p>";
    } else {
        echo "<p class='error'>✗ Some handler functions are missing</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Error loading handler: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Step 5: Database tables check
echo "<div class='section'>";
echo "<h2>🗄️ Step 5: Database Tables Verification</h2>";

if (isset($conn) && $conn instanceof PDO) {
    $required_tables = [
        'classes' => 'Class information',
        'enrollments' => 'Student enrollments',
        'users' => 'User accounts',
        'grades' => 'Grade records',
        'attendance' => 'Attendance records',
        'class_schedules' => 'Class schedules'
    ];
    
    echo "<table>";
    echo "<tr><th>Table</th><th>Description</th><th>Status</th><th>Records</th></tr>";
    
    foreach ($required_tables as $table => $desc) {
        try {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result && $result->rowCount() > 0) {
                $count_result = $conn->query("SELECT COUNT(*) as count FROM `$table`");
                $count = $count_result ? $count_result->fetchColumn() : 0;
                
                echo "<tr class='pass'>";
                echo "<td><code>$table</code></td>";
                echo "<td>$desc</td>";
                echo "<td>✓ Exists</td>";
                echo "<td><strong>$count</strong> records</td>";
                echo "</tr>";
            } else {
                echo "<tr class='fail'>";
                echo "<td><code>$table</code></td>";
                echo "<td>$desc</td>";
                echo "<td>✗ Missing</td>";
                echo "<td>N/A</td>";
                echo "</tr>";
            }
        } catch (PDOException $e) {
            echo "<tr class='fail'>";
            echo "<td><code>$table</code></td>";
            echo "<td>$desc</td>";
            echo "<td>✗ Error</td>";
            echo "<td>N/A</td>";
            echo "</tr>";
        }
    }
    echo "</table>";
} else {
    echo "<p class='error'>✗ Cannot verify tables - database connection failed</p>";
}
echo "</div>";

// Step 6: Test with actual class_id
if ($class_id && isset($conn) && $conn instanceof PDO) {
    echo "<div class='section'>";
    echo "<h2>🧪 Step 6: Testing with Class ID: $class_id</h2>";
    
    try {
        // Check if class exists
        $class_check = $conn->prepare("SELECT * FROM classes WHERE class_id = :class_id");
        $class_check->execute([':class_id' => $class_id]);
        $class_data = $class_check->fetch(PDO::FETCH_ASSOC);
        
        if ($class_data) {
            echo "<p class='success'>✓ Class found in database</p>";
            
            echo "<div class='step'>";
            echo "<strong>Class Details:</strong><br>";
            echo "• Class ID: " . $class_data['class_id'] . "<br>";
            echo "• Subject: " . $class_data['subject'] . "<br>";
            echo "• Name: " . $class_data['class_name'] . "<br>";
            echo "• Section: " . $class_data['section'] . "<br>";
            echo "• Code: " . $class_data['class_code'] . "<br>";
            echo "• Teacher ID: " . $class_data['teacher_id'] . "<br>";
            echo "• Status: " . $class_data['status'] . "<br>";
            echo "</div>";
            
            // Check if logged in user is the teacher
            if (isset($_SESSION['user_id'])) {
                if ($class_data['teacher_id'] == $_SESSION['user_id']) {
                    echo "<p class='success'>✓ Logged in user OWNS this class</p>";
                } else {
                    echo "<p class='error'>✗ Logged in user does NOT own this class</p>";
                    echo "<p class='info'>Class belongs to teacher ID: " . $class_data['teacher_id'] . "</p>";
                    echo "<p class='info'>Logged in as user ID: " . $_SESSION['user_id'] . "</p>";
                }
            }
            
            // Test getClassInfo function
            if (function_exists('getClassInfo') && isset($_SESSION['user_id'])) {
                echo "<h3>Testing getClassInfo() function:</h3>";
                $class_info = getClassInfo($class_id, $_SESSION['user_id']);
                
                if ($class_info) {
                    echo "<p class='success'>✓ getClassInfo() returned data</p>";
                    echo "<pre>";
                    print_r($class_info);
                    echo "</pre>";
                } else {
                    echo "<p class='warning'>⚠ getClassInfo() returned NULL</p>";
                    echo "<p class='info'>This might happen if the class doesn't belong to the logged-in teacher</p>";
                }
            }
            
            // Test getEnrolledStudents function
            if (function_exists('getEnrolledStudents')) {
                echo "<h3>Testing getEnrolledStudents() function:</h3>";
                $students = getEnrolledStudents($class_id);
                
                echo "<p class='success'>✓ Found <strong>" . count($students) . "</strong> enrolled students</p>";
                
                if (count($students) > 0) {
                    echo "<div class='step'>";
                    echo "<strong>Sample Student (First in list):</strong>";
                    echo "<pre>";
                    print_r($students[0]);
                    echo "</pre>";
                    echo "</div>";
                } else {
                    echo "<p class='warning'>⚠ No students enrolled in this class</p>";
                }
            }
            
            // Test getClassGrades function
            if (function_exists('getClassGrades')) {
                echo "<h3>Testing getClassGrades() function:</h3>";
                $grades = getClassGrades($class_id);
                
                echo "<p class='success'>✓ Found <strong>" . count($grades) . "</strong> grade records</p>";
                
                if (count($grades) > 0) {
                    echo "<div class='step'>";
                    echo "<strong>Sample Grade (First record):</strong>";
                    echo "<pre>";
                    print_r($grades[0]);
                    echo "</pre>";
                    echo "</div>";
                } else {
                    echo "<p class='info'>No grades recorded yet (this is normal for new classes)</p>";
                }
            }
            
            // Test getClassAttendance function
            if (function_exists('getClassAttendance')) {
                echo "<h3>Testing getClassAttendance() function:</h3>";
                $attendance = getClassAttendance($class_id);
                
                echo "<p class='success'>✓ Found <strong>" . count($attendance) . "</strong> attendance records</p>";
                
                if (count($attendance) > 0) {
                    echo "<div class='step'>";
                    echo "<strong>Sample Attendance (First record):</strong>";
                    echo "<pre>";
                    print_r($attendance[0]);
                    echo "</pre>";
                    echo "</div>";
                } else {
                    echo "<p class='info'>No attendance recorded yet (this is normal for new classes)</p>";
                }
            }
            
        } else {
            echo "<p class='error'>✗ Class with ID $class_id NOT FOUND in database</p>";
            
            // Show available classes
            $available = $conn->query("SELECT class_id, subject, class_name, section FROM classes WHERE status = 'active' LIMIT 5");
            if ($available && $available->rowCount() > 0) {
                echo "<p class='info'>Available classes to test:</p>";
                echo "<ul>";
                while ($row = $available->fetch(PDO::FETCH_ASSOC)) {
                    $test_url = "debug-view-grades.php?class_id=" . $row['class_id'];
                    echo "<li><a href='$test_url'>Class ID {$row['class_id']}: {$row['subject']} - {$row['class_name']} ({$row['section']})</a></li>";
                }
                echo "</ul>";
            }
        }
    } catch (PDOException $e) {
        echo "<p class='error'>✗ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "</div>";
}

// Step 7: Test view-grades.php access
echo "<div class='section'>";
echo "<h2>🔗 Step 7: Direct Access Test</h2>";

if ($class_id) {
    $test_url = "view-grades.php?class_id=$class_id";
    echo "<p class='info'>Test URL: <a href='$test_url' target='_blank' style='font-weight: bold;'>$test_url</a></p>";
    echo "<p>Click the link above to test the actual view-grades.php page</p>";
} else {
    echo "<p class='warning'>⚠ Add ?class_id=30 to test with a specific class</p>";
}

echo "</div>";

// Summary
echo "<div class='section'>";
echo "<h2>📋 Summary & Next Steps</h2>";

echo "<h3>✅ What to do next:</h3>";
echo "<ol>";
echo "<li>Make sure you're logged in as a teacher</li>";
echo "<li>Look at the test results above to identify any issues</li>";
echo "<li>If all tests pass, try accessing <a href='view-grades.php?class_id=$class_id'>view-grades.php directly</a></li>";
echo "<li>Check browser console (F12) for JavaScript errors</li>";
echo "</ol>";

echo "</div>";

echo "<div style='text-align: center; margin-top: 30px; padding: 20px; background: #e9ecef; border-radius: 8px;'>";
echo "<p><strong>🔍 Debug Complete!</strong></p>";
echo "<p>All functions are now compatible with PDO</p>";
echo "<p style='color: #666; font-size: 0.9em;'>Timestamp: " . date('Y-m-d H:i:s') . "</p>";
echo "</div>";
?>