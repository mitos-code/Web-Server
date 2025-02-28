<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username = "root";
$password = "Amirhmbm_2004";
$database = "web_server";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Approve user
if (isset($_GET['approve'])) {
    $id = intval($_GET['approve']);
    $sql = "UPDATE users SET approved = 1 WHERE id = $id AND role != 'admin'";
    $conn->query($sql);
    header("Location: approval.php");
    exit();
}

// Reject user (delete from database)
if (isset($_GET['reject'])) {
    $id = intval($_GET['reject']);
    $sql = "DELETE FROM users WHERE id = $id AND role != 'admin'";
    $conn->query($sql);
    header("Location: approval.php");
    exit();
}

// Fetch users waiting for approval (excluding admins)
$sql_pending = "SELECT id, username, email, created_at FROM users WHERE approved = 0 AND role != 'admin'";
$result_pending = $conn->query($sql_pending);

// Fetch approved users (excluding admins)
$sql_approved = "SELECT id, username, email, created_at FROM users WHERE approved = 1 AND role != 'admin'";
$result_approved = $conn->query($sql_approved);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Approvals</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 20px; }
        .container { max-width: 800px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #007bff; color: white; }
        .approve { background: green; color: white; padding: 5px 10px; border-radius: 5px; text-decoration: none; }
        .reject { background: red; color: white; padding: 5px 10px; border-radius: 5px; text-decoration: none; }
        .back { display: block; text-align: center; margin-top: 20px; background: #555; color: white; padding: 10px; border-radius: 5px; text-decoration: none; }
    </style>
</head>
<body>

<div class="container">
    <h2>Pending User Approvals</h2>

    <?php if ($result_pending->num_rows > 0): ?>
        <table>
            <tr>
                <th>Username</th>
                <th>Email</th>
                <th>Registered At</th>
                <th>Actions</th>
            </tr>
            <?php while ($row = $result_pending->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo $row['created_at']; ?></td>
                    <td>
                        <a href="approval.php?approve=<?php echo $row['id']; ?>" class="approve">Approve</a>
                        <a href="approval.php?reject=<?php echo $row['id']; ?>" class="reject" onclick="return confirm('Are you sure you want to reject this user?');">Reject</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p style="text-align: center;">No pending approvals.</p>
    <?php endif; ?>

    <h2>Approved Users</h2>
    <?php if ($result_approved->num_rows > 0): ?>
        <table>
            <tr>
                <th>Username</th>
                <th>Email</th>
                <th>Registered At</th>
                <th>Actions</th>
            </tr>
            <?php while ($row = $result_approved->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo $row['created_at']; ?></td>
                    <td>
                        <a href="approval.php?reject=<?php echo $row['id']; ?>" class="reject" onclick="return confirm('Are you sure you want to remove this approved user?');">Reject</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p style="text-align: center;">No approved users yet.</p>
    <?php endif; ?>

    <a href="location.php" class="back">Back</a>
</div>

</body>
</html>

<?php $conn->close(); ?>
