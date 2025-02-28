<?php
session_start();

// Redirect if not a guest
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guest') {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root"; // Change if needed
$password = "Amirhmbm_2004"; // Change if needed
$dbname = "web_server"; // Change if needed

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the selected site and location from GET request
$selected_site = isset($_GET['site']) ? $_GET['site'] : '';
$selected_location = isset($_GET['location']) ? $_GET['location'] : '';

// Build the SQL query
$sql = "SELECT r.router_name, r.site, r.location, r.ip_address, rs.status
        FROM routers r
        LEFT JOIN router_status rs ON r.id = rs.router_id
        WHERE rs.status = 'down'"; // Only show routers with "down" status

$conditions = [];
$params = [];
$types = "";

// Apply filters for site and location
if (!empty($selected_site)) {
    $conditions[] = "r.site = ?";
    $types .= "s";
    $params[] = $selected_site;
}
if (!empty($selected_location)) {
    $conditions[] = "r.location = ?";
    $types .= "s";
    $params[] = $selected_location;
}

// Append conditions to SQL if any exist
if (!empty($conditions)) {
    $sql .= " AND " . implode(" AND ", $conditions);
}

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

// Bind parameters if any exist
if (!empty($params)) {
    $bind_names[] = $types;
    foreach ($params as $key => $value) {
        $bind_names[] = &$params[$key];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_names);
}

$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Guest Panel</title>
  <style>
      /* Your existing CSS styles */
      body {
          font-family: 'Poppins', sans-serif;
          background: linear-gradient(to bottom, #4facfe, #00f2fe);
          display: flex;
          justify-content: center;
          align-items: center;
          height: 100vh;
          margin: 0;
      }
      .container {
          background: white;
          padding: 20px;
          border-radius: 15px;
          box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.2);
          width: 400px;
          text-align: center;
      }
      h2 {
          color: #2c3e50;
          border-bottom: 2px solid #2980b9;
          display: inline-block;
          padding-bottom: 5px;
          margin-bottom: 20px;
      }
      .router {
          background: #ecf0f1;
          padding: 10px;
          border-radius: 10px;
          margin: 10px 0;
          display: flex;
          justify-content: space-between;
          align-items: center;
          transition: transform 0.3s;
      }
      .router:hover {
          transform: scale(1.05);
      }
      .status-down {
          padding: 5px 10px;
          border-radius: 5px;
          color: white;
          background: red;
      }
      .action-btn {
          display: block;
          width: 80%;
          margin: 10px auto;
          padding: 6px;
          text-align: center;
          text-decoration: none;
          border-radius: 5px;
          font-weight: bold;
          font-size: 14px;
          transition: 0.3s;
      }
      .logout-btn {
          background-color: #e74c3c;
          color: white;
      }
      .logout-btn:hover {
          background-color: #c0392b;
      }
  </style>
</head>
<body>
  <div class="container">
      <h2>GUEST PANEL</h2>
      <!-- Router List -->
      <?php
      if ($result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) {
              echo "<div class='router'>";
              echo "<span>(" . htmlspecialchars($row["location"]) . ") (" . htmlspecialchars($row["site"]) . ") " . htmlspecialchars($row["router_name"]) . "</span>";
              echo "<span class='status-down'>DOWN</span>";
              echo "</div>";
          }
      } else {
          echo "<p>No data available</p>";
      }
      $stmt->close();
      $conn->close();
      ?>
      <!-- Logout Button -->
      <a href="login.php" class="action-btn logout-btn">Log Out</a>
  </div>
</body>
</html>
