<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Teacher Session - Diagnostic Tool</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #7f1d1d;
            border-bottom: 3px solid #7f1d1d;
            padding-bottom: 10px;
        }
        h3 {
            color: #333;
            margin-top: 25px;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
        }
        .success {
            color: #22c55e;
            font-weight: bold;
        }
        .error {
            color: #ef4444;
            font-weight: bold;
        }
        .warning {
            color: #f59e0b;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #7f1d1d;
            color: white;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .info-box {
            background: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .alert-box {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .success-box {
            background: #d1fae5;
            border-left: 4px solid #22c55e;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        code {
            background: #f1f5f9;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>🔍 Teacher Session Diagnostic Tool</h2>
        
        <?php
        require_once 'includes/config.php';
        require_once 'includes/session.php';
        
        echo "<h3>1. Session Status</h3>";
        
        if (session_status() === PHP_SESSION_ACTIVE) {
            echo "<p class='success'>✓ Session is active</p>";
        } else {
            echo "<p class='error'>✗ No active session</p>";
        }
        
        echo "<h3>2. Session Data</h3>";
        if (!empty($_SESSION)) {
            echo "<table>";
            echo "<tr><th>Key</th><th>Value</th></tr>";
            foreach ($_SESSION as $key => $value) {
                if ($key === 'password') continue; // Don't show passwords
                echo "<tr>";
                echo "<td><strong>" . htmlspecialchars($key) . "</strong></td>";
                echo "<td>" . htmlspecialchars(is_array($value) ? json_encode($value) : $value) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>✗ Session is empty - You are not logged in!</p>";
            echo "<div class='alert-box'>";
            echo "<strong>Problem:</strong> You need to be logged in as a teacher to create classes.<br>";
            echo "<strong>Solution:</strong> Please log in first.";
            echo "</div>";
        }
        
        echo "<h3>3. Teacher Verification</h3>";
        
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            echo "<p>Session User ID: <code>$user_id</code></p>";
            
            try {
                // Check if user exists in database
                $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    echo "<div class='success-box'>";
                    echo "<p class='success'>✓ User found in database</p>";
                    echo "</div>";
                    
                    echo "<table>";
                    echo "<tr><th>Field</th><th>Value</th></tr>";
                    echo "<tr><td><strong>User ID</strong></td><td>" . htmlspecialchars($user['user_id']) . "</td></tr>";
                    echo "<tr><td><strong>Full Name</strong></td><td>" . htmlspecialchars($user['full_name']) . "</td></tr>";
                    echo "<tr><td><strong>Email</strong></td><td>" . htmlspecialchars($user['email']) . "</td></tr>";
                    echo "<tr><td><strong>Role</strong></td><td><strong>" . htmlspecialchars($user['role']) . "</strong></td></tr>";
                    echo "<tr><td><strong>Status</strong></td><td>" . htmlspecialchars($user['status']) . "</td></tr>";
                    echo "</table>";
                    
                    // Check if role is teacher
                    if ($user['role'] === 'teacher') {
                        echo "<div class='success-box'>";
                        echo "<p class='success'>✓ User role is TEACHER - You can create classes!</p>";
                        echo "</div>";
                    } else {
                        echo "<div class='alert-box'>";
                        echo "<p class='error'>✗ User role is '" . htmlspecialchars($user['role']) . "' (NOT teacher)</p>";
                        echo "<strong>Problem:</strong> Only users with role='teacher' can create classes.<br>";
                        echo "<strong>Solution:</strong> You need to log in with a teacher account.";
                        echo "</div>";
                    }
                    
                    // Check if user is active
                    if ($user['status'] !== 'active') {
                        echo "<div class='alert-box'>";
                        echo "<p class='warning'>⚠ User status is '" . htmlspecialchars($user['status']) . "' (not active)</p>";
                        echo "</div>";
                    }
                    
                } else {
                    echo "<div class='alert-box'>";
                    echo "<p class='error'>✗ User ID $user_id does NOT exist in the database!</p>";
                    echo "<strong>Problem:</strong> The session has a user_id that doesn't exist in the users table.<br>";
                    echo "<strong>Solution:</strong> Please log out and log in again.";
                    echo "</div>";
                }
                
            } catch (PDOException $e) {
                echo "<p class='error'>✗ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            
        } else {
            echo "<div class='alert-box'>";
            echo "<p class='error'>✗ No user_id in session</p>";
            echo "<strong>Problem:</strong> You are not logged in.<br>";
            echo "<strong>Solution:</strong> Please log in as a teacher.";
            echo "</div>";
        }
        
        echo "<h3>4. All Teachers in Database</h3>";
        
        try {
            $stmt = $conn->prepare("SELECT user_id, full_name, email, role, status FROM users WHERE role = 'teacher' ORDER BY user_id");
            $stmt->execute();
            $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($teachers) > 0) {
                echo "<p>Found <strong>" . count($teachers) . "</strong> teacher(s) in database:</p>";
                echo "<table>";
                echo "<tr><th>User ID</th><th>Full Name</th><th>Email</th><th>Status</th></tr>";
                foreach ($teachers as $teacher) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($teacher['user_id']) . "</td>";
                    echo "<td>" . htmlspecialchars($teacher['full_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($teacher['email']) . "</td>";
                    echo "<td>" . htmlspecialchars($teacher['status']) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<div class='alert-box'>";
                echo "<p class='warning'>⚠ No teachers found in database!</p>";
                echo "<strong>Problem:</strong> There are no users with role='teacher' in the users table.<br>";
                echo "<strong>Solution:</strong> You need to create a teacher account first.";
                echo "</div>";
            }
            
        } catch (PDOException $e) {
            echo "<p class='error'>✗ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
        echo "<h3>5. Summary & Next Steps</h3>";
        
        if (isset($_SESSION['user_id']) && isset($user) && $user['role'] === 'teacher') {
            echo "<div class='success-box'>";
            echo "<p class='success'><strong>✓ Everything looks good! You should be able to create classes.</strong></p>";
            echo "<p>If you're still getting errors, please check:</p>";
            echo "<ul>";
            echo "<li>Make sure <code>generateClassCode()</code> function exists in <code>includes/functions.php</code></li>";
            echo "<li>Check PHP error logs for more details</li>";
            echo "</ul>";
            echo "</div>";
        } else {
            echo "<div class='alert-box'>";
            echo "<p class='error'><strong>✗ Issues detected:</strong></p>";
            echo "<ul>";
            if (!isset($_SESSION['user_id'])) {
                echo "<li>You are not logged in</li>";
            }
            if (isset($user) && $user['role'] !== 'teacher') {
                echo "<li>You are logged in as '" . htmlspecialchars($user['role']) . "' but need to be a 'teacher'</li>";
            }
            if (isset($user) === false && isset($_SESSION['user_id'])) {
                echo "<li>Your user account doesn't exist in the database</li>";
            }
            echo "</ul>";
            echo "<p><strong>Action Required:</strong> Log in with a valid teacher account.</p>";
            echo "</div>";
        }
        
        ?>
        
        <div class="info-box" style="margin-top: 30px;">
            <strong>📝 Note:</strong> This is a diagnostic tool. After fixing any issues, delete this file for security.
        </div>
    </div>
</body>
</html>