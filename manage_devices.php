<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username = "root";
$password = "Amirhmbm_2004";
$dbname = "web_server";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$site = isset($_GET['site']) ? $_GET['site'] : '';
$location = isset($_GET['location']) ? $_GET['location'] : '';

// Handle form submission for adding a router
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_router'])) {
    $router_name = $_POST['router_name'];
    $ip_address = $_POST['ip_address'];

    // Insert query into routers table
    $insert_sql = "INSERT INTO routers (router_name, ip_address, site, location)
                   VALUES (?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("ssss", $router_name, $ip_address, $site, $location);

    if ($insert_stmt->execute()) {
        $insert_stmt->close();
        header("Location: manage_devices.php?site=" . urlencode($site) . "&location=" . urlencode($location));
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}

// Handle form submission for removing a router
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remove_router'])) {
    $router_id = $_POST['router_id'];

    // Delete query from routers table
    $delete_sql = "DELETE FROM routers WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $router_id);

    if ($delete_stmt->execute()) {
        $delete_stmt->close();
        header("Location: manage_devices.php?site=" . urlencode($site) . "&location=" . urlencode($location));
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}

// Fetch all routers for the remove device dropdown
$routers = [];
$router_sql = "SELECT id, router_name, ip_address FROM routers WHERE site = ? AND location = ?";
$router_stmt = $conn->prepare($router_sql);
$router_stmt->bind_param("ss", $site, $location);
$router_stmt->execute();
$router_result = $router_stmt->get_result();

if ($router_result->num_rows > 0) {
    while ($row = $router_result->fetch_assoc()) {
        $routers[] = $row;
    }
}
$router_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Devices</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(to right, #6a11cb, #2575fc);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.3);
            width: 400px;
            text-align: center;
        }
        h2 {
            color: #2c3e50;
            margin-bottom: 15px;
            border-bottom: 3px solid #2575fc;
            display: inline-block;
            padding-bottom: 5px;
        }
        input {
            width: calc(100% - 20px);
            padding: 10px;
            margin: 8px 0;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 16px;
        }
        button {
            width: 100%;
            padding: 10px;
            color: white;
            font-size: 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 10px;
        }
        .add-btn {
            background-color: #28a745; /* Green color for Add Devices button */
        }
        .remove-btn {
            background-color: #e74c3c; /* Red color for Remove Devices button */
        }
        .back-btn {
            display: block;
            margin-top: 20px;
            padding: 10px;
            color: white;
            text-align: center;
            border-radius: 8px;
            text-decoration: none;
            background-color: #ff0000; /* Red color for Back button */
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Manage Devices</h2>
    <form method="POST">
        <input type="text" id="router_name" name="router_name" placeholder="Device Name" required>
        <input type="text" id="ip_address" name="ip_address" placeholder="IP Address" required>
        <input type="hidden" name="site" value="<?= htmlspecialchars($site) ?>">
        <input type="hidden" name="location" value="<?= htmlspecialchars($location) ?>">
        <button type="submit" name="add_router" class="add-btn">Add Devices</button>
    </form>
    <form method="POST">
        <select name="router_id" required>
            <option value="" selected disabled>Select Device to Remove</option>
            <?php foreach ($routers as $router): ?>
                <option value="<?= htmlspecialchars($router['id']) ?>">
                    <?= htmlspecialchars($router['router_name']) ?> (<?= htmlspecialchars($router['ip_address']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="remove_router" class="remove-btn">Remove Devices</button>
    </form>
    <!-- Back Button -->
    <a href="admin.php?site=<?= urlencode($site) ?>&location=<?= urlencode($location) ?>" class="back-btn">Back to Admin</a>
</div>
</body>
</html>

<?php $conn->close(); ?>
