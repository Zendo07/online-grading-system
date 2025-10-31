<?php
/**
 * Setup Uploads Directory
 * Run this file once: http://localhost/online-grading-system/setup-uploads.php
 * Then DELETE this file after running
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Setup Uploads Directory</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 { color: #7b2d26; }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        .warning { color: #f59e0b; }
        .info { color: #3b82f6; }
        .code {
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        hr { margin: 20px 0; }
    </style>
</head>
<body>
    <div class='container'>";

echo "<h2>Setting up uploads directory...</h2>";

// Create uploads directory structure
$directories = [
    'uploads',
    'uploads/profiles'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "<p class='success'>✅ Created: <span class='code'>$dir</span></p>";
        } else {
            echo "<p class='error'>❌ Failed to create: <span class='code'>$dir</span></p>";
        }
    } else {
        echo "<p class='info'>ℹ️  Already exists: <span class='code'>$dir</span></p>";
    }
    
    // Check if writable
    if (is_writable($dir)) {
        echo "<p class='success'>✅ Writable: <span class='code'>$dir</span></p>";
    } else {
        echo "<p class='warning'>⚠️  Not writable: <span class='code'>$dir</span> (Attempting to fix...)</p>";
        // Try to make it writable
        if (@chmod($dir, 0755)) {
            echo "<p class='success'>✅ Fixed permissions for: <span class='code'>$dir</span></p>";
        } else {
            echo "<p class='error'>❌ Could not fix permissions. Run manually: <span class='code'>chmod 755 $dir</span></p>";
        }
    }
}

// Create .htaccess for uploads directory
$htaccess_content = '# Uploads Directory Security Configuration

# Allow access to image files only
<FilesMatch "\.(jpg|jpeg|png|gif)$">
    Require all granted
</FilesMatch>

# Deny access to PHP files for security
<FilesMatch "\.php$">
    Require all denied
</FilesMatch>

# Prevent directory listing
Options -Indexes

# Set proper MIME types
<IfModule mod_mime.c>
    AddType image/jpeg .jpg .jpeg
    AddType image/png .png
    AddType image/gif .gif
</IfModule>

# Enable caching for images
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/gif "access plus 1 month"
</IfModule>';

$htaccess_file = 'uploads/.htaccess';
if (file_put_contents($htaccess_file, $htaccess_content)) {
    echo "<p class='success'>✅ Created: <span class='code'>$htaccess_file</span></p>";
} else {
    echo "<p class='error'>❌ Failed to create: <span class='code'>$htaccess_file</span></p>";
}

// Create test file to verify upload works
$test_file = 'uploads/profiles/test.txt';
if (file_put_contents($test_file, 'Test file - ' . date('Y-m-d H:i:s'))) {
    echo "<p class='success'>✅ Can write to <span class='code'>uploads/profiles/</span></p>";
    @unlink($test_file); // Delete test file
    echo "<p class='success'>✅ Test file cleaned up</p>";
} else {
    echo "<p class='error'>❌ Cannot write to <span class='code'>uploads/profiles/</span></p>";
}

// Check PHP upload settings
echo "<hr>";
echo "<h3>PHP Upload Settings:</h3>";
echo "<p><strong>upload_max_filesize:</strong> " . ini_get('upload_max_filesize') . "</p>";
echo "<p><strong>post_max_size:</strong> " . ini_get('post_max_size') . "</p>";
echo "<p><strong>file_uploads:</strong> " . (ini_get('file_uploads') ? '<span class="success">Enabled</span>' : '<span class="error">Disabled</span>') . "</p>";
echo "<p><strong>max_file_uploads:</strong> " . ini_get('max_file_uploads') . "</p>";

// Show current permissions
echo "<hr>";
echo "<h3>Directory Permissions:</h3>";
echo "<table style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'><th style='padding: 10px; text-align: left;'>Directory</th><th style='padding: 10px; text-align: left;'>Permissions</th><th style='padding: 10px; text-align: left;'>Status</th></tr>";

foreach ($directories as $dir) {
    if (file_exists($dir)) {
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        $is_writable = is_writable($dir);
        $status_class = $is_writable ? 'success' : 'error';
        $status_text = $is_writable ? '✅ OK' : '❌ Not Writable';
        
        echo "<tr>";
        echo "<td style='padding: 10px; border-top: 1px solid #ddd;'><span class='code'>$dir</span></td>";
        echo "<td style='padding: 10px; border-top: 1px solid #ddd;'><span class='code'>$perms</span></td>";
        echo "<td style='padding: 10px; border-top: 1px solid #ddd;'><span class='$status_class'>$status_text</span></td>";
        echo "</tr>";
    }
}
echo "</table>";

echo "<hr>";
echo "<h3 class='success'>✅ Setup Complete!</h3>";
echo "<p><strong class='warning'>⚠️  IMPORTANT:</strong> Delete this file (<span class='code'>setup-uploads.php</span>) after running it for security.</p>";
echo "<p>Now you can:</p>";
echo "<ul>";
echo "<li>Upload profile pictures at: <a href='student/profile.php'>student/profile.php</a></li>";
echo "<li>Test the upload functionality</li>";
echo "<li>Check if images display correctly</li>";
echo "</ul>";

echo "</div></body></html>";
?>