<?php
include "../includes/dp_connect.php";
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// Check if the user is logged in and has the required role
if (!isset($_SESSION['employee_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['executive', 'jarvis'])) {
    // Redirect to login page or show an error
    header('Location: ../login.php');
    exit;
}

// Assume manager_id is obtained from session
$manager_id = $_SESSION['employee_id'];

// Fetch pending leave requests
$pending_sql = "SELECT lr.request_id, e.first_name, e.last_name, lt.leave_type_name, lr.start_date, lr.end_date, lr.comments, lr.request_date
                FROM LeaveRequests lr
                JOIN Employee e ON lr.employee_id = e.employee_id
                JOIN LeaveTypes lt ON lr.leave_type_id = lt.leave_type_id
                WHERE lr.status = 'Pending'";
$pending_result = $conn->query($pending_sql);

// Handle approval or denial
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = intval($_POST['request_id']);
    $action = $_POST['action']; // 'Approve' or 'Deny'

    if ($action === 'Approve') {
        // Fetch leave request details
        $details_sql = "SELECT employee_id, leave_type_id, start_date, end_date FROM LeaveRequests WHERE request_id = ?";
        $stmt_details = $conn->prepare($details_sql);
        $stmt_details->bind_param("i", $request_id);
        $stmt_details->execute();
        $details_result = $stmt_details->get_result();
        $details = $details_result->fetch_assoc();

        $employee_id = $details['employee_id'];
        $leave_type_id = $details['leave_type_id'];
        $start_date = $details['start_date'];
        $end_date = $details['end_date'];

        // Calculate leave days
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $interval = $start->diff($end);
        $leave_days = $interval->days + 1; // Including start date

        // Check and update leave balance
        $balance_sql = "SELECT balance FROM LeaveBalances WHERE employee_id = ? AND leave_type_id = ?";
        $stmt_balance = $conn->prepare($balance_sql);
        $stmt_balance->bind_param("ii", $employee_id, $leave_type_id);
        $stmt_balance->execute();
        $balance_result = $stmt_balance->get_result();
        $current_balance = $balance_result->fetch_assoc()['balance'];

        if ($leave_days > $current_balance) {
            $message = "<div class='alert alert-danger' role='alert'>Cannot approve. Insufficient leave balance.</div>";
        } else {
            // Update leave request status to Approved
            $update_request_sql = "UPDATE LeaveRequests SET status = 'Approved', manager_id = ? WHERE request_id = ?";
            $stmt_update = $conn->prepare($update_request_sql);
            $stmt_update->bind_param("ii", $manager_id, $request_id);
            if ($stmt_update->execute()) {
                // Deduct leave days from balance
                $deduct_sql = "UPDATE LeaveBalances SET balance = balance - ? WHERE employee_id = ? AND leave_type_id = ?";
                $stmt_deduct = $conn->prepare($deduct_sql);
                $stmt_deduct->bind_param("dii", $leave_days, $employee_id, $leave_type_id);
                $stmt_deduct->execute();
                $stmt_deduct->close();

                $message = "<div class='alert alert-success' role='alert'>Leave request approved successfully.</div>";
            } else {
                $message = "<div class='alert alert-danger' role='alert'>Error approving leave request: " . htmlspecialchars($conn->error) . "</div>";
            }
            $stmt_update->close();
        }
        $stmt_balance->close();
        $stmt_details->close();
    } elseif ($action === 'Deny') {
        // Update leave request status to Denied
        $update_request_sql = "UPDATE LeaveRequests SET status = 'Denied', manager_id = ? WHERE request_id = ?";
        $stmt_update = $conn->prepare($update_request_sql);
        $stmt_update->bind_param("ii", $manager_id, $request_id);
        if ($stmt_update->execute()) {
            $message = "<div class='alert alert-success' role='alert'>Leave request denied successfully.</div>";
        } else {
            $message = "<div class='alert alert-danger' role='alert'>Error denying leave request: " . htmlspecialchars($conn->error) . "</div>";
        }
        $stmt_update->close();
    }

    // Refresh pending requests
    $pending_result = $conn->query($pending_sql);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Leave Requests</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .manage-leave-container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .manage-leave-container h1 {
            margin-bottom: 20px;
        }

        .action-buttons button {
            margin-right: 5px;
        }

        @media (max-width: 576px) {
            .action-buttons button {
                margin-bottom: 5px;
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/navbar.php'; // Create a navbar.php with links as needed ?>

    <div class="container my-5">
        <div class="manage-leave-container">
            <h1 class="text-center">Manage Leave Requests</h1>
            <?php if (!empty($message)) {
                echo $message;
            } ?>

            <?php if ($pending_result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Employee Name</th>
                                <th>Leave Type</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Comments</th>
                                <th>Request Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $pending_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['leave_type_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['start_date']); ?></td>
                                    <td><?php echo htmlspecialchars($row['end_date']); ?></td>
                                    <td><?php echo htmlspecialchars($row['comments']); ?></td>
                                    <td><?php echo htmlspecialchars($row['request_date']); ?></td>
                                    <td class="action-buttons">
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="request_id" value="<?php echo $row['request_id']; ?>">
                                            <button type="submit" name="action" value="Approve"
                                                class="btn btn-success btn-sm">Approve</button>
                                            <button type="submit" name="action" value="Deny"
                                                class="btn btn-danger btn-sm">Deny</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center" role="alert">
                    No pending leave requests.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>