<?php
// Enable error reporting (for development only; disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

// Include database connection
include "includes/dp_connect.php";

// Check if the user is already logged in; if so, redirect to the report
if (isset($_SESSION['employee_id'])) {
    header('Location: main_menu.php');
    exit;
}


// Initialize variables
$username = '';
$password = '';
$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form inputs
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    // Basic validation
    if (empty($username) || empty($password)) {
        $message = "Please enter both username and password.";
    } else {
        // Prepare SQL statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT employee_id, username, password, role FROM users WHERE username = ?");
        if ($stmt) {
            $stmt->bind_param("s", $username);
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows === 1) {
                    // Bind the result variables
                    $stmt->bind_result($user_id, $db_username, $db_password, $role);
                    $stmt->fetch();
                    
                    // Verify the password
                    if (password_verify($password, $db_password)) {
                        // Password is correct; set session variables
                        $_SESSION['employee_id'] = $user_id;
                        $_SESSION['username'] = $db_username;
                        $_SESSION['role'] = $role;
                        
                        if ($role === "employee"){
                            header("Location: modules/request_leave.php");
                        }
                        else if ($role === "jarvis" or $role === "executive"){
                            header("Location: modules/employee_directory.php");
                        }
                        
                        
                        exit;
                    } else {
                        // Invalid password
                        $message = "Invalid username or password.";
                    }
                } else {
                    // Username not found
                    $message = "Invalid username or password.";
                }
            } else {
                // SQL execution failed
                $message = "An error occurred. Please try again later.";
            }
            $stmt->close();
        } else {
            // SQL preparation failed
            $message = "An error occurred. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kilburnazon - Login System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            margin-top: 100px;
        }
        .login-card {
            padding: 30px;
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            background-color: #ffffff;
        }
    </style>
</head>
<body>
    <div class="container login-container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card login-card">
                    <h3 class="text-center mb-4">Kilburnazon - Login System</h3>
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>
                    <form method="POST" action="login.php">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username:</label>
                            <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($username); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password:</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                </div>
                <p class="text-center mt-3">Don't have an account? Contact your administrator.</p>
            </div>
        </div>
    </div>
</body>
</html>
