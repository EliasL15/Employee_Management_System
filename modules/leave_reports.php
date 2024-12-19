<?php
include "../includes/dp_connect.php";
session_start();

// Check if the user is logged in and has the required role
if (!isset($_SESSION['employee_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['executive', 'jarvis'])) {
    // Redirect to login page
    header('Location: ../login.php');
    exit;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Handle form submission for report generation
$report_data = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];


    $group_by = "YEAR(lr.start_date), MONTH(lr.start_date)";
    

    // Updated query to include Position and Department relationship
    $report_sql = "
        SELECT 
            d.department_name,
            lt.leave_type_name,
            COUNT(*) AS total_requests,
            SUM(DATEDIFF(lr.end_date, lr.start_date) + 1) AS total_days_absent,
            YEAR(lr.start_date) AS year_group,
            CASE 
                WHEN ? = 'Monthly' THEN MONTH(lr.start_date)
                WHEN ? = 'Quarterly' THEN QUARTER(lr.start_date)
            END AS period_group
        FROM LeaveRequests lr
        JOIN Employee e ON lr.employee_id = e.employee_id
        JOIN Position p ON e.position_id = p.position_id
        JOIN Department d ON p.department_id = d.department_id
        JOIN LeaveTypes lt ON lr.leave_type_id = lt.leave_type_id
        WHERE lr.status = 'Approved'
          AND lr.start_date BETWEEN ? AND ?
        GROUP BY d.department_name, lt.leave_type_name, year_group, period_group
        ORDER BY year_group ASC, period_group ASC, d.department_name ASC;
    ";

    $stmt_report = $conn->prepare($report_sql);

    if ($stmt_report === false) {
        die("Error preparing the SQL statement: " . $conn->error);
    }

    $stmt_report->bind_param("ssss", $period, $period, $start_date, $end_date);
    $stmt_report->execute();
    $report_result = $stmt_report->get_result();

    while ($row = $report_result->fetch_assoc()) {
        $report_data[] = $row;
    }
    $stmt_report->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absenteeism Report</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .report-container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .report-container h1 {
            margin-bottom: 20px;
        }

        .report-form {
            margin-bottom: 30px;
        }

        .report-table th,
        .report-table td {
            vertical-align: middle;
        }

        @media (max-width: 576px) {
            .report-table thead {
                display: none;
            }

            .report-table,
            .report-table tbody,
            .report-table tr,
            .report-table td {
                display: block;
                width: 100%;
            }

            .report-table tr {
                margin-bottom: 15px;
            }

            .report-table td {
                text-align: right;
                padding-left: 50%;
                position: relative;
            }

            .report-table td::before {
                content: attr(data-label);
                position: absolute;
                left: 0;
                width: 50%;
                padding-left: 15px;
                font-weight: bold;
                text-align: left;
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container my-5">
        <div class="report-container">
            <h1 class="text-center">Absenteeism Report</h1>

            <!-- Report Generation Form -->
            <form method="POST" class="report-form">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">Start Date:</label>
                        <input type="date" id="start_date" name="start_date" class="form-control" required
                            value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : ''; ?>">
                    </div>

                    <div class="col-md-4">
                        <label for="end_date" class="form-label">End Date:</label>
                        <input type="date" id="end_date" name="end_date" class="form-control" required
                            value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : ''; ?>">
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary w-100">Generate Report</button>
                </div>
            </form>

            <!-- Report Display -->
            <?php if (!empty($report_data)): ?>
                <h2 class="mb-4">Report from <?php echo htmlspecialchars($start_date); ?> to
                    <?php echo htmlspecialchars($end_date); ?>
                </h2>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover report-table">
                        <thead class="table-dark">
                            <tr>
                                <th>Year</th>
                                <th>Department</th>
                                <th>Leave Type</th>
                                <th>Total Requests</th>
                                <th>Total Days Absent</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $data): ?>
                                <tr>
                                    <td data-label="Year"><?php echo htmlspecialchars($data['year_group']); ?></td>
                                        <?php
                                        ?>
                                    </td>
                                    <td data-label="Department"><?php echo htmlspecialchars($data['department_name']); ?></td>
                                    <td data-label="Leave Type"><?php echo htmlspecialchars($data['leave_type_name']); ?></td>
                                    <td data-label="Total Requests"><?php echo htmlspecialchars($data['total_requests']); ?>
                                    </td>
                                    <td data-label="Total Days Absent">
                                        <?php echo htmlspecialchars($data['total_days_absent']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                <div class="alert alert-info text-center" role="alert">
                    No data available for the selected period and date range.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>