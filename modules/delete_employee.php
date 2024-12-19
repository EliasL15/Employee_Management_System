<?php
// Enable error reporting (only in development)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// Correct the include path using __DIR__ for reliability
include "../includes/dp_connect.php";

// Check if the user is logged in and has the required role
if (!isset($_SESSION['employee_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['executive', 'jarvis'])) {
    // Redirect to login page or show an error
    header('Location: ../login.php');
    exit;
}

// Initialize message variable
$message = "";

// Check if the database connection is successful
if (!$conn) {
    $message = "<div class='alert alert-danger' role='alert'>
                Database connection failed: " . htmlspecialchars(mysqli_connect_error()) . "
                </div>";
} else {
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!empty($_POST['employee_id'])) {
            // Get the employee ID from the form
            $employee_id = intval($_POST['employee_id']);

            // Set the admin user ID (replace with session-based user ID in production)
            $admin_id = $_SESSION['employee_id']; // Update as per your user system

            // Set the admin user ID variable for the trigger
            if (!$conn->query("SET @admin_id = '" . $conn->real_escape_string($admin_id) . "'")) {
                $message = "<div class='alert alert-danger' role='alert'>
                            Error setting admin ID: " . htmlspecialchars($conn->error) . "
                            </div>";
            } else {
                // Delete the employee using a prepared statement
                $delete_sql = "UPDATE Employee SET is_deleted = 1, deleted_at = NOW() WHERE employee_id = ?";
                $stmt = $conn->prepare($delete_sql);

                if ($stmt) {
                    $stmt->bind_param("i", $employee_id);

                    if ($stmt->execute()) {
                        if ($stmt->affected_rows > 0) {
                            $message = "<div class='alert alert-success' role='alert'>
                                        Employee deleted successfully.
                                        </div>";
                        } else {
                            $message = "<div class='alert alert-warning' role='alert'>
                                        No employee found with the selected ID.
                                        </div>";
                        }
                    } else {
                        $message = "<div class='alert alert-danger' role='alert'>
                                    Error deleting employee: " . htmlspecialchars($stmt->error) . "
                                    </div>";
                    }

                    $stmt->close();
                } else {
                    $message = "<div class='alert alert-danger' role='alert'>
                                Prepare failed: " . htmlspecialchars($conn->error) . "
                                </div>";
                }
            }
        } else {
            $message = "<div class='alert alert-warning' role='alert'>
                        Please select an employee to delete.
                        </div>";
        }
    }

    // Fetch employees for the dropdown list using a prepared statement
    $employees_sql = "SELECT employee_id, CONCAT(first_name, ' ', last_name) AS name FROM Employee WHERE is_deleted = 0";
    $employees = $conn->query($employees_sql);

    if (!$employees) {
        $message = "<div class='alert alert-danger' role='alert'>
                    Error fetching employees: " . htmlspecialchars($conn->error) . "
                    </div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Employee</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-5">
        <h1 class="mb-4">Delete Employee</h1>
        <?php echo $message; ?>

        <?php if ($conn && $employees && $employees->num_rows > 0): ?>
            <form method="POST" class="row g-3">
                <div class="col-md-6">
                    <label for="employee_id" class="form-label">Select Employee:</label>
                    <select id="employee_id" name="employee_id" class="form-select" required>
                        <option value="">-- Select Employee --</option>
                        <?php
                        while ($row = $employees->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($row['employee_id']) . "'>" . htmlspecialchars($row['name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-danger">Delete Employee</button>
                </div>
            </form>
        <?php elseif ($conn && $employees && $employees->num_rows == 0): ?>
            <div class="alert alert-info" role="alert">
                No employees available to delete.
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
<?php
// Close the database connection
if (isset($conn)) {
    $conn->close();
}
?>