<?php
/**
 * PHPMailer Location Checker
 * Run this to find where PHPMailer actually is
 * Access: http://localhost/online-grading-system/check-phpmailer.php
 */

$project_root = __DIR__;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHPMailer Location Checker</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #7b2d26; }
        .found { color: #10b981; font-weight: bold; }
        .not-found { color: #ef4444; }
        .path { background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0; font-family: monospace; word-break: break-all; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        th { background: #f8f9fa; font-weight: bold; }
        .section { margin: 30px 0; padding: 20px; background: #f8f9fa; border-radius: 8px; }
        .success { background: #d1fae5; border: 2px solid #10b981; padding: 15px; border-radius: 8px; }
        .error { background: #fee2e2; border: 2px solid #ef4444; padding: 15px; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 PHPMailer Location Checker</h1>
        <p>This script will scan your project and find PHPMailer files.</p>

        <div class="section">
            <h2>📂 Project Root:</h2>
            <div class="path"><?php echo $project_root; ?></div>
        </div>

        <div class="section">
            <h2>🔎 Scanning for PHPMailer...</h2>
            
            <?php
            // Paths to check
            $paths_to_check = [
                'PHPMailer-6.9.1/src/PHPMailer.php',
                'PHPMailer-6.9.1/PHPMailer.php',
                'PHPMailer/src/PHPMailer.php',
                'PHPMailer/PHPMailer.php',
                'vendor/phpmailer/phpmailer/src/PHPMailer.php',
                'src/PHPMailer.php',
            ];

            echo '<table>';
            echo '<tr><th>Status</th><th>Path</th></tr>';
            
            $found_path = null;
            foreach ($paths_to_check as $path) {
                $full_path = $project_root . '/' . $path;
                $exists = file_exists($full_path);
                
                if ($exists && !$found_path) {
                    $found_path = $full_path;
                }
                
                $status = $exists ? '<span class="found">✅ FOUND</span>' : '<span class="not-found">❌ Not found</span>';
                echo "<tr><td>$status</td><td><code>$full_path</code></td></tr>";
            }
            echo '</table>';
            ?>
        </div>

        <div class="section">
            <h2>📁 Directory Contents Scan</h2>
            <p>Folders in your project root:</p>
            <?php
            $dirs = array_filter(glob($project_root . '/*'), 'is_dir');
            echo '<ul>';
            foreach ($dirs as $dir) {
                $dirname = basename($dir);
                echo "<li><strong>$dirname/</strong>";
                
                // Check if it contains PHPMailer files
                if (file_exists($dir . '/src/PHPMailer.php')) {
                    echo ' <span class="found">← Contains PHPMailer!</span>';
                } elseif (file_exists($dir . '/PHPMailer.php')) {
                    echo ' <span class="found">← Contains PHPMailer (root level)!</span>';
                } elseif (stripos($dirname, 'phpmailer') !== false) {
                    echo ' <span style="color: #f59e0b;">← PHPMailer folder (checking contents...)</span>';
                    // List contents
                    $contents = scandir($dir);
                    echo '<ul>';
                    foreach ($contents as $item) {
                        if ($item != '.' && $item != '..') {
                            echo "<li>$item</li>";
                        }
                    }
                    echo '</ul>';
                }
                
                echo '</li>';
            }
            echo '</ul>';
            ?>
        </div>

        <?php if ($found_path): ?>
        <div class="success">
            <h2>✅ PHPMailer Found!</h2>
            <p><strong>Location:</strong></p>
            <div class="path"><?php echo $found_path; ?></div>
            
            <h3>🔧 Fix Instructions:</h3>
            <p>Update <code>includes/email-config.php</code> line ~10 to:</p>
            <pre style="background: #1f2937; color: #f9fafb; padding: 15px; border-radius: 5px; overflow-x: auto;">$phpmailer_paths = [
    __DIR__ . '/../<?php echo str_replace($project_root . '/', '', dirname($found_path)); ?>/PHPMailer.php',
    __DIR__ . '/../PHPMailer/src/PHPMailer.php',
    __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php'
];</pre>
            
            <p style="margin-top: 20px;">Or simply copy the PHPMailer files to match the expected path:</p>
            <div class="path"><?php echo $project_root; ?>/PHPMailer/src/</div>
        </div>
        <?php else: ?>
        <div class="error">
            <h2>❌ PHPMailer Not Found Anywhere</h2>
            <p><strong>Please do one of the following:</strong></p>
            <ol style="line-height: 1.8;">
                <li>Extract the PHPMailer-6.9.1.zip file you downloaded</li>
                <li>Look for a folder named <strong>PHPMailer-6.9.1</strong> or similar</li>
                <li>Copy that ENTIRE folder to: <code><?php echo $project_root; ?>/</code></li>
                <li>Refresh this page</li>
            </ol>
            
            <p style="margin-top: 20px;"><strong>Expected structure after copying:</strong></p>
            <pre style="background: #f8f9fa; padding: 15px; border-radius: 5px;">
<?php echo $project_root; ?>/
├── PHPMailer-6.9.1/
│   └── src/
│       ├── PHPMailer.php
│       ├── SMTP.php
│       └── Exception.php
├── includes/
├── api/
└── ...
            </pre>
        </div>
        <?php endif; ?>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #e0e0e0; text-align: center;">
            <p><a href="install-phpmailer.php" style="color: #7b2d26; font-weight: bold;">← Try Auto-Installer</a> | 
            <a href="auth/student-register.php" style="color: #7b2d26; font-weight: bold;">Test Registration →</a></p>
        </div>
    </div>
</body>
</html>