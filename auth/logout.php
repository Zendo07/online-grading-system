<?php
// auth/logout.php
session_start();
require_once '../includes/config.php';

// Clear session
session_destroy();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Logging out...</title>
    <script>
        // Clear all profile data from localStorage on logout
        (function() {
            const keys = Object.keys(localStorage);
            keys.forEach(key => {
                if (key.startsWith('profile_') || key === 'current_user_id') {
                    localStorage.removeItem(key);
                }
            });
            
            // Also clear sessionStorage
            sessionStorage.clear();
            
            // Redirect to login
            window.location.href = '<?php echo BASE_URL; ?>auth/login.php';
        })();
    </script>
</head>
<body>
    <p>Logging out...</p>
</body>
</html>