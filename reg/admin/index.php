<?php
// admin/index.php - Admin dashboard

// Start secure session
session_start();

// Logout functionality
if (isset($_GET['logout'])) {
    // Unset all session variables
    $_SESSION = array();
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Redirect to the login page
    header("Location: index.php");
    exit();
}

// Basic authentication - in a real implementation, use proper authentication
$admin_username = 'admin';
$admin_password = 'admin123'; // This should be properly hashed in production

// Check if user is already authenticated
$authenticated = false;
if (isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true) {
    $authenticated = true;
} elseif (isset($_POST['username']) && isset($_POST['password'])) {
    // Verify credentials (in production, use password_hash and password_verify)
    if ($_POST['username'] === $admin_username && $_POST['password'] === $admin_password) {
        $_SESSION['admin_authenticated'] = true;
        $authenticated = true;
    } else {
        $error_message = "Invalid credentials";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Registration System</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f8;
            padding: 20px;
            color: #2c3e50;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1, h2 {
            text-align: center;
            color: #2c3e50;
        }
        .login-form {
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button, .btn {
            background-color: #2c3e50;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        button:hover, .btn:hover {
            background-color: #34495e;
        }
        .error {
            color: #e74c3c;
            background-color: #f9e7e7;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .admin-menu {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .admin-card {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .admin-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .admin-card h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        .admin-card p {
            color: #7f8c8d;
            margin-bottom: 20px;
        }
        .admin-card .btn {
            width: 100%;
        }
        .logout-link {
            display: block;
            text-align: right;
            margin-bottom: 20px;
        }
        .footer-links {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Dashboard</h1>
        
        <?php if (!$authenticated): ?>
            <!-- Login Form -->
            <div class="login-form">
                <h2>Admin Login</h2>
                <?php if (isset($error_message)): ?>
                    <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit">Login</button>
                </form>
            </div>
        <?php else: ?>
            <!-- Admin Dashboard -->
            <a href="?logout=1" class="logout-link">Logout</a>
            
            <div class="admin-menu">
                <div class="admin-card">
                    <h3>View Receipts</h3>
                    <p>View and manage student receipt uploads, approve or reject students.</p>
                    <a href="view_receipts.php" class="btn">Access</a>
                </div>
                
                <div class="admin-card">
                    <h3>View Approved Students</h3>
                    <p>View the list of approved and rejected students with their details.</p>
                    <a href="view_approved_students.php" class="btn">Access</a>
                </div>
                
                <!-- Add more admin functions as needed -->
                <!-- <div class="admin-card">
                    <h3>Reports</h3>
                    <p>Generate and view reports on student registrations and approvals.</p>
                    <a href="#" class="btn" onclick="alert('Coming soon!')">Access</a>
                </div>
                
                <div class="admin-card">
                    <h3>Settings</h3>
                    <p>Configure system settings and preferences.</p>
                    <a href="#" class="btn" onclick="alert('Coming soon!')">Access</a>
                </div> -->
            </div>
            
            <div class="footer-links">
                <a href="../index.php" class="btn">Return to Main Site</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 