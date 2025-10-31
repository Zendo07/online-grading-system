<?php
/**
 * Setup Uploads Directory
 * Run this file once: http://localhost/online-grading-system/setup-uploads.php
 * Then DELETE this file after running
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Setting up uploads directory...</h2>";

// Create uploads directory structure
$directories = [
    'uploads',
    'uploads/profiles'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "✅ Created: $dir<br>";
        } else {
            echo "❌ Failed to create: $dir<br>";
        }
    } else {
        echo "✅ Already exists: $dir<br>";
    }
    
    // Check if writable
    if (is_writable($dir)) {
        echo "✅ Writable: $dir<br>";
    } else {
        echo "❌ Not writable: $dir (Run: chmod 755 $dir)<br>";
        // Try to make it writable
        @chmod($dir, 0755);
    }
}

// Create .htaccess for uploads directory
$htaccess_content = '# Uploads Directory Security Configuration

# Allow access to image files only
<FilesMatch "\.(jpg|jpeg|png|gif)$">
    Order Allow,Deny
    Allow from all
</FilesMatch>

# Deny access to PHP files for security
<FilesMatch "\.php$">
    Order Deny,Allow
    Deny from all
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
    echo "✅ Created: $htaccess_file<br>";
} else {
    echo "❌ Failed to create: $htaccess_file<br>";
}

// Create test image to verify upload works
$test_image = 'uploads/profiles/test.txt';
if (file_put_contents($test_image, 'Test file')) {
    echo "✅ Can write to uploads/profiles/<br>";
    @unlink($test_image); // Delete test file
} else {
    echo "❌ Cannot write to uploads/profiles/<br>";
}

// Check PHP upload settings
echo "<h3>PHP Upload Settings:</h3>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "file_uploads: " . (ini_get('file_uploads') ? 'Enabled' : 'Disabled') . "<br>";

// Show current permissions
echo "<h3>Directory Permissions:</h3>";
foreach ($directories as $dir) {
    if (file_exists($dir)) {
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        echo "$dir: $perms<br>";
    }
}

echo "<hr>";
echo "<h3>✅ Setup Complete!</h3>";
echo "<p><strong>IMPORTANT:</strong> Delete this file (setup-uploads.php) after running it for security.</p>";
echo "<p>Now try uploading a profile picture at: <a href='student/profile.php'>student/profile.php</a></p>";
?>