<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username = "root";
$password = "Amirhmbm_2004";
$database = "web_server";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Fetch locations and sites grouped by location
$sql = "SELECT location, site FROM routers ORDER BY location, site";
$result = $conn->query($sql);
if (!$result) {
    die("Error in SQL query: " . $conn->error);
}

$locations = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $location = $row['location'];
        $site = $row['site'];

        // Group sites under locations
        if (!isset($locations[$location])) {
            $locations[$location] = ['sites' => [], 'total' => 0, 'active' => 0];
        }
        if (!in_array($site, $locations[$location]['sites'])) {
            $locations[$location]['sites'][] = $site;
        }
    }
}

// Fetch router status count per location
$status_sql = "SELECT r.location, COUNT(rs.id) AS total,
                      SUM(CASE WHEN rs.status = 'up' THEN 1 ELSE 0 END) AS active
               FROM routers r
               LEFT JOIN router_status rs
               ON r.router_name = rs.router_name AND r.ip_address = rs.ip_address
               GROUP BY r.location";
$status_result = $conn->query($status_sql);
if (!$status_result) {
    die("Error in SQL query (status count): " . $conn->error);
}

if ($status_result->num_rows > 0) {
    while ($row = $status_result->fetch_assoc()) {
        if (isset($locations[$row['location']])) {
            $locations[$row['location']]['total'] = $row['total'];
            $locations[$row['location']]['active'] = $row['active'];
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devices Locations</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header .left { flex: 1; text-align: left; }
        .header .center { flex: 1; text-align: center; }
        .header .right { flex: 1; text-align: right; }
        .header a { text-decoration: none; padding: 8px 12px; border-radius: 5px; font-weight: bold; }
        .logout { background: red; color: white; }
        .manage-locations { background: green; color: white; }
        .user-approval { background: blue; color: white; }
        .location { background: #007bff; color: white; padding: 10px; border-radius: 5px; cursor: pointer; margin-top: 10px; font-weight: bold; display: flex; justify->
        .status { font-size: 14px; background: rgba(255, 255, 255, 0.2); padding: 5px 10px; border-radius: 5px; }
        .sites { display: none; padding: 10px; margin-left: 20px; border-left: 3px solid #007bff; }
        .site { background: #f8f9fa; padding: 3px 8px; border-radius: 5px; border: 1px solid #ccc; font-size: 14px; margin-top: 5px; }
        .site a { text-decoration: none; color: #007bff; font-weight: bold; }
        .edit-location { background: orange; color: white; padding: 5px 8px; border-radius: 5px; text-decoration: none; font-size: 12px; margin-left: 10px; }
    </style>
    <script>
        function toggleSites(locationId) {
            var sitesDiv = document.getElementById('sites-' + locationId);
            if (sitesDiv.style.display === 'none' || sitesDiv.style.display === '') {
                sitesDiv.style.display = 'block';
            } else {
                sitesDiv.style.display = 'none';
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="left">
                <a class="manage-locations" href="manage_location.php">Manage Locations</a>
            </div>
            <div class="center">
                <a class="user-approval" href="approval.php">User Approval</a>
            </div>
            <div class="right">
                <a class="logout" href="login.php">Logout</a>
            </div>
        </div>

        <?php foreach ($locations as $location => $data): ?>
            <div class="location" onclick="toggleSites('<?php echo htmlspecialchars($location); ?>')">
                <span><?php echo htmlspecialchars($location); ?></span>
                <span class="status">Active: <?php echo $data['active']; ?> / Total: <?php echo $data['total']; ?></span>
                <!-- Edit Site button passes the location as a parameter -->
                <a class="edit-location" href="edit_site.php?location=<?php echo urlencode($location); ?>">Edit Site</a>
            </div>
            <div class="sites" id="sites-<?php echo htmlspecialchars($location); ?>">
                <?php foreach ($data['sites'] as $site): ?>
                    <div class="site">
                        <a href="admin.php?location=<?php echo urlencode($location); ?>&site=<?php echo urlencode($site); ?>"><?php echo htmlspecialchars($site); ?></a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
