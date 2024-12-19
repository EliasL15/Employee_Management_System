<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

include "../includes/dp_connect.php";

// Check if the user is logged in and has the required role
if (!isset($_SESSION['employee_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['executive', 'jarvis'])) {
    // Redirect to login page
    header('Location: ../login.php');
    exit;
}


// Initialize message variable
$message = "";

// Check if the database connection is successful
if (!$conn) {
    $message = "<div class='alert alert-danger' role='alert'>Database connection failed: " . mysqli_connect_error() . "</div>";
} else {
    // Fetch audit logs using a prepared statement for security
    $sql = "SELECT log_id, first_name, last_name, termination_date, deleted_by_employee_id
            FROM AuditTermination
            ORDER BY termination_date DESC";

    if ($result = $conn->query($sql)) {
        // Query executed successfully
    } else {
        // Query failed
        $message = "<div class='alert alert-danger' role='alert'>Error fetching audit logs: " . $conn->error . "</div>";
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Audit Log</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-5">
        <h1 class="mb-4">Audit Log</h1>
        <?php echo $message; ?>
        <?php if (isset($result) && $result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Log ID</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Termination Date</th>
                            <th>Deleted By (Employee ID)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['log_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['termination_date']); ?></td>
                                <td><?php echo htmlspecialchars($row['deleted_by_employee_id']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif (isset($result)): ?>
            <div class="alert alert-info" role="alert">
                No audit logs found.
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
<?php
if (isset($conn)) {
    $conn->close();
}
?>