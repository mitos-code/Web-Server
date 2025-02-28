<?php
// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

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
    $newUsername = $_POST['username'];
    $newEmail = $_POST['email'];
    $newPassword = $_POST['password'];

    // Check if username already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $newUsername);
    $stmt->execute();
    $stmt->store_result();

    // Check if email already exists
    $stmtEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmtEmail->bind_param("s", $newEmail);
    $stmtEmail->execute();
    $stmtEmail->store_result();

    // Hash the password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    if ($stmt->num_rows > 0) {
        $error_message = "Username already exists. Please choose a different one.";
    } elseif ($stmtEmail->num_rows > 0) {
        $error_message = "Email already exists. Please choose a different one.";
    } else {
        $stmtInsert = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmtInsert->bind_param("sss", $newUsername, $newEmail, $hashedPassword);

        if ($stmtInsert->execute()) {
            header("Location: login.php");
            exit();
        } else {
            $error_message = "Error registering user: " . $stmtInsert->error;
        }
        $stmtInsert->close();
    }

    $stmt->close();
    $stmtEmail->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup</title>
    <style>
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
            position: relative;
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
        .login-btn {
            color: #4CAF50;
            text-decoration: none;
        }
        .login-btn:hover {
            text-decoration: underline;
        }
        .captcha-container {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .captcha-checkbox {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Signup</h2>
        <form method="POST" action="signup.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            <div class="captcha-container">
                <input type="checkbox" class="captcha-checkbox" required>
                <label>I'm not a robot</label>
            </div>
            <button type="submit" class="btn">Sign Up</button>
            <?php if ($error_message): ?>
                <p class="message"><?php echo $error_message; ?></p>
            <?php endif; ?>
        </form>
        <p>Already have an account? <a href="login.php" class="login-btn">Log In</a></p>
    </div>
</body>
</html>
