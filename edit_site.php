<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username   = "root";
$password   = "Amirhmbm_2004";
$database   = "web_server";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Get the location from the URL
$location = isset($_GET['location']) ? $_GET['location'] : '';
if (empty($location)) {
    die("Location not specified.");
}

// Handle new site addition for the given location
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_site'])) {
    $new_site = trim($_POST['new_site']);
    if (!empty($new_site)) {
        // Check if this site already exists for this location in the routers table
        $check_stmt = $conn->prepare("SELECT COUNT(*) AS count FROM routers WHERE location = ? AND site = ?");
        $check_stmt->bind_param("ss", $location, $new_site);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result()->fetch_assoc();
        $check_stmt->close();

        if ($check_result['count'] == 0) {
            // Insert a new row into routers with the new site and the given location.
            // Using default values for ip_address (NULL) and router_name ('DefaultRouter').
            $default_router_name = "DefaultRouter";
            $default_ip = NULL;
            $insert_stmt = $conn->prepare("INSERT INTO routers (ip_address, router_name, site, location) VALUES (?, ?, ?, ?)");
            $insert_stmt->bind_param("ssss", $default_ip, $default_router_name, $new_site, $location);
            $insert_stmt->execute();
            $insert_stmt->close();

            // Option 1: Update any records with "DefaultSite" for this location to the new site value
            $update_stmt = $conn->prepare("UPDATE routers SET site = ? WHERE location = ? AND site = 'DefaultSite'");
            $update_stmt->bind_param("ss", $new_site, $location);
            $update_stmt->execute();
            $update_stmt->close();
        }
    }
    header("Location: edit_site.php?location=" . urlencode($location));
    exit();
}

// Handle site removal – update all routers with this location and site to the default site value "DefaultSite"
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remove_site'])) {
    $remove_site = trim($_POST['remove_site']);
    if (!empty($remove_site)) {
        $default_site = "DefaultSite";
        $update_stmt = $conn->prepare("UPDATE routers SET site = ? WHERE location = ? AND site = ?");
        $update_stmt->bind_param("sss", $default_site, $location, $remove_site);
        $update_stmt->execute();
        $update_stmt->close();
    }
    header("Location: edit_site.php?location=" . urlencode($location));
    exit();
}

// Fetch distinct sites for the current location from the routers table
$stmt = $conn->prepare("SELECT DISTINCT site FROM routers WHERE location = ? ORDER BY site");
$stmt->bind_param("s", $location);
$stmt->execute();
$result = $stmt->get_result();

$sites = [];
while ($row = $result->fetch_assoc()) {
    $sites[] = $row['site'];
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Sites for <?php echo htmlspecialchars($location); ?></title>
  <style>
      body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f4; }
      .container { max-width: 600px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
      h2 { text-align: center; }
      form { margin-bottom: 20px; }
      input[type="text"] { width: 80%; padding: 8px; margin-right: 10px; }
      button { padding: 8px 12px; }
      .site-item { background: #e9ecef; padding: 10px; margin-bottom: 10px; border-radius: 5px; display: flex; justify-content: space-between; align-items: center; }
      .back-link { display: inline-block; margin-top: 20px; text-decoration: none; color: #007bff; }
  </style>
</head>
<body>
  <div class="container">
    <h2>Edit Sites for "<?php echo htmlspecialchars($location); ?>"</h2>

    <!-- Form to add a new site -->
    <form method="POST">
      <input type="text" name="new_site" placeholder="Enter new site" required>
      <button type="submit" name="add_site">Add Site</button>
    </form>

    <h3>Existing Sites</h3>
    <?php if (empty($sites)): ?>
      <p>No sites found for this location.</p>
    <?php else: ?>
      <?php foreach ($sites as $site): ?>
        <div class="site-item">
          <span><?php echo htmlspecialchars($site); ?></span>
          <?php if ($site !== "DefaultSite") : ?>
          <form method="POST" style="margin:0;">
            <input type="hidden" name="remove_site" value="<?php echo htmlspecialchars($site); ?>">
            <button type="submit">Remove Site</button>
          </form>
          <?php else: ?>
          <em>Default</em>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

    <a class="back-link" href="location.php">← Back to Locations</a>
  </div>
</body>
</html>
