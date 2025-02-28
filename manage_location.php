<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username = "root";
$password = "Amirhmbm_2004";
$database = "web_server";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Handle adding a location
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['location'])) {
    $location = trim($_POST['location']);

    if (!empty($location)) {
        $stmt = $conn->prepare("INSERT INTO routers (location, site) VALUES (?, 'Unknown')");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("s", $location);

        if ($stmt->execute()) {
            echo "<p style='color: green;'>Location added successfully!</p>";
        } else {
            echo "<p style='color: red;'>Error: " . $stmt->error . "</p>";
        }

        $stmt->close();
    } else {
        echo "<p style='color: red;'>Invalid location!</p>";
    }
}

// Handle deleting a location
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_location'])) {
    $delete_location = trim($_POST['delete_location']);

    if (!empty($delete_location)) {
        $stmt = $conn->prepare("DELETE FROM routers WHERE location = ?");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("s", $delete_location);

        if ($stmt->execute()) {
            echo "<p style='color: red;'>Location deleted successfully!</p>";
        } else {
            echo "<p style='color: red;'>Error: " . $stmt->error . "</p>";
        }

        $stmt->close();
    }
}

// Fetch existing locations for display
$sql = "SELECT DISTINCT location FROM routers ORDER BY location ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Locations</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
        }
        .container {
            width: 50%;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        input[type="text"], button {
            padding: 10px;
            margin: 5px;
        }
        button {
            cursor: pointer;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
        }
        .add-btn {
            background-color: #28a745;
            color: white;
        }
        .add-btn:hover {
            background-color: #218838;
        }
        .delete-btn {
            background-color: #dc3545;
            color: white;
            margin-left: 10px;
        }
        .delete-btn:hover {
            background-color: #c82333;
        }
        .location-list {
            margin-top: 20px;
            list-style-type: none;
            padding: 0;
        }
        .location-item {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Manage Locations</h2>

        <!-- Form to add a new location -->
        <form action="manage_location.php" method="POST">
            <input type="text" name="location" placeholder="Enter new location" required>
            <button type="submit" class="add-btn">Add Location</button>
        </form>

        <div class="location-list">
            <h3>Existing Locations</h3>
            <ul>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<li class='location-item'>"
                            . htmlspecialchars($row['location']) .
                            " <form action='manage_location.php' method='POST' style='display:inline;'>
                                <input type='hidden' name='delete_location' value='" . htmlspecialchars($row['location']) . "'>
                                <button type='submit' class='delete-btn'>Delete</button>
                              </form>
                          </li>";
                    }
                } else {
                    echo "<p>No locations found.</p>";
                }
                ?>
            </ul>
        </div>

        <br>
        <a href="location.php">Back to Locations</a>
   </div>
</body>
</html>

<?php
$conn->close();
?>
