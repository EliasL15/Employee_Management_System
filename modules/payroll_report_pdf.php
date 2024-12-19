<?php
// payroll_report_pdf.php

// Start the session
session_start();

// Check if the user is logged in and has the required role
if (!isset($_SESSION['employee_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['executive', 'jarvis'])) {
    // Redirect to login page or show an error
    header('Location: ../login.php');
    exit;
}


// Retrieve report data from session
$report_data = $_SESSION['report_data'] ?? [];
$start_date = $_SESSION['report_start_date'] ?? '';
$end_date = $_SESSION['report_end_date'] ?? '';

// Clear the session data
unset($_SESSION['report_data'], $_SESSION['report_start_date'], $_SESSION['report_end_date']);

// If no data, redirect back
if (empty($report_data)) {
    header('Location: payroll_report.php');
    exit;
}

// Calculate Summary Totals
$total_payroll = 0;
$total_salary = 0;
$employee_count = count($report_data);

foreach ($report_data as $row) {
    $total_payroll += $row['net_pay'];
    $total_salary += $row['salary_amount'];
}

$average_salary = $employee_count > 0 ? $total_salary / $employee_count : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Payroll Report PDF</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        h2,
        h3 {
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table,
        th,
        td {
            border: 1px solid #000;
        }

        th,
        td {
            padding: 8px;
            text-align: center;
        }

        .summary {
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <?php // include '../includes/navbar.php'; // Create a navbar.php with links as needed ?>

    <h2>Payroll Report</h2>
    <h3>Period: <?php echo htmlspecialchars($start_date); ?> to <?php echo htmlspecialchars($end_date); ?></h3>

    <table>
        <thead>
            <tr>
                <th>Employee ID</th>
                <th>Name</th>
                <th>Department</th>
                <th>Position</th>
                <th>Base Salary</th>
                <th>Bonuses</th>
                <th>Incentives</th>
                <th>Allowances</th>
                <th>Deductions</th>
                <th>Net Pay</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($report_data as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['employee_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['employee_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['department_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['position_title']); ?></td>
                    <td>$<?php echo number_format($row['salary_amount'], 2); ?></td>
                    <td>$<?php echo number_format($row['bonuses'], 2); ?></td>
                    <td>$<?php echo number_format($row['incentives'], 2); ?></td>
                    <td>$<?php echo number_format($row['allowances'], 2); ?></td>
                    <td>$<?php echo number_format($row['deductions'], 2); ?></td>
                    <td>$<?php echo number_format($row['net_pay'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Summary Totals -->
    <div class="summary">
        <h4>Summary Totals</h4>
        <p><strong>Total Payroll:</strong> $<?php echo number_format($total_payroll, 2); ?></p>
        <p><strong>Average Salary:</strong> $<?php echo number_format($average_salary, 2); ?></p>
    </div>

    <!-- Automatically trigger print dialog -->
    <script>
        //download the pdf
        window.print();
    </script>
</body>

</html>