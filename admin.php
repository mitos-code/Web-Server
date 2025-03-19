<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Redirect if not an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Database connection settings
$servername = "localhost";
$username = "root"; // Change if needed
$password = "Amirhmbm_2004"; // Change if needed
$dbname = "web_server"; // Change if needed

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get selected site and location safely
$selected_site = $_GET['site'] ?? '';
$selected_location = $_GET['location'] ?? '';

// Determine the selected option from the dropdown
$selected_option = $_GET['option'] ?? 'status'; // Default to 'status'

// Fetch data based on the selected option
if ($selected_option === 'wifi_users') {
    // Fetch WiFi users data only for routers of type 'wifi_ap'
    $sql = "SELECT wu.id, wu.ip_address, wu.router_name, wu.total_users
            FROM wifi_users wu
            JOIN routers r ON wu.router_name = r.router_name
            WHERE r.site = ? AND r.location = ? AND r.type = 'WiFi AP'";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("ss", $selected_site, $selected_location);
    $stmt->execute();
    $result = $stmt->get_result();
} elseif ($selected_option === 'traffic') {
    // Fetch router traffic data for the selected site and location
    $sql = "SELECT router_name, ip_address, interface
            FROM routers
            WHERE site = ? AND location = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("ss", $selected_site, $selected_location);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Default: Fetch router status data for the selected site and location
    $sql = "SELECT r.router_name, r.site, r.location, r.ip_address, rs.status
            FROM routers r
            LEFT JOIN router_status rs ON r.router_name = rs.router_name AND r.ip_address = rs.ip_address
            WHERE r.site = ? AND r.location = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("ss", $selected_site, $selected_location);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel</title>
  <style>
      /* Your original CSS styles */
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
          margin-bottom: 10px;
      }
      select {
          width: 100%;
          padding: 10px;
          margin-bottom: 20px;
          border-radius: 5px;
          border: 1px solid #ccc;
          font-size: 16px;
      }
      .router, .wifi-user, .traffic {
          background: #ecf0f1;
          padding: 10px;
          border-radius: 10px;
          margin: 10px 0;
          display: flex;
          justify-content: space-between;
          align-items: center;
          transition: transform 0.3s;
          cursor: pointer;
      }
      .router:hover, .wifi-user:hover, .traffic:hover {
          transform: scale(1.05);
      }
      .status-up, .status-down {
          padding: 5px 10px;
          border-radius: 5px;
          color: white;
      }
      .status-up {
          background: green;
      }
      .status-down {
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
      .manage-btn:hover {
          background-color: #1e8449;
      }
  </style>
  <script>
      document.addEventListener('DOMContentLoaded', function() {
          const adminOption = document.getElementById('admin_option');

          adminOption.addEventListener('change', function() {
              const selectedOption = this.value;
              // Redirect to the same page with the selected option as a query parameter
              window.location.href = `admin.php?option=${selectedOption}&site=<?= urlencode($selected_site) ?>&location=<?= urlencode($selected_location) ?>`;
          });
      });
  </script>
</head>
<body>
  <div class="container">
      <h2>ADMIN PANEL</h2>
      <select name="admin_option" id="admin_option">
          <option value="status" <?= $selected_option === 'status' ? 'selected' : '' ?>>Status</option>
          <option value="wifi_users" <?= $selected_option === 'wifi_users' ? 'selected' : '' ?>>WiFi Users</option>
          <option value="traffic" <?= $selected_option === 'traffic' ? 'selected' : '' ?>>Traffic</option>
      </select>

      <!-- Display data based on the selected option -->
      <?php if ($selected_option === 'wifi_users'): ?>
          <!-- WiFi Users List -->
          <h2>WiFi Users</h2>
          <?php if ($result->num_rows > 0): ?>
              <?php while ($row = $result->fetch_assoc()): ?>
                  <!-- Make each row clickable and redirect to wifi_graph.php -->
                  <div class="wifi-user" onclick="window.location.href='wifi_graph.php?router=<?= urlencode($row['router_name']) ?>&ip=<?= urlencode($row['ip_address']>
                      <span>Device: <?= htmlspecialchars($row['router_name'] ?? '') ?></span>
                      <span>IP: <?= htmlspecialchars($row['ip_address'] ?? '') ?></span>
                      <span>Users: <?= htmlspecialchars($row['total_users'] ?? '') ?></span>
                  </div>
              <?php endwhile; ?>
          <?php else: ?>
              <p>No WiFi users data available for the selected site and location.</p>
          <?php endif; ?>
      <?php elseif ($selected_option === 'traffic'): ?>
          <!-- Traffic List -->
          <h2>Traffic</h2>
          <?php if ($result->num_rows > 0): ?>
              <?php while ($row = $result->fetch_assoc()): ?>
                  <div class="traffic">
                      <span>Device: <?= htmlspecialchars($row['router_name'] ?? '') ?></span>
                      <span>IP: <?= htmlspecialchars($row['ip_address'] ?? '') ?></span>
                      <span>Interface: <?= htmlspecialchars($row['interface'] ?? 'N/A') ?></span>
                  </div>
              <?php endwhile; ?>
          <?php else: ?>
              <p>No traffic data available for the selected site and location.</p>
          <?php endif; ?>
      <?php else: ?>
          <!-- Router Status List -->
          <?php if ($result->num_rows > 0): ?>
              <?php while ($row = $result->fetch_assoc()): ?>
                  <?php
                  $statusClass = (strtolower($row["status"]) == "up") ? "status-up" : "status-down";
                  ?>
                  <div class="router" onclick="window.location.href='status_graph.php?router=<?= urlencode($row['router_name']) ?>'">
                      <span>Device: <?= htmlspecialchars($row["router_name"] ?? '') ?></span>
                      <span>IP: <?= htmlspecialchars($row["ip_address"] ?? '') ?></span>
                      <span class="<?= $statusClass ?>"><?= strtoupper(htmlspecialchars($row["status"] ?? '')) ?></span>
                  </div>
              <?php endwhile; ?>
          <?php else: ?>
              <p>No data available for the selected site and location.</p>
          <?php endif; ?>
      <?php endif; ?>

      <!-- Admin Controls -->
      <a href="manage_devices.php?site=<?= urlencode($selected_site) ?>&location=<?= urlencode($selected_location) ?>" class="action-btn manage-btn">Manage Devices</a>
      <a href="location.php?site=<?= urlencode($selected_site) ?>&location=<?= urlencode($selected_location) ?>" class="action-btn logout-btn">Back to Location</a>
  </div>
</body>
</html>
