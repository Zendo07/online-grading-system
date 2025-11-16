<?php
// Create this file: teacher/debug-schedule.php
// Access it via: your-site.com/teacher/debug-schedule.php

require_once '../includes/config.php';
require_once '../includes/session.php';

requireTeacher();

$teacher_id = $_SESSION['user_id'];
$today = date('l'); // Full day name
$today_num = date('N'); // 1-7
$today_w = date('w'); // 0-6

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Debug - indEx</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
            background: #f5f5f5;
        }
        .debug-section {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #7b2d26;
            border-bottom: 3px solid #7b2d26;
            padding-bottom: 12px;
        }
        h2 {
            color: #333;
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .badge {
            background: #7b2d26;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: bold;
        }
        .badge.success { background: #28a745; }
        .badge.warning { background: #ffc107; color: #333; }
        .badge.danger { background: #dc3545; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }
        .info-item {
            background: #f8f9fa;
            padding: 16px;
            border-radius: 8px;
            border-left: 4px solid #7b2d26;
        }
        .info-label {
            font-size: 12px;
            color: #666;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .info-value {
            font-size: 20px;
            color: #333;
            font-weight: bold;
        }
        pre {
            background: #f8f9fa;
            padding: 16px;
            border-radius: 8px;
            overflow-x: auto;
            border-left: 4px solid #17a2b8;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 16px;
            border-radius: 8px;
            border-left: 4px solid #dc3545;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 12px 16px;
            border-radius: 8px;
            border-left: 4px solid #28a745;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 12px 16px;
            border-radius: 8px;
            border-left: 4px solid #ffc107;
        }
        .sql-box {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 16px;
            border-radius: 8px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
        .back-btn {
            display: inline-block;
            background: #7b2d26;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .back-btn:hover {
            background: #5a1f1a;
        }
    </style>
</head>
<body>
    <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
    
    <h1>🔍 Schedule Debugging Tool</h1>

    <!-- Session Info -->
    <div class="debug-section">
        <h2>📋 Session Information</h2>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Teacher ID</div>
                <div class="info-value"><?php echo $teacher_id; ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Full Name</div>
                <div class="info-value"><?php echo $_SESSION['full_name']; ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Role</div>
                <div class="info-value"><?php echo $_SESSION['role']; ?></div>
            </div>
        </div>
    </div>

    <!-- Date Info -->
    <div class="debug-section">
        <h2>📅 Current Date Information</h2>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Full Date</div>
                <div class="info-value"><?php echo date('Y-m-d H:i:s'); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Day Name (date('l'))</div>
                <div class="info-value"><?php echo $today; ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Day Number N (1-7)</div>
                <div class="info-value"><?php echo $today_num; ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Day Number w (0-6)</div>
                <div class="info-value"><?php echo $today_w; ?></div>
            </div>
        </div>
    </div>

    <!-- Database Connection Check -->
    <div class="debug-section">
        <h2>🔌 Database Connection</h2>
        <?php if (isset($conn) && $conn): ?>
            <div class="success">✅ Database connection is active</div>
        <?php else: ?>
            <div class="error">❌ Database connection failed!</div>
        <?php endif; ?>
    </div>

    <!-- Check Classes -->
    <div class="debug-section">
        <h2>📚 Your Classes</h2>
        <?php
        try {
            $stmt = $conn->prepare("SELECT * FROM classes WHERE teacher_id = ? ORDER BY class_id");
            $stmt->execute([$teacher_id]);
            $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($classes) > 0) {
                echo '<div class="success">✅ Found ' . count($classes) . ' class(es)</div>';
                echo '<table>';
                echo '<tr><th>Class ID</th><th>Class Name</th><th>Subject</th><th>Section</th><th>Status</th></tr>';
                foreach ($classes as $class) {
                    $status_class = $class['status'] == 'active' ? 'success' : 'warning';
                    echo '<tr>';
                    echo '<td>' . $class['class_id'] . '</td>';
                    echo '<td>' . htmlspecialchars($class['class_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($class['subject']) . '</td>';
                    echo '<td>' . htmlspecialchars($class['section']) . '</td>';
                    echo '<td><span class="badge ' . $status_class . '">' . $class['status'] . '</span></td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<div class="warning">⚠️ No classes found for this teacher</div>';
            }
        } catch (PDOException $e) {
            echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
    </div>

    <!-- Check Schedules Table Structure -->
    <div class="debug-section">
        <h2>🗂️ Schedules Table Structure</h2>
        <?php
        try {
            $stmt = $conn->query("DESCRIBE schedules");
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
        } catch (PDOException $e) {
            echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
    </div>

    <!-- Check All Schedules -->
    <div class="debug-section">
        <h2>📋 All Your Schedules (All Days)</h2>
        <?php
        try {
            $stmt = $conn->prepare("
                SELECT 
                    s.*,
                    c.class_name,
                    c.subject,
                    c.section,
                    c.status as class_status
                FROM schedules s
                INNER JOIN classes c ON s.class_id = c.class_id
                WHERE c.teacher_id = ?
                ORDER BY s.day_of_week, s.start_time
            ");
            $stmt->execute([$teacher_id]);
            $all_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($all_schedules) > 0) {
                echo '<div class="success">✅ Found ' . count($all_schedules) . ' schedule(s)</div>';
                echo '<table>';
                echo '<tr><th>Schedule ID</th><th>Class</th><th>Day of Week</th><th>Day Length</th><th>Start Time</th><th>End Time</th><th>Room</th><th>Class Status</th></tr>';
                foreach ($all_schedules as $sched) {
                    echo '<tr>';
                    echo '<td>' . $sched['schedule_id'] . '</td>';
                    echo '<td>' . htmlspecialchars($sched['class_name']) . '</td>';
                    echo '<td><strong>' . htmlspecialchars($sched['day_of_week']) . '</strong> (' . strlen($sched['day_of_week']) . ' chars)</td>';
                    echo '<td>' . strlen($sched['day_of_week']) . '</td>';
                    echo '<td>' . $sched['start_time'] . '</td>';
                    echo '<td>' . $sched['end_time'] . '</td>';
                    echo '<td>' . htmlspecialchars($sched['room'] ?? 'N/A') . '</td>';
                    echo '<td><span class="badge ' . ($sched['class_status'] == 'active' ? 'success' : 'warning') . '">' . $sched['class_status'] . '</span></td>';
                    echo '</tr>';
                }
                echo '</table>';
                
                // Show unique day values
                $unique_days = array_unique(array_column($all_schedules, 'day_of_week'));
                echo '<div style="margin-top: 16px;"><strong>Unique day_of_week values in database:</strong> ' . implode(', ', array_map('htmlspecialchars', $unique_days)) . '</div>';
                
            } else {
                echo '<div class="warning">⚠️ No schedules found for this teacher</div>';
                echo '<p>This is likely the problem! You need to add schedule entries to the schedules table.</p>';
            }
        } catch (PDOException $e) {
            echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
    </div>

    <!-- Check Today's Schedules -->
    <div class="debug-section">
        <h2>🎯 Today's Schedules (<?php echo $today; ?>)</h2>
        <?php
        try {
            // Try exact match first
            $stmt = $conn->prepare("
                SELECT 
                    c.class_name,
                    c.subject,
                    c.section,
                    s.day_of_week,
                    s.start_time,
                    s.end_time,
                    s.room
                FROM schedules s
                INNER JOIN classes c ON s.class_id = c.class_id
                WHERE c.teacher_id = ? 
                AND c.status = 'active' 
                AND s.day_of_week = ?
                ORDER BY s.start_time ASC
            ");
            $stmt->execute([$teacher_id, $today]);
            $today_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<h3>Attempt 1: Exact Match (day_of_week = \'' . $today . '\')</h3>';
            if (count($today_schedules) > 0) {
                echo '<div class="success">✅ Found ' . count($today_schedules) . ' schedule(s) for today</div>';
                echo '<table>';
                echo '<tr><th>Class</th><th>Subject</th><th>Section</th><th>Time</th><th>Room</th></tr>';
                foreach ($today_schedules as $sched) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($sched['class_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($sched['subject']) . '</td>';
                    echo '<td>' . htmlspecialchars($sched['section']) . '</td>';
                    echo '<td>' . $sched['start_time'] . ' - ' . $sched['end_time'] . '</td>';
                    echo '<td>' . htmlspecialchars($sched['room'] ?? 'N/A') . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<div class="error">❌ No schedules found with exact match</div>';
            }
            
            // Try case-insensitive match
            echo '<h3>Attempt 2: Case-Insensitive Match (LOWER(day_of_week) = LOWER(\'' . $today . '\'))</h3>';
            $stmt = $conn->prepare("
                SELECT 
                    c.class_name,
                    c.subject,
                    c.section,
                    s.day_of_week,
                    s.start_time,
                    s.end_time,
                    s.room
                FROM schedules s
                INNER JOIN classes c ON s.class_id = c.class_id
                WHERE c.teacher_id = ? 
                AND c.status = 'active' 
                AND LOWER(s.day_of_week) = LOWER(?)
                ORDER BY s.start_time ASC
            ");
            $stmt->execute([$teacher_id, $today]);
            $today_schedules_ci = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($today_schedules_ci) > 0) {
                echo '<div class="success">✅ Found ' . count($today_schedules_ci) . ' schedule(s) with case-insensitive match</div>';
            } else {
                echo '<div class="error">❌ No schedules found with case-insensitive match</div>';
            }
            
            // Try with TRIM
            echo '<h3>Attempt 3: With TRIM (TRIM(day_of_week) = \'' . $today . '\')</h3>';
            $stmt = $conn->prepare("
                SELECT 
                    c.class_name,
                    c.subject,
                    c.section,
                    s.day_of_week,
                    s.start_time,
                    s.end_time,
                    s.room
                FROM schedules s
                INNER JOIN classes c ON s.class_id = c.class_id
                WHERE c.teacher_id = ? 
                AND c.status = 'active' 
                AND TRIM(s.day_of_week) = ?
                ORDER BY s.start_time ASC
            ");
            $stmt->execute([$teacher_id, $today]);
            $today_schedules_trim = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($today_schedules_trim) > 0) {
                echo '<div class="success">✅ Found ' . count($today_schedules_trim) . ' schedule(s) with TRIM</div>';
            } else {
                echo '<div class="error">❌ No schedules found with TRIM</div>';
            }
            
        } catch (PDOException $e) {
            echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
    </div>

    <!-- Solution Section -->
    <div class="debug-section">
        <h2>💡 Suggested Solutions</h2>
        <?php
        $has_classes = isset($classes) && count($classes) > 0;
        $has_schedules = isset($all_schedules) && count($all_schedules) > 0;
        $has_today = isset($today_schedules) && count($today_schedules) > 0;
        
        if (!$has_classes) {
            echo '<div class="error"><strong>Problem:</strong> You have no classes in the system.</div>';
            echo '<p><strong>Solution:</strong> Create classes first through the Classes page.</p>';
        } elseif (!$has_schedules) {
            echo '<div class="error"><strong>Problem:</strong> You have classes but no schedules.</div>';
            echo '<p><strong>Solution:</strong> Add schedules to your classes. Use the SQL below:</p>';
            echo '<div class="sql-box">';
            echo 'INSERT INTO schedules (class_id, day_of_week, start_time, end_time, room)<br>';
            echo 'VALUES<br>';
            echo '  (' . ($classes[0]['class_id'] ?? 'YOUR_CLASS_ID') . ', \'' . $today . '\', \'08:00:00\', \'09:30:00\', \'Room 101\'),<br>';
            echo '  (' . ($classes[0]['class_id'] ?? 'YOUR_CLASS_ID') . ', \'' . $today . '\', \'10:00:00\', \'11:30:00\', \'Room 101\');';
            echo '</div>';
        } elseif (!$has_today) {
            echo '<div class="warning"><strong>Problem:</strong> You have schedules but none for today (' . $today . ').</div>';
            echo '<p><strong>Possible causes:</strong></p>';
            echo '<ul>';
            echo '<li>Day name mismatch (check spelling and case)</li>';
            echo '<li>Extra whitespace in day_of_week column</li>';
            echo '<li>No schedule created for ' . $today . '</li>';
            echo '</ul>';
            
            if (isset($all_schedules) && count($all_schedules) > 0) {
                echo '<p><strong>Your days in database:</strong> ' . implode(', ', array_unique(array_column($all_schedules, 'day_of_week'))) . '</p>';
                echo '<p><strong>PHP expects:</strong> ' . $today . '</p>';
                
                echo '<p><strong>Quick Fix SQL:</strong></p>';
                echo '<div class="sql-box">';
                echo '-- Update all day names to standard format<br>';
                echo 'UPDATE schedules SET day_of_week = TRIM(day_of_week);<br><br>';
                echo '-- Standardize capitalization<br>';
                echo 'UPDATE schedules SET day_of_week = <br>';
                echo '  CASE LOWER(day_of_week)<br>';
                echo '    WHEN \'monday\' THEN \'Monday\'<br>';
                echo '    WHEN \'tuesday\' THEN \'Tuesday\'<br>';
                echo '    WHEN \'wednesday\' THEN \'Wednesday\'<br>';
                echo '    WHEN \'thursday\' THEN \'Thursday\'<br>';
                echo '    WHEN \'friday\' THEN \'Friday\'<br>';
                echo '    WHEN \'saturday\' THEN \'Saturday\'<br>';
                echo '    WHEN \'sunday\' THEN \'Sunday\'<br>';
                echo '    ELSE day_of_week<br>';
                echo '  END;';
                echo '</div>';
            }
        } else {
            echo '<div class="success"><strong>✅ Everything looks good!</strong> Schedules should be appearing on your dashboard.</div>';
        }
        ?>
    </div>

    <div style="text-align: center; margin-top: 40px;">
        <a href="dashboard.php" class="back-btn">← Return to Dashboard</a>
    </div>
</body>
</html>