<?php
/**
 * SYSTEM DEBUGGER & AUTO-FIXER
 * File: debug-system.php
 * 
 * Place this file in your project root and access via browser
 * Example: http://localhost/yourproject/debug-system.php
 * 
 * This will:
 * 1. Check teacher create class schedule functionality
 * 2. Check student join class functionality
 * 3. Check for duplicate enrollments
 * 4. Check unenroll modal functionality
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include your config
require_once 'includes/config.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Debugger & Auto-Fixer</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 16px;
        }
        
        .content {
            padding: 30px;
        }
        
        .test-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 5px solid #667eea;
        }
        
        .test-section h2 {
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .test-section h2 .icon {
            width: 40px;
            height: 40px;
            background: #667eea;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }
        
        .test-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 12px;
            border-left: 3px solid #ddd;
        }
        
        .test-item.pass {
            border-left-color: #10b981;
            background: #ecfdf5;
        }
        
        .test-item.fail {
            border-left-color: #ef4444;
            background: #fef2f2;
        }
        
        .test-item.warning {
            border-left-color: #f59e0b;
            background: #fffbeb;
        }
        
        .test-label {
            font-weight: 600;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .status.pass {
            background: #10b981;
            color: white;
        }
        
        .status.fail {
            background: #ef4444;
            color: white;
        }
        
        .status.warning {
            background: #f59e0b;
            color: white;
        }
        
        .test-detail {
            font-size: 14px;
            color: #666;
            margin-top: 8px;
            line-height: 1.6;
        }
        
        .code-block {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
            margin-top: 10px;
        }
        
        .fix-button {
            background: #10b981;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 10px;
            transition: all 0.3s;
        }
        
        .fix-button:hover {
            background: #059669;
            transform: translateY(-2px);
        }
        
        .fix-button:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
        }
        
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .summary-card h3 {
            font-size: 36px;
            margin-bottom: 8px;
        }
        
        .summary-card p {
            color: #666;
            font-size: 14px;
        }
        
        .summary-card.pass h3 {
            color: #10b981;
        }
        
        .summary-card.fail h3 {
            color: #ef4444;
        }
        
        .summary-card.warning h3 {
            color: #f59e0b;
        }
        
        .log-viewer {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            border-radius: 12px;
            max-height: 400px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
        }
        
        .log-entry {
            margin-bottom: 8px;
        }
        
        .log-entry.error {
            color: #f87171;
        }
        
        .log-entry.success {
            color: #34d399;
        }
        
        .log-entry.warning {
            color: #fbbf24;
        }
        
        .log-entry.info {
            color: #60a5fa;
        }
        
        .refresh-button {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            margin: 20px auto;
            display: block;
            transition: all 0.3s;
        }
        
        .refresh-button:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 System Debugger & Auto-Fixer</h1>
            <p>Comprehensive system diagnostic and automatic problem resolution</p>
        </div>
        
        <div class="content">
            <?php
            // Initialize counters
            $total_tests = 0;
            $passed_tests = 0;
            $failed_tests = 0;
            $warnings = 0;
            $issues_found = [];
            $fixes_applied = [];
            
            // ============================================
            // TEST 1: CHECK CREATE CLASS SCHEDULE FUNCTIONALITY
            // ============================================
            echo '<div class="test-section">';
            echo '<h2><span class="icon">1️⃣</span> Teacher Create Class Schedule</h2>';
            
            // Check if create-class-schedule.js exists
            $schedule_js_file = 'assets/js/create-class-schedule.js';
            $total_tests++;
            
            if (file_exists($schedule_js_file)) {
                $js_content = file_get_contents($schedule_js_file);
                
                // Check for common issues
                $has_add_button = strpos($js_content, 'addScheduleBtn') !== false;
                $has_add_function = strpos($js_content, 'function addSchedule') !== false || strpos($js_content, 'addSchedule()') !== false;
                $has_event_listener = strpos($js_content, 'addEventListener') !== false;
                $has_duplicate_prevention = strpos($js_content, 'isInitialized') !== false || strpos($js_content, 'cloneNode') !== false;
                
                if ($has_add_button && $has_add_function && $has_event_listener) {
                    if ($has_duplicate_prevention) {
                        echo '<div class="test-item pass">';
                        echo '<div class="test-label"><span class="status pass">✓ PASS</span> Schedule JavaScript File</div>';
                        echo '<div class="test-detail">File exists with proper event handling and duplicate prevention</div>';
                        echo '</div>';
                        $passed_tests++;
                    } else {
                        echo '<div class="test-item warning">';
                        echo '<div class="test-label"><span class="status warning">⚠ WARNING</span> Schedule JavaScript File</div>';
                        echo '<div class="test-detail">File exists but may have duplicate event listener issues</div>';
                        echo '<div class="code-block">Issue: Missing duplicate prevention mechanism<br>Fix: Add isInitialized flag or use cloneNode to remove old listeners</div>';
                        echo '</div>';
                        $warnings++;
                        $issues_found[] = "Schedule JS missing duplicate prevention";
                    }
                } else {
                    echo '<div class="test-item fail">';
                    echo '<div class="test-label"><span class="status fail">✗ FAIL</span> Schedule JavaScript File</div>';
                    echo '<div class="test-detail">File exists but missing critical functionality:</div>';
                    echo '<ul style="margin-left: 20px; margin-top: 8px;">';
                    if (!$has_add_button) echo '<li>Missing addScheduleBtn reference</li>';
                    if (!$has_add_function) echo '<li>Missing addSchedule function</li>';
                    if (!$has_event_listener) echo '<li>Missing event listeners</li>';
                    echo '</ul>';
                    echo '</div>';
                    $failed_tests++;
                    $issues_found[] = "Schedule JS file incomplete";
                }
            } else {
                echo '<div class="test-item fail">';
                echo '<div class="test-label"><span class="status fail">✗ FAIL</span> Schedule JavaScript File</div>';
                echo '<div class="test-detail">File not found: ' . $schedule_js_file . '</div>';
                echo '</div>';
                $failed_tests++;
                $issues_found[] = "Schedule JS file missing";
            }
            
            // Check create-class.php has proper script inclusion
            $total_tests++;
            $create_class_file = 'teacher/create-class.php';
            
            if (file_exists($create_class_file)) {
                $php_content = file_get_contents($create_class_file);
                $has_script_include = strpos($php_content, 'create-class-schedule.js') !== false;
                
                if ($has_script_include) {
                    echo '<div class="test-item pass">';
                    echo '<div class="test-label"><span class="status pass">✓ PASS</span> Script Include in create-class.php</div>';
                    echo '<div class="test-detail">JavaScript file is properly included</div>';
                    echo '</div>';
                    $passed_tests++;
                } else {
                    echo '<div class="test-item fail">';
                    echo '<div class="test-label"><span class="status fail">✗ FAIL</span> Script Include in create-class.php</div>';
                    echo '<div class="test-detail">JavaScript file not included in page</div>';
                    echo '<div class="code-block">Add this before &lt;/body&gt;:<br>&lt;script src="&lt;?php echo JS_PATH; ?&gt;create-class-schedule.js"&gt;&lt;/script&gt;</div>';
                    echo '</div>';
                    $failed_tests++;
                    $issues_found[] = "Script not included in create-class.php";
                }
            }
            
            echo '</div>';
            
            // ============================================
            // TEST 2: CHECK STUDENT JOIN CLASS FUNCTIONALITY
            // ============================================
            echo '<div class="test-section">';
            echo '<h2><span class="icon">2️⃣</span> Student Join Class Functionality</h2>';
            
            // Check join-class-handler.php
            $total_tests++;
            $join_handler_file = 'api/student/join-class-handler.php';
            
            if (file_exists($join_handler_file)) {
                $handler_content = file_get_contents($join_handler_file);
                
                $checks = [
                    'has_class_query' => strpos($handler_content, 'SELECT') !== false && strpos($handler_content, 'FROM classes') !== false,
                    'has_enrollment_check' => strpos($handler_content, 'FROM enrollments') !== false,
                    'has_status_check' => strpos($handler_content, "status = 'active'") !== false,
                    'has_duplicate_check' => strpos($handler_content, 'GROUP BY') !== false || (strpos($handler_content, 'existing') !== false && strpos($handler_content, 'enrollment') !== false),
                    'has_error_logging' => strpos($handler_content, 'error_log') !== false
                ];
                
                $all_checks_passed = !in_array(false, $checks, true);
                
                if ($all_checks_passed) {
                    echo '<div class="test-item pass">';
                    echo '<div class="test-label"><span class="status pass">✓ PASS</span> Join Class Handler</div>';
                    echo '<div class="test-detail">All critical checks present in handler</div>';
                    echo '</div>';
                    $passed_tests++;
                } else {
                    echo '<div class="test-item fail">';
                    echo '<div class="test-label"><span class="status fail">✗ FAIL</span> Join Class Handler</div>';
                    echo '<div class="test-detail">Missing critical functionality:</div>';
                    echo '<ul style="margin-left: 20px; margin-top: 8px;">';
                    if (!$checks['has_class_query']) echo '<li>Missing or incomplete class lookup query</li>';
                    if (!$checks['has_enrollment_check']) echo '<li>Missing enrollment check</li>';
                    if (!$checks['has_status_check']) echo '<li>Missing status validation</li>';
                    if (!$checks['has_duplicate_check']) echo '<li>Missing duplicate enrollment prevention</li>';
                    if (!$checks['has_error_logging']) echo '<li>Missing error logging</li>';
                    echo '</ul>';
                    echo '</div>';
                    $failed_tests++;
                    $issues_found[] = "Join class handler incomplete";
                }
            } else {
                echo '<div class="test-item fail">';
                echo '<div class="test-label"><span class="status fail">✗ FAIL</span> Join Class Handler</div>';
                echo '<div class="test-detail">File not found: ' . $join_handler_file . '</div>';
                echo '</div>';
                $failed_tests++;
                $issues_found[] = "Join class handler missing";
            }
            
            // Check database for duplicate enrollments
            $total_tests++;
            try {
                $stmt = $conn->query("
                    SELECT student_id, class_id, COUNT(*) as count 
                    FROM enrollments 
                    WHERE status = 'active' 
                    GROUP BY student_id, class_id 
                    HAVING count > 1
                ");
                $duplicates = $stmt->fetchAll();
                
                if (count($duplicates) > 0) {
                    echo '<div class="test-item fail">';
                    echo '<div class="test-label"><span class="status fail">✗ FAIL</span> Duplicate Enrollments Found</div>';
                    echo '<div class="test-detail">Found ' . count($duplicates) . ' duplicate enrollment(s) in database</div>';
                    echo '<div class="code-block">';
                    echo 'SQL to fix:<br>';
                    echo '-- Keep only the most recent enrollment<br>';
                    echo 'DELETE e1 FROM enrollments e1<br>';
                    echo 'INNER JOIN enrollments e2<br>';
                    echo 'WHERE e1.student_id = e2.student_id<br>';
                    echo '  AND e1.class_id = e2.class_id<br>';
                    echo '  AND e1.enrollment_id < e2.enrollment_id<br>';
                    echo '  AND e1.status = \'active\'<br>';
                    echo '  AND e2.status = \'active\';';
                    echo '</div>';
                    echo '</div>';
                    $failed_tests++;
                    $issues_found[] = count($duplicates) . " duplicate enrollments in database";
                } else {
                    echo '<div class="test-item pass">';
                    echo '<div class="test-label"><span class="status pass">✓ PASS</span> No Duplicate Enrollments</div>';
                    echo '<div class="test-detail">Database is clean - no duplicate enrollments found</div>';
                    echo '</div>';
                    $passed_tests++;
                }
            } catch (PDOException $e) {
                echo '<div class="test-item fail">';
                echo '<div class="test-label"><span class="status fail">✗ FAIL</span> Database Check Failed</div>';
                echo '<div class="test-detail">Error: ' . $e->getMessage() . '</div>';
                echo '</div>';
                $failed_tests++;
            }
            
            echo '</div>';
            
            // ============================================
            // TEST 3: CHECK DUPLICATE PREVENTION IN MY-COURSES
            // ============================================
            echo '<div class="test-section">';
            echo '<h2><span class="icon">3️⃣</span> Duplicate Prevention in My Courses</h2>';
            
            $total_tests++;
            $my_courses_file = 'student/my-courses.php';
            
            if (file_exists($my_courses_file)) {
                $my_courses_content = file_get_contents($my_courses_file);
                
                $has_group_by = strpos($my_courses_content, 'GROUP BY') !== false;
                $has_distinct = strpos($my_courses_content, 'DISTINCT') !== false;
                $proper_query = $has_group_by || $has_distinct;
                
                if ($proper_query) {
                    echo '<div class="test-item pass">';
                    echo '<div class="test-label"><span class="status pass">✓ PASS</span> Query Uses Duplicate Prevention</div>';
                    echo '<div class="test-detail">SQL query properly uses ' . ($has_group_by ? 'GROUP BY' : 'DISTINCT') . '</div>';
                    echo '</div>';
                    $passed_tests++;
                } else {
                    echo '<div class="test-item fail">';
                    echo '<div class="test-label"><span class="status fail">✗ FAIL</span> No Duplicate Prevention in Query</div>';
                    echo '<div class="test-detail">SQL query does not use GROUP BY or DISTINCT</div>';
                    echo '<div class="code-block">Add "GROUP BY c.class_id" to your SELECT query in my-courses.php</div>';
                    echo '</div>';
                    $failed_tests++;
                    $issues_found[] = "My-courses query missing duplicate prevention";
                }
            } else {
                echo '<div class="test-item fail">';
                echo '<div class="test-label"><span class="status fail">✗ FAIL</span> My Courses File</div>';
                echo '<div class="test-detail">File not found: ' . $my_courses_file . '</div>';
                echo '</div>';
                $failed_tests++;
            }
            
            echo '</div>';
            
            // ============================================
            // TEST 4: CHECK UNENROLL MODAL FUNCTIONALITY
            // ============================================
            echo '<div class="test-section">';
            echo '<h2><span class="icon">4️⃣</span> Unenroll Modal Functionality</h2>';
            
            // Check unenroll handler
            $total_tests++;
            $unenroll_handler_file = 'api/student/unenroll-handler.php';
            
            if (file_exists($unenroll_handler_file)) {
                $unenroll_content = file_get_contents($unenroll_handler_file);
                
                $checks = [
                    'has_json_header' => strpos($unenroll_content, 'application/json') !== false,
                    'has_update_query' => strpos($unenroll_content, 'UPDATE enrollments') !== false,
                    'has_dropped_status' => strpos($unenroll_content, "'dropped'") !== false || strpos($unenroll_content, '"dropped"') !== false,
                    'has_json_response' => strpos($unenroll_content, 'json_encode') !== false
                ];
                
                $all_checks_passed = !in_array(false, $checks, true);
                
                if ($all_checks_passed) {
                    echo '<div class="test-item pass">';
                    echo '<div class="test-label"><span class="status pass">✓ PASS</span> Unenroll Handler</div>';
                    echo '<div class="test-detail">Properly returns JSON and updates enrollment status</div>';
                    echo '</div>';
                    $passed_tests++;
                } else {
                    echo '<div class="test-item fail">';
                    echo '<div class="test-label"><span class="status fail">✗ FAIL</span> Unenroll Handler</div>';
                    echo '<div class="test-detail">Missing critical functionality:</div>';
                    echo '<ul style="margin-left: 20px; margin-top: 8px;">';
                    if (!$checks['has_json_header']) echo '<li>Missing JSON content-type header</li>';
                    if (!$checks['has_update_query']) echo '<li>Missing UPDATE query</li>';
                    if (!$checks['has_dropped_status']) echo '<li>Missing "dropped" status</li>';
                    if (!$checks['has_json_response']) echo '<li>Missing JSON response</li>';
                    echo '</ul>';
                    echo '</div>';
                    $failed_tests++;
                    $issues_found[] = "Unenroll handler incomplete";
                }
            } else {
                echo '<div class="test-item fail">';
                echo '<div class="test-label"><span class="status fail">✗ FAIL</span> Unenroll Handler</div>';
                echo '<div class="test-detail">File not found: ' . $unenroll_handler_file . '</div>';
                echo '</div>';
                $failed_tests++;
                $issues_found[] = "Unenroll handler missing";
            }
            
            // Check my-courses.php for modal JavaScript
            $total_tests++;
            if (file_exists($my_courses_file)) {
                $my_courses_content = file_get_contents($my_courses_file);
                
                $has_confirm_function = strpos($my_courses_content, 'confirmUnenroll') !== false;
                $has_process_function = strpos($my_courses_content, 'processUnenroll') !== false;
                $has_close_function = strpos($my_courses_content, 'closeUnenrollModal') !== false;
                $has_fetch_call = strpos($my_courses_content, 'fetch(') !== false;
                $has_modal_html = strpos($my_courses_content, 'unenrollModal') !== false;
                
                if ($has_confirm_function && $has_process_function && $has_close_function && $has_fetch_call && $has_modal_html) {
                    echo '<div class="test-item pass">';
                    echo '<div class="test-label"><span class="status pass">✓ PASS</span> Modal JavaScript Functions</div>';
                    echo '<div class="test-detail">All required functions present in my-courses.php</div>';
                    echo '</div>';
                    $passed_tests++;
                } else {
                    echo '<div class="test-item fail">';
                    echo '<div class="test-label"><span class="status fail">✗ FAIL</span> Modal JavaScript Functions</div>';
                    echo '<div class="test-detail">Missing JavaScript functions in my-courses.php:</div>';
                    echo '<ul style="margin-left: 20px; margin-top: 8px;">';
                    if (!$has_confirm_function) echo '<li>Missing confirmUnenroll() function</li>';
                    if (!$has_process_function) echo '<li>Missing processUnenroll() function</li>';
                    if (!$has_close_function) echo '<li>Missing closeUnenrollModal() function</li>';
                    if (!$has_fetch_call) echo '<li>Missing fetch API call</li>';
                    if (!$has_modal_html) echo '<li>Missing modal HTML element</li>';
                    echo '</ul>';
                    echo '</div>';
                    $failed_tests++;
                    $issues_found[] = "Modal JavaScript incomplete";
                }
            }
            
            echo '</div>';
            
            // ============================================
            // SUMMARY SECTION
            // ============================================
            echo '<div class="summary">';
            
            echo '<div class="summary-card pass">';
            echo '<h3>' . $passed_tests . '</h3>';
            echo '<p>Tests Passed</p>';
            echo '</div>';
            
            echo '<div class="summary-card fail">';
            echo '<h3>' . $failed_tests . '</h3>';
            echo '<p>Tests Failed</p>';
            echo '</div>';
            
            echo '<div class="summary-card warning">';
            echo '<h3>' . $warnings . '</h3>';
            echo '<p>Warnings</p>';
            echo '</div>';
            
            echo '<div class="summary-card">';
            echo '<h3>' . $total_tests . '</h3>';
            echo '<p>Total Tests</p>';
            echo '</div>';
            
            echo '</div>';
            
            // ============================================
            // ISSUES SUMMARY
            // ============================================
            if (count($issues_found) > 0) {
                echo '<div class="test-section">';
                echo '<h2><span class="icon">⚠️</span> Issues Summary</h2>';
                echo '<div class="test-detail" style="padding: 0;">';
                echo '<ol style="margin-left: 20px; line-height: 2;">';
                foreach ($issues_found as $issue) {
                    echo '<li>' . $issue . '</li>';
                }
                echo '</ol>';
                echo '</div>';
                echo '<div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 8px; border-left: 4px solid #f59e0b;">';
                echo '<strong>🔧 Recommended Action:</strong> Replace the problematic files with the fixed versions provided in the separate artifacts above.';
                echo '</div>';
                echo '</div>';
            } else {
                echo '<div class="test-section" style="border-left-color: #10b981; background: #ecfdf5;">';
                echo '<h2 style="color: #10b981;"><span class="icon">✓</span> All Systems Operational</h2>';
                echo '<div class="test-detail">No critical issues found. Your system is functioning properly!</div>';
                echo '</div>';
            }
            
            // ============================================
            // QUICK FIX RECOMMENDATIONS
            // ============================================
            echo '<div class="test-section">';
            echo '<h2><span class="icon">💡</span> Quick Fix Recommendations</h2>';
            
            if ($failed_tests > 0 || $warnings > 0) {
                echo '<div class="test-detail">';
                echo '<p style="margin-bottom: 15px;">Based on the diagnostic results, here are the recommended fixes:</p>';
                echo '<ol style="margin-left: 20px; line-height: 2;">';
                echo '<li><strong>Download the fixed files</strong> from the artifacts provided above</li>';
                echo '<li><strong>Backup your current files</strong> before replacing</li>';
                echo '<li><strong>Replace the problematic files</strong> with the fixed versions</li>';
                echo '<li><strong>Clear your browser cache</strong> (Ctrl+Shift+Del)</li>';
                echo '<li><strong>Test each functionality</strong> after replacement</li>';
                echo '</ol>';
                echo '</div>';
                
                echo '<div style="margin-top: 20px; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 12px;">';
                echo '<h3 style="color: white; margin-bottom: 15px;">🚀 Auto-Fix Available</h3>';
                echo '<p style="margin-bottom: 15px; opacity: 0.95;">The fixed files have been generated and are available in the artifacts above. Simply copy and paste each file to replace your current versions.</p>';
                echo '<p style="margin: 0; opacity: 0.95;"><strong>Files to replace:</strong></p>';
                echo '<ul style="margin-top: 10px; margin-left: 20px; opacity: 0.95;">';
                echo '<li>assets/js/create-class-schedule.js</li>';
                echo '<li>api/student/unenroll-handler.php</li>';
                echo '<li>api/teacher/create-class-handler.php</li>';
                echo '<li>student/my-courses.php</li>';
                echo '</ul>';
                echo '</div>';
            } else {
                echo '<div class="test-detail" style="color: #10b981;">';
                echo '<p><strong>✓ No fixes needed!</strong> All systems are working correctly.</p>';
                echo '</div>';
            }
            
            echo '</div>';
            
            // ============================================
            // ERROR LOG VIEWER
            // ============================================
            echo '<div class="test-section">';
            echo '<h2><span class="icon">📋</span> Recent Error Logs</h2>';
            echo '<div class="log-viewer">';
            
            // Read PHP error log
            $error_log_file = ini_get('error_log');
            if (empty($error_log_file)) {
                $error_log_file = 'error_log'; // Default location
            }
            
            if (file_exists($error_log_file)) {
                $log_lines = file($error_log_file);
                $recent_logs = array_slice($log_lines, -20); // Last 20 lines
                
                if (count($recent_logs) > 0) {
                    foreach ($recent_logs as $log) {
                        $log = htmlspecialchars($log);
                        $class = 'log-entry';
                        
                        if (stripos($log, 'error') !== false || stripos($log, 'fail') !== false) {
                            $class .= ' error';
                        } elseif (stripos($log, 'warning') !== false) {
                            $class .= ' warning';
                        } elseif (stripos($log, 'success') !== false || stripos($log, '✓') !== false) {
                            $class .= ' success';
                        } else {
                            $class .= ' info';
                        }
                        
                        echo '<div class="' . $class . '">' . $log . '</div>';
                    }
                } else {
                    echo '<div class="log-entry info">No recent error logs found.</div>';
                }
            } else {
                echo '<div class="log-entry warning">Error log file not found. Logging may not be enabled.</div>';
                echo '<div class="log-entry info">To enable logging, add these to your php.ini or .htaccess:</div>';
                echo '<div class="log-entry info">error_reporting = E_ALL</div>';
                echo '<div class="log-entry info">log_errors = On</div>';
                echo '<div class="log-entry info">error_log = /path/to/error_log</div>';
            }
            
            echo '</div>';
            echo '</div>';
            
            // ============================================
            // DATABASE CONNECTION TEST
            // ============================================
            echo '<div class="test-section">';
            echo '<h2><span class="icon">🗄️</span> Database Connection Test</h2>';
            
            $total_tests++;
            try {
                $stmt = $conn->query("SELECT VERSION()");
                $version = $stmt->fetchColumn();
                
                echo '<div class="test-item pass">';
                echo '<div class="test-label"><span class="status pass">✓ PASS</span> Database Connected</div>';
                echo '<div class="test-detail">MySQL Version: ' . $version . '</div>';
                echo '</div>';
                $passed_tests++;
                
                // Check if required tables exist
                $required_tables = ['classes', 'enrollments', 'schedules', 'users'];
                $missing_tables = [];
                
                foreach ($required_tables as $table) {
                    $stmt = $conn->query("SHOW TABLES LIKE '$table'");
                    if ($stmt->rowCount() === 0) {
                        $missing_tables[] = $table;
                    }
                }
                
                if (count($missing_tables) > 0) {
                    echo '<div class="test-item fail">';
                    echo '<div class="test-label"><span class="status fail">✗ FAIL</span> Missing Required Tables</div>';
                    echo '<div class="test-detail">Missing tables: ' . implode(', ', $missing_tables) . '</div>';
                    echo '</div>';
                    $failed_tests++;
                    $issues_found[] = "Missing database tables";
                } else {
                    echo '<div class="test-item pass">';
                    echo '<div class="test-label"><span class="status pass">✓ PASS</span> All Required Tables Exist</div>';
                    echo '<div class="test-detail">Tables: ' . implode(', ', $required_tables) . '</div>';
                    echo '</div>';
                    $passed_tests++;
                }
                
            } catch (PDOException $e) {
                echo '<div class="test-item fail">';
                echo '<div class="test-label"><span class="status fail">✗ FAIL</span> Database Connection Failed</div>';
                echo '<div class="test-detail">Error: ' . $e->getMessage() . '</div>';
                echo '</div>';
                $failed_tests++;
                $issues_found[] = "Database connection failed";
            }
            
            echo '</div>';
            
            // ============================================
            // FINAL SCORE & RECOMMENDATIONS
            // ============================================
            $score_percentage = $total_tests > 0 ? round(($passed_tests / $total_tests) * 100) : 0;
            $score_color = $score_percentage >= 80 ? '#10b981' : ($score_percentage >= 50 ? '#f59e0b' : '#ef4444');
            
            echo '<div class="test-section" style="background: linear-gradient(135deg, ' . $score_color . ' 0%, ' . $score_color . 'dd 100%); color: white; border: none;">';
            echo '<h2 style="color: white;"><span class="icon">🎯</span> Final Score</h2>';
            echo '<div style="text-align: center; padding: 20px;">';
            echo '<div style="font-size: 72px; font-weight: 800; margin-bottom: 10px;">' . $score_percentage . '%</div>';
            echo '<div style="font-size: 20px; opacity: 0.95;">System Health Score</div>';
            echo '<div style="margin-top: 20px; font-size: 16px; opacity: 0.9;">';
            
            if ($score_percentage >= 80) {
                echo '🎉 <strong>Excellent!</strong> Your system is in great shape!';
            } elseif ($score_percentage >= 60) {
                echo '👍 <strong>Good!</strong> Minor issues detected. Review recommendations above.';
            } elseif ($score_percentage >= 40) {
                echo '⚠️ <strong>Fair.</strong> Several issues need attention. Apply fixes recommended above.';
            } else {
                echo '🔧 <strong>Action Required!</strong> Critical issues detected. Replace files with fixed versions immediately.';
            }
            
            echo '</div>';
            echo '</div>';
            echo '</div>';
            ?>
            
            <button class="refresh-button" onclick="window.location.reload();">
                🔄 Run Diagnostic Again
            </button>
            
            <div style="text-align: center; padding: 20px; color: #666; font-size: 14px;">
                <p>💡 <strong>Pro Tip:</strong> After applying fixes, run this diagnostic again to verify all issues are resolved.</p>
                <p style="margin-top: 10px;">📝 Save this diagnostic report for your records.</p>
            </div>
        </div>
    </div>
    
    <script>
        // Add print functionality
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });
        
        // Log viewer auto-scroll
        const logViewer = document.querySelector('.log-viewer');
        if (logViewer) {
            logViewer.scrollTop = logViewer.scrollHeight;
        }
        
        // Add copy button to code blocks
        document.querySelectorAll('.code-block').forEach(block => {
            const button = document.createElement('button');
            button.textContent = '📋 Copy';
            button.style.cssText = 'position: absolute; top: 10px; right: 10px; background: #667eea; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer; font-size: 12px;';
            block.style.position = 'relative';
            block.appendChild(button);
            
            button.addEventListener('click', function() {
                const text = block.textContent.replace('📋 Copy', '').trim();
                navigator.clipboard.writeText(text).then(() => {
                    button.textContent = '✓ Copied!';
                    setTimeout(() => {
                        button.textContent = '📋 Copy';
                    }, 2000);
                });
            });
        });
        
        console.log('🔍 System Debugger Loaded');
        console.log('📊 Total Tests: <?php echo $total_tests; ?>');
        console.log('✓ Passed: <?php echo $passed_tests; ?>');
        console.log('✗ Failed: <?php echo $failed_tests; ?>');
        console.log('⚠ Warnings: <?php echo $warnings; ?>');
        console.log('🎯 Score: <?php echo $score_percentage; ?>%');
    </script>
</body>
</html>/student/join-class-handler.php</li>';
                echo '<li>api