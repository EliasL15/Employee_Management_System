<?php
include "../includes/dp_connect.php";
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();



// Use the logged-in user's ID
$employee_id = $_SESSION['employee_id'];

// Fetch leave types
$leave_types_sql = "SELECT leave_type_id, leave_type_name FROM LeaveTypes";
$leave_types_result = $conn->query($leave_types_sql);

// Handle form submission
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leave_type_id = intval($_POST['leave_type']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $comments = htmlspecialchars($_POST['comments']);

    // Calculate number of leave days
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $interval = $start->diff($end);
    $leave_days = $interval->days + 1; // Including start date

    // Check leave balance
    $balance_check_sql = "SELECT balance FROM LeaveBalances WHERE employee_id = ? AND leave_type_id = ?";
    $stmt_balance = $conn->prepare($balance_check_sql);
    $stmt_balance->bind_param("ii", $employee_id, $leave_type_id);
    $stmt_balance->execute();
    $balance_result = $stmt_balance->get_result();
    $balance_row = $balance_result->fetch_assoc();
    $balance = $balance_row ? $balance_row['balance'] : 0;

    if ($leave_days > $balance) {
        $message = "<div class='alert alert-danger' role='alert'>Insufficient leave balance for the selected leave type.</div>";
    } else {
        // Insert leave request
        $insert_sql = "INSERT INTO LeaveRequests (employee_id, leave_type_id, start_date, end_date, comments)
                    VALUES (?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($insert_sql);
        $stmt_insert->bind_param("iisss", $employee_id, $leave_type_id, $start_date, $end_date, $comments);

        if ($stmt_insert->execute()) {
            $message = "<div class='alert alert-success' role='alert'>Leave request submitted successfully.</div>";
            // Optionally, send notification to manager
            $manager_id = 2; // Replace with actual manager ID logic if needed

            $notification_msg = "New leave request from Employee ID: $employee_id";
            $insert_notif_sql = "INSERT INTO Notifications (manager_id, message) VALUES (?, ?)";
            $stmt_notif = $conn->prepare($insert_notif_sql);
            $stmt_notif->bind_param("is", $manager_id, $notification_msg);
            $stmt_notif->execute();
            $stmt_notif->close();
        } else {
            $message = "<div class='alert alert-danger' role='alert'>Error submitting leave request: " . htmlspecialchars($conn->error) . "</div>";
        }
        $stmt_insert->close();
    }
    $stmt_balance->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Leave</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .leave-form {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .leave-form h2 {
            margin-bottom: 20px;
        }

        .submit-button {
            width: 100%;
        }
    </style>
</head>

<body>
    <?php include '../includes/navbar.php'; // Create a navbar.php with links as needed ?>

    <div class="container my-5">
        <div class="leave-form">
            <h2 class="text-center">Request Leave</h2>
            <?php echo $message; ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="leave_type" class="form-label">Leave Type:</label>
                    <select id="leave_type" name="leave_type" class="form-select" required>
                        <option value="">-- Select Leave Type --</option>
                        <?php
                        while ($row = $leave_types_result->fetch_assoc()) {
                            // Fetch the balance for each leave type
                            $leave_type_id = $row['leave_type_id'];
                            $balance_sql = "SELECT balance FROM LeaveBalances WHERE employee_id = $employee_id AND leave_type_id = $leave_type_id";
                            $balance_result = $conn->query($balance_sql);
                            $balance_row = $balance_result->fetch_assoc();
                            $balance = $balance_row ? $balance_row['balance'] : 0;

                            echo "<option value='{$row['leave_type_id']}'>{$row['leave_type_name']} (Available: {$balance})</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="start_date" class="form-label">Start Date:</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="end_date" class="form-label">End Date:</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="comments" class="form-label">Comments:</label>
                    <textarea id="comments" name="comments" class="form-control" rows="4"></textarea>
                </div>

                <button type="submit" class="btn btn-primary submit-button">Submit Leave Request</button>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>