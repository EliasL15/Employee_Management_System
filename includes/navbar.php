<!-- navbar.php -->
<?php
// Start the session if it's not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include the database connection
include "../includes/dp_connect.php";

$pending_count = 0;

// Check if the user has the 'executive' or 'jarvis' role
if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['executive', 'jarvis'])) {
    $sql = "SELECT COUNT(*) AS pending_count FROM LeaveRequests WHERE status = 'pending'";
    
    // Prepare the statement
    if ($stmt = $conn->prepare($sql)) {
        // Execute the statement
        $stmt->execute();
        
        // Bind the result to the $pending_count variable
        $stmt->bind_result($pending_count);
        
        // Fetch the result
        $stmt->fetch();
        
        // Close the statement
        $stmt->close();
    } else {
        // Handle SQL preparation error (optional)
        error_log("Failed to prepare statement: " . $conn->error);
    }
}
?>

<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <?php
        if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['executive', 'jarvis'])) {
            $url = 'employee_directory.php';
        } else {
            $url = 'request_leave.php';
        }
        ?>
        <a class="navbar-brand" href="<?php echo htmlspecialchars($url); ?>">Kilburnazon</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['executive', 'jarvis'])) { ?>
                    <li class="nav-item">
                        <a class="nav-link" href="employee_directory.php">Employee Directory</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="add_employee.php">Add Employee</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="edit_employee.php">Edit Employee</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="delete_employee.php">Delete Employee</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="audit_log.php">Audit</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_leave.php">
                            Manage Leave
                            <?php if ($pending_count > 0) { ?>
                                <span class="badge bg-danger"><?php echo intval($pending_count); ?></span>
                            <?php } ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="leave_reports.php">Leave Reports</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="payroll_report.php">Payroll Report</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="view_birthdays.php">View Birthdays</a>
                    </li>
                <?php } ?>
                <li class="nav-item">
                    <a class="nav-link" href="../logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>