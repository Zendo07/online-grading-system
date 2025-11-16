<?php
session_start();
require_once '../includes/config.php';

// Check if logout is confirmed
if (isset($_POST['confirm_logout']) && $_POST['confirm_logout'] === 'yes') {
    // Clear session
    session_destroy();
    
    // Redirect with JavaScript to clear localStorage
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Logging out...</title>
        <script>
            // Clear all profile data from localStorage on logout
            const keys = Object.keys(localStorage);
            keys.forEach(key => {
                if (key.startsWith("profile_") || key === "current_user_id") {
                    localStorage.removeItem(key);
                }
            });
            
            // Also clear sessionStorage
            sessionStorage.clear();
            
            // Redirect to login immediately
            window.location.replace("' . BASE_URL . 'auth/login.php");
        </script>
    </head>
    <body></body>
    </html>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout Confirmation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            overflow: hidden;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            animation: fadeIn 0.3s ease forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .modal {
            background: white;
            border-radius: 12px;
            padding: 32px;
            max-width: 400px;
            width: 100%;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            transform: translateY(20px);
            opacity: 0;
            animation: slideUp 0.3s ease 0.1s forwards;
        }

        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-icon {
            width: 48px;
            height: 48px;
            margin: 0 auto 20px;
            background-color: #fee2e2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-icon svg {
            width: 24px;
            height: 24px;
            color: #dc2626;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 600;
            color: #111827;
            text-align: center;
            margin-bottom: 12px;
        }

        .modal-message {
            font-size: 14px;
            color: #6b7280;
            text-align: center;
            margin-bottom: 24px;
            line-height: 1.5;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
        }

        .btn {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-cancel {
            background-color: #f3f4f6;
            color: #374151;
        }

        .btn-cancel:hover {
            background-color: #e5e7eb;
        }

        .btn-logout {
            background-color: #dc2626;
            color: white;
        }

        .btn-logout:hover {
            background-color: #b91c1c;
        }

        .btn-logout:active {
            transform: scale(0.98);
        }
    </style>
</head>
<body>
    <div class="modal-overlay">
        <div class="modal">
            <div class="modal-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
            </div>
            <h2 class="modal-title">Confirm Logout</h2>
            <p class="modal-message">Are you sure you want to log out? You will need to sign in again to access your account.</p>
            <form method="POST" action="" class="modal-actions">
                <a href="javascript:history.back()" class="btn btn-cancel">Cancel</a>
                <button type="submit" name="confirm_logout" value="yes" class="btn btn-logout">Logout</button>
            </form>
        </div>
    </div>
</body>
</html>