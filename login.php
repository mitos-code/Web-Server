<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start the session at the very beginning
session_start();

// Database connection
$host = 'localhost'; // Replace with your DB host
$dbname = 'web_server'; // Replace with your database name
$username = 'root'; // Replace with your database username
$password = 'Amirhmbm_2004'; // Replace with your database password

$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputUsername = $_POST['username'];
    $inputPassword = $_POST['password'];

    // Check if user exists and is approved
    $stmt = $conn->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND approved = 1");
    $stmt->bind_param("ss", $inputUsername, $inputUsername);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Verify password
        if (password_verify($inputPassword, $user['password'])) {
            // Password is correct, start session and store user info
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on user role
            if ($_SESSION['role'] === 'admin') {
                header("Location: location.php");
            } else {
                header("Location: main_menu.php");
            }
            exit();
        } else {
            $error_message = "Incorrect password.";
        }
    } else {
        $error_message = "User not found or not approved.";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        /* Basic styling */
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background: white;
            padding: 30px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            border-radius: 8px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .captcha-box {
            margin-bottom: 20px;
        }
        .captcha-box label {
            font-size: 14px;
        }
        .btn {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background-color: #45a049;
        }
        .message {
            color: red;
            text-align: center;
            font-size: 14px;
        }
        .signup-btn {
            color: #4CAF50;
            text-decoration: none;
        }
        .signup-btn:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Login</h2>
        <form method="POST" action="login.php" id="login-form">
            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" id="username" name="username" placeholder="Enter your username or email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            <div class="captcha-box">
                <input type="checkbox" id="captcha-checkbox" name="captcha-checkbox" required>
                <label for="captcha-checkbox">I'm not a robot</label>
            </div>
            <button type="submit" id="submit-btn" class="btn">Log In</button>
            <?php if ($error_message): ?>
                <p id="error-message" class="message"><?php echo $error_message; ?></p>
            <?php endif; ?>
            <p>Don't have an account? <a href="signup.php" class="signup-btn">Sign Up</a></p>
        </form>
    </div>
</body>
</html>

