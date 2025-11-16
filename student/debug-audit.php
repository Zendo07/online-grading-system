<?php
/**
 * AUDIT TRAIL DEBUGGER
 * This file helps diagnose why audit logs aren't appearing
 */

require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireStudent();
$student_id = $_SESSION['user_id'];
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Trail Debugger</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            background: #1a1a1a;
            color: #0f0;
            padding: 20px;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        h1 {
            color: #0ff;
            margin-bottom: 20px;
            border-bottom: 2px solid #0ff;
            padding-bottom: 10px;
        }
        
        h2 {
            color: #ff0;
            margin: 30px 0 15px 0;
            border-left: 4px solid #ff0;
            padding-left: 10px;
        }
        
        .section {
            background: #2a2a2a;
            border: 1px solid #444;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .success {
            color: #0f0;
        }
        
        .error {
            color: #f00;
            font-weight: bold;
        }
        
        .warning {
            color: #ff0;
        }
        
        .info {
            color: #0ff;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            background: #1a1a1a;
        }
        
        th, td {
            border: 1px solid #444;
            padding: 10px;
            text-align: left;
        }
        
        th {
            background: #333;
            color: #0ff;
        }
        
        .query-box {
            background: #000;
            border: 1px solid #0f0;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            margin: 10px 0;
        }
        
        .count {
            font-size: 24px;
            font-weight: bold;
            color: #ff0;
            margin: 10px 0;
        }
        
        pre {
            white-space: pre-wrap;
            word-break: break-all;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            margin: 2px;
        }
        
        .badge-success {
            background: #0f0;
            color: #000;
        }
        
        .badge-error {
            background: #f00;
            color: #fff;
        }
        
        .badge-warning {
            background: #ff0;
            color: #000;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 AUDIT TRAIL DEBUGGER</h1>
        
        <?php
        // ==================== STEP 1: Check Database Connection ====================
        echo '<div class="section">';
        echo '<h2>Step 1: Database Connection</h2>';
        
        try {
            $conn->query("SELECT 1");
            echo '<p class="success">✓ Database connection: <strong>WORKING</strong></p>';
        } catch (PDOException $e) {
            echo '<p class="error">✗ Database connection: <strong>FAILED</strong></p>';
            echo '<p class="error">Error: ' . $e->getMessage() . '</p>';
            die();
        }
        echo '</div>';
        
        // ==================== STEP 2: Check User Session ====================
        echo '<div class="section">';
        echo '<h2>Step 2: User Session</h2>';
        echo '<p class="info">User ID: <strong>' . $student_id . '</strong></p>';
        echo '<p class="info">Role: <strong>' . ($_SESSION['role'] ?? 'NOT SET') . '</strong></p>';
        echo '<p class="info">Email: <strong>' . ($_SESSION['email'] ?? 'NOT SET') . '</strong></p>';
        echo '</div>';
        
        // ==================== STEP 3: Check audit_logs Table ====================
        echo '<div class="section">';
        echo '<h2>Step 3: Audit Logs Table Structure</h2>';
        
        try {
            $stmt = $conn->query("DESCRIBE audit_logs");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<p class="success">✓ Table exists with ' . count($columns) . ' columns</p>';
            echo '<table>';
            echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>';
            foreach ($columns as $col) {
                echo '<tr>';
                echo '<td>' . $col['Field'] . '</td>';
                echo '<td>' . $col['Type'] . '</td>';
                echo '<td>' . $col['Null'] . '</td>';
                echo '<td>' . $col['Key'] . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } catch (PDOException $e) {
            echo '<p class="error">✗ Error checking table: ' . $e->getMessage() . '</p>';
        }
        echo '</div>';
        
        // ==================== STEP 4: Count Total Logs ====================
        echo '<div class="section">';
        echo '<h2>Step 4: Total Audit Logs Count</h2>';
        
        try {
            // Total logs in database
            $stmt = $conn->query("SELECT COUNT(*) as total FROM audit_logs");
            $totalLogs = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            echo '<p class="count">Total logs in database: ' . $totalLogs . '</p>';
            
            // Logs for current user
            $stmt = $conn->prepare("SELECT COUNT(*) as user_total FROM audit_logs WHERE user_id = ?");
            $stmt->execute([$student_id]);
            $userLogs = $stmt->fetch(PDO::FETCH_ASSOC)['user_total'];
            echo '<p class="count">Logs for user ' . $student_id . ': ' . $userLogs . '</p>';
            
            if ($userLogs == 0) {
                echo '<p class="warning">⚠ WARNING: No logs found for your user ID!</p>';
                echo '<p class="warning">This could mean:</p>';
                echo '<ul>';
                echo '<li>You haven\'t performed any logged actions yet</li>';
                echo '<li>The logging system isn\'t working</li>';
                echo '<li>Your user_id in session doesn\'t match audit_logs</li>';
                echo '</ul>';
            }
        } catch (PDOException $e) {
            echo '<p class="error">✗ Error counting logs: ' . $e->getMessage() . '</p>';
        }
        echo '</div>';
        
        // ==================== STEP 5: Check Action Types ====================
        echo '<div class="section">';
        echo '<h2>Step 5: Action Types Distribution</h2>';
        
        try {
            $stmt = $conn->prepare("
                SELECT action_type, COUNT(*) as count 
                FROM audit_logs 
                WHERE user_id = ? 
                GROUP BY action_type
            ");
            $stmt->execute([$student_id]);
            $actionTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($actionTypes) > 0) {
                echo '<table>';
                echo '<tr><th>Action Type</th><th>Count</th></tr>';
                foreach ($actionTypes as $type) {
                    echo '<tr>';
                    echo '<td>' . $type['action_type'] . '</td>';
                    echo '<td>' . $type['count'] . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<p class="warning">No action types found for your user</p>';
            }
        } catch (PDOException $e) {
            echo '<p class="error">✗ Error: ' . $e->getMessage() . '</p>';
        }
        echo '</div>';
        
        // ==================== STEP 6: Test Main Query ====================
        echo '<div class="section">';
        echo '<h2>Step 6: Main Query Test (WITHOUT Filters)</h2>';
        
        try {
            $query = "
                SELECT 
                    al.*,
                    c.class_name,
                    c.subject,
                    c.section,
                    u.full_name as teacher_name
                FROM audit_logs al
                LEFT JOIN classes c ON JSON_EXTRACT(al.details, '$.class_id') = c.class_id
                LEFT JOIN users u ON c.teacher_id = u.user_id
                WHERE al.user_id = ? 
                ORDER BY al.created_at DESC 
                LIMIT 10
            ";
            
            echo '<div class="query-box">' . htmlspecialchars($query) . '</div>';
            
            $stmt = $conn->prepare($query);
            $stmt->execute([$student_id]);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<p class="count">Found ' . count($logs) . ' logs (unfiltered)</p>';
            
            if (count($logs) > 0) {
                echo '<table>';
                echo '<tr><th>ID</th><th>Action</th><th>Type</th><th>Class</th><th>Created At</th></tr>';
                foreach ($logs as $log) {
                    echo '<tr>';
                    echo '<td>' . $log['log_id'] . '</td>';
                    echo '<td>' . htmlspecialchars($log['action']) . '</td>';
                    echo '<td>' . $log['action_type'] . '</td>';
                    echo '<td>' . ($log['class_name'] ?? 'N/A') . '</td>';
                    echo '<td>' . $log['created_at'] . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
        } catch (PDOException $e) {
            echo '<p class="error">✗ Query Error: ' . $e->getMessage() . '</p>';
        }
        echo '</div>';
        
        // ==================== STEP 7: Test Filtered Query ====================
        echo '<div class="section">';
        echo '<h2>Step 7: Filtered Query Test (WITH Action Type Filter)</h2>';
        
        try {
            $query = "
                SELECT 
                    al.*,
                    c.class_name,
                    c.subject,
                    c.section,
                    u.full_name as teacher_name
                FROM audit_logs al
                LEFT JOIN classes c ON JSON_EXTRACT(al.details, '$.class_id') = c.class_id
                LEFT JOIN users u ON c.teacher_id = u.user_id
                WHERE al.user_id = ? 
                AND al.action_type IN ('login', 'logout', 'view', 'join', 'update', 'create')
                ORDER BY al.created_at DESC 
                LIMIT 10
            ";
            
            echo '<div class="query-box">' . htmlspecialchars($query) . '</div>';
            
            $stmt = $conn->prepare($query);
            $stmt->execute([$student_id]);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<p class="count">Found ' . count($logs) . ' logs (filtered)</p>';
            
            if (count($logs) > 0) {
                echo '<p class="success">✓ Query is working! Logs should appear on the main page.</p>';
                
                echo '<table>';
                echo '<tr><th>ID</th><th>Action</th><th>Type</th><th>Class</th><th>Details</th></tr>';
                foreach ($logs as $log) {
                    echo '<tr>';
                    echo '<td>' . $log['log_id'] . '</td>';
                    echo '<td>' . htmlspecialchars($log['action']) . '</td>';
                    echo '<td><span class="badge badge-success">' . $log['action_type'] . '</span></td>';
                    echo '<td>' . ($log['class_name'] ?? 'N/A') . '</td>';
                    echo '<td><pre>' . htmlspecialchars(substr($log['details'] ?? 'null', 0, 100)) . '...</pre></td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<p class="warning">⚠ No logs match the filter criteria!</p>';
                echo '<p>The filter requires action_type to be one of:</p>';
                echo '<ul>';
                echo '<li>login</li>';
                echo '<li>logout</li>';
                echo '<li>view</li>';
                echo '<li>join</li>';
                echo '<li>update</li>';
                echo '<li>create</li>';
                echo '</ul>';
            }
        } catch (PDOException $e) {
            echo '<p class="error">✗ Query Error: ' . $e->getMessage() . '</p>';
            echo '<p class="error">SQL State: ' . $e->getCode() . '</p>';
        }
        echo '</div>';
        
        // ==================== STEP 8: Check JSON_EXTRACT Compatibility ====================
        echo '<div class="section">';
        echo '<h2>Step 8: JSON_EXTRACT Compatibility</h2>';
        
        try {
            $stmt = $conn->query("SELECT VERSION() as version");
            $version = $stmt->fetch(PDO::FETCH_ASSOC)['version'];
            echo '<p class="info">MySQL/MariaDB Version: ' . $version . '</p>';
            
            // Test JSON_EXTRACT
            $stmt = $conn->query("SELECT JSON_EXTRACT('{\"test\": 123}', '$.test') as result");
            $result = $stmt->fetch(PDO::FETCH_ASSOC)['result'];
            
            if ($result == 123) {
                echo '<p class="success">✓ JSON_EXTRACT is working properly</p>';
            } else {
                echo '<p class="warning">⚠ JSON_EXTRACT returned unexpected result: ' . $result . '</p>';
            }
        } catch (PDOException $e) {
            echo '<p class="error">✗ JSON_EXTRACT test failed: ' . $e->getMessage() . '</p>';
            echo '<p class="warning">Your MySQL version might not support JSON functions</p>';
        }
        echo '</div>';
        
        // ==================== STEP 9: Recent Raw Logs ====================
        echo '<div class="section">';
        echo '<h2>Step 9: Your Most Recent 5 Raw Logs</h2>';
        
        try {
            $stmt = $conn->prepare("
                SELECT * FROM audit_logs 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 5
            ");
            $stmt->execute([$student_id]);
            $rawLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($rawLogs) > 0) {
                foreach ($rawLogs as $log) {
                    echo '<div style="background: #000; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #0f0;">';
                    echo '<pre>' . print_r($log, true) . '</pre>';
                    echo '</div>';
                }
            } else {
                echo '<p class="warning">No logs found</p>';
            }
        } catch (PDOException $e) {
            echo '<p class="error">✗ Error: ' . $e->getMessage() . '</p>';
        }
        echo '</div>';
        
        // ==================== STEP 10: Recommendations ====================
        echo '<div class="section">';
        echo '<h2>Step 10: Diagnostic Summary & Recommendations</h2>';
        
        $issues = [];
        $recommendations = [];
        
        if ($userLogs == 0) {
            $issues[] = "No audit logs found for your user ID";
            $recommendations[] = "Perform some actions (login, join class, etc.) and check again";
            $recommendations[] = "Verify the audit logging system is enabled";
        }
        
        if (count($logs) == 0 && $userLogs > 0) {
            $issues[] = "Logs exist but filtered query returns nothing";
            $recommendations[] = "Check if action_type values in database match the filter";
            $recommendations[] = "Remove or modify the action_type filter in audit-trail.php";
        }
        
        if (count($issues) > 0) {
            echo '<p class="error"><strong>Issues Found:</strong></p><ul>';
            foreach ($issues as $issue) {
                echo '<li>' . $issue . '</li>';
            }
            echo '</ul>';
            
            echo '<p class="warning"><strong>Recommendations:</strong></p><ul>';
            foreach ($recommendations as $rec) {
                echo '<li>' . $rec . '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p class="success">✓ Everything looks good! Your audit trail should be working.</p>';
        }
        
        echo '<hr style="border-color: #444; margin: 20px 0;">';
        echo '<p><strong>Next Steps:</strong></p>';
        echo '<ol>';
        echo '<li>Check the audit-trail.php page</li>';
        echo '<li>If still not working, check browser console for JavaScript errors</li>';
        echo '<li>Verify CSS file is loading correctly</li>';
        echo '<li>Check file permissions on all audit trail files</li>';
        echo '</ol>';
        echo '</div>';
        ?>
        
        <div class="section">
            <h2>Quick Actions</h2>
            <p><a href="audit-trail.php" style="color: #0ff;">→ Go to Audit Trail Page</a></p>
            <p><a href="?refresh=1" style="color: #0ff;">→ Refresh This Debug Page</a></p>
        </div>
    </div>
</body>
</html>