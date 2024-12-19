<?php
// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session for access restriction
session_start();

// Access control: Only 'executive' and 'jarvis' roles can access
if (!isset($_SESSION['employee_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['executive', 'jarvis'])) {
    header('Location: ../login.php');
    exit;
}

// Include database connection
include "../includes/dp_connect.php";

// Validate database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Initialize variables
$report_data = [];
$message = "";
$start_date = '';
$end_date = '';
$department_filter = '';
$role_filter = '';
$salary_min = '';
$salary_max = '';
$export_format = '';

// Define records per page for pagination
$records_per_page = 20;

// Get current page from GET request, default is 1
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;

// Function to calculate overlapping months between two date ranges
function calculate_overlapping_months($start1, $end1, $start2, $end2)
{
    $start = max(strtotime($start1), strtotime($start2));
    $end = min(strtotime($end1), strtotime($end2));

    if ($start > $end) {
        return 0;
    }

    $start_date = new DateTime(date('Y-m-01', $start));
    $end_date = new DateTime(date('Y-m-01', $end));
    $end_date->modify('+1 month'); // To include the last month if partial

    $interval = $start_date->diff($end_date);
    return ($interval->y * 12) + $interval->m;
}

// Function to calculate UK taxes and NI
function calculate_deductions($annual_gross)
{
    // UK Tax Bands for 2023/2024
    $personal_allowance = 12570;
    $basic_rate_limit = 50270;
    $higher_rate_limit = 150000;

    // Income Tax Calculation
    $taxable_income = max(0, $annual_gross - $personal_allowance);
    $income_tax = 0;

    if ($taxable_income <= ($basic_rate_limit - $personal_allowance)) {
        $income_tax += $taxable_income * 0.20;
    } elseif ($taxable_income <= ($higher_rate_limit - $personal_allowance)) {
        $income_tax += ($basic_rate_limit - $personal_allowance) * 0.20;
        $income_tax += ($taxable_income - ($basic_rate_limit - $personal_allowance)) * 0.40;
    } else {
        $income_tax += ($basic_rate_limit - $personal_allowance) * 0.20;
        $income_tax += ($higher_rate_limit - $personal_allowance - ($basic_rate_limit - $personal_allowance)) * 0.40;
        $income_tax += ($taxable_income - ($higher_rate_limit - $personal_allowance - ($basic_rate_limit - $personal_allowance))) * 0.45;
    }

    // National Insurance Calculation
    $ni = 0;
    if ($annual_gross > 12570) {
        if ($annual_gross <= 50270) {
            $ni += ($annual_gross - 12570) * 0.12;
        } else {
            $ni += (50270 - 12570) * 0.12;
            $ni += ($annual_gross - 50270) * 0.02;
        }
    }

    // Other Deductions (e.g., Pension) - assuming 5%
    $other_deductions = $annual_gross * 0.05;

    // Total Deductions
    $total_deductions = $income_tax + $ni + $other_deductions;

    return [
        'income_tax' => $income_tax,
        'national_insurance' => $ni,
        'other_deductions' => $other_deductions,
        'total_deductions' => $total_deductions
    ];
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form data
    $start_date = isset($_POST['start_date']) ? trim($_POST['start_date']) : '';
    $end_date = isset($_POST['end_date']) ? trim($_POST['end_date']) : '';
    $department_filter = isset($_POST['department']) ? trim($_POST['department']) : '';
    $role_filter = isset($_POST['role']) ? trim($_POST['role']) : '';
    $salary_min = isset($_POST['salary_min']) ? trim($_POST['salary_min']) : '';
    $salary_max = isset($_POST['salary_max']) ? trim($_POST['salary_max']) : '';
    $export_format = isset($_POST['export_format']) ? trim($_POST['export_format']) : '';

    // Validate input
    if (empty($start_date) || empty($end_date)) {
        $message = "Please select both start and end dates.";
    } elseif ($start_date > $end_date) {
        $message = "Start date cannot be later than end date.";
    }

    if (empty($message)) {
        // Build SQL query with necessary joins and filters
        $report_sql = "
            SELECT 
                e.employee_id,
                CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
                d.department_name,
                p.position_title,
                eh.base_salary,
                eh.allowance,
                eh.incentives,
                eh.bonuses,
                eh.date_added,
                eh.date_modified
            FROM employeeHistory eh
            JOIN Employee e ON eh.employee_id = e.employee_id
            JOIN Position p ON e.position_id = p.position_id
            JOIN Department d ON p.department_id = d.department_id
            WHERE eh.date_added <= ?
              AND (eh.date_modified >= ? OR eh.date_modified IS NULL)
        ";

        $params = [$end_date, $start_date];
        $types = "ss";

        // Apply Department Filter
        if (!empty($department_filter)) {
            $report_sql .= " AND d.department_name = ?";
            $params[] = $department_filter;
            $types .= "s";
        }

        // Apply Role Filter
        if (!empty($role_filter)) {
            $report_sql .= " AND p.position_title = ?";
            $params[] = $role_filter;
            $types .= "s";
        }

        // Apply Salary Filters (annual salary)
        if (!empty($salary_min)) {
            if (!is_numeric($salary_min)) {
                $message = "Minimum salary must be a valid number.";
            } else {
                $report_sql .= " AND eh.base_salary >= ?";
                $params[] = $salary_min;
                $types .= "d";
            }
        }

        if (empty($message) && !empty($salary_max)) {
            if (!is_numeric($salary_max)) {
                $message = "Maximum salary must be a valid number.";
            } else {
                $report_sql .= " AND eh.base_salary <= ?";
                $params[] = $salary_max;
                $types .= "d";
            }
        }

        if (empty($message)) {
            // Prepare and execute the SQL statement
            $stmt = $conn->prepare($report_sql);
            if ($stmt) {
                $stmt->bind_param($types, ...$params);
                if ($stmt->execute()) {
                    $result = $stmt->get_result();

                    // Temporary array to hold employee payments
                    $employees = [];

                    while ($row = $result->fetch_assoc()) {
                        $employee_id = $row['employee_id'];
                        $employee_name = $row['employee_name'];
                        $department = $row['department_name'];
                        $position = $row['position_title'];
                        $base_salary = $row['base_salary'];
                        $allowance = $row['allowance'];
                        $incentives = $row['incentives'];
                        $bonuses = $row['bonuses'];
                        $eh_start_date = $row['date_added'];
                        $eh_end_date = $row['date_modified'] ? $row['date_modified'] : $end_date;

                        // Calculate overlapping period
                        $overlap_start = max(strtotime($start_date), strtotime($eh_start_date));
                        $overlap_end = min(strtotime($end_date), strtotime($eh_end_date));

                        if ($overlap_start > $overlap_end) {
                            continue; // No overlap
                        }

                        // Calculate number of overlapping months
                        $overlapping_months = calculate_overlapping_months(date('Y-m-01', $overlap_start), date('Y-m-t', $overlap_end), $start_date, $end_date);

                        if ($overlapping_months <= 0) {
                            continue; // No full month overlap
                        }

                        // Calculate monthly payments
                        $monthly_base = $base_salary / 12;
                        $monthly_total = $monthly_base + $allowance + $incentives + $bonuses;

                        // Total payment for the overlapping period
                        $total_payment = $monthly_total * $overlapping_months;

                        // Aggregate payments per employee
                        if (!isset($employees[$employee_id])) {
                            $employees[$employee_id] = [
                                'employee_id' => $employee_id,
                                'employee_name' => $employee_name,
                                'department' => $department,
                                'position' => $position,
                                'gross_pay' => 0
                            ];
                        }

                        $employees[$employee_id]['gross_pay'] += $total_payment;
                    }

                    // Now, calculate deductions and net pay for each employee
                    foreach ($employees as $emp) {
                        $gross_pay = $emp['gross_pay'];

                        // Calculate deductions based on annualized gross pay
                        // Since gross_pay is over the period, we need to annualize it for tax calculations
                        // Assuming the report period is in months, we'll scale accordingly
                        // For simplicity, we'll calculate deductions proportionally
                        // Alternatively, you can implement detailed tax calculations based on exact income

                        // Determine the number of months in the report period
                        $report_start = new DateTime($start_date);
                        $report_end = new DateTime($end_date);
                        $interval = $report_start->diff($report_end);
                        $report_months = ($interval->y * 12) + $interval->m + 1; // +1 to include the end month

                        // Annualize the gross pay
                        $annual_gross = ($gross_pay / $report_months) * 12;

                        // Calculate deductions
                        $deductions = calculate_deductions($annual_gross);

                        // Pro-rate deductions based on the report period
                        $deductions_pro_rated = [];
                        foreach ($deductions as $key => $value) {
                            $deductions_pro_rated[$key] = ($value / 12) * $report_months;
                        }

                        $net_pay = $gross_pay - $deductions_pro_rated['total_deductions'];

                        // Append to report data
                        $report_data[] = [
                            'employee_id' => $emp['employee_id'],
                            'employee_name' => $emp['employee_name'],
                            'department' => $emp['department'],
                            'position' => $emp['position'],
                            'gross_pay' => $gross_pay,
                            'income_tax' => $deductions_pro_rated['income_tax'],
                            'national_insurance' => $deductions_pro_rated['national_insurance'],
                            'other_deductions' => $deductions_pro_rated['other_deductions'],
                            'total_deductions' => $deductions_pro_rated['total_deductions'],
                            'net_pay' => $net_pay
                        ];
                    }
                } else {
                    $message = "Execute failed: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $message = "Prepare failed: " . $conn->error;
            }
        }
    }
}

// Handle Export to CSV or PDF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($export_format, ['csv', 'pdf']) && !empty($report_data)) {
    if ($export_format === 'csv') {
        // Set headers to initiate file download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=payroll_report_' . $start_date . '_to_' . $end_date . '.csv');

        $output = fopen('php://output', 'w');

        // Header row
        fputcsv($output, ['Employee ID', 'Employee Name', 'Department', 'Position', 'Gross Pay', 'Income Tax', 'National Insurance', 'Other Deductions', 'Total Deductions', 'Net Pay']);

        // Data rows
        foreach ($report_data as $row) {
            fputcsv($output, [
                $row['employee_id'],
                $row['employee_name'],
                $row['department'],
                $row['position'],
                number_format($row['gross_pay'], 2),
                number_format($row['income_tax'], 2),
                number_format($row['national_insurance'], 2),
                number_format($row['other_deductions'], 2),
                number_format($row['total_deductions'], 2),
                number_format($row['net_pay'], 2)
            ]);
        }

        fclose($output);
        exit;
    } elseif ($export_format === 'pdf') {
        // Since external libraries are not allowed, use browser's print functionality
        // Store report data in session
        $_SESSION['report_data'] = $report_data;
        $_SESSION['report_start_date'] = $start_date;
        $_SESSION['report_end_date'] = $end_date;

        // Redirect to PDF view page
        header('Location: payroll_report_pdf.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Report Generation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Highlight summary totals */
        .mt-4 h4 {
            color: #0d6efd;
        }

        .mt-4 p {
            font-size: 1.1em;
        }

        /* Enhance table readability */
        .table th,
        .table td {
            vertical-align: middle;
            text-align: center;
        }

        /* Print Styles */
        @media print {
            .no-print {
                display: none;
            }

            table {
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            thead {
                display: table-header-group;
            }

            tfoot {
                display: table-footer-group;
            }
        }
    </style>

    <!-- Include jsPDF-AutoTable plugin -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>
</head>

<body>
    <?php include '../includes/navbar.php'; // Ensure this file exists and contains your navigation bar ?>

    <div class="container">
        <h1 class="text-center">Payroll Report</h1>
        <?php if (!empty($message)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="POST" class="mt-4">
            <div class="row mb-3">
                <!-- Start Date Selection -->
                <div class="col-md-6">
                    <label for="start_date" class="form-label">Start Date:</label>
                    <input type="date" id="start_date" name="start_date" class="form-control"
                        value="<?php echo htmlspecialchars($start_date); ?>" required>
                </div>

                <!-- End Date Selection -->
                <div class="col-md-6">
                    <label for="end_date" class="form-label">End Date:</label>
                    <input type="date" id="end_date" name="end_date" class="form-control"
                        value="<?php echo htmlspecialchars($end_date); ?>" required>
                </div>
            </div>

            <div class="row mb-3">
                <!-- Department Filter -->
                <div class="col-md-3">
                    <label for="department" class="form-label">Department:</label>
                    <select id="department" name="department" class="form-select">
                        <option value="">-- All Departments --</option>
                        <?php
                        // Fetch departments for the dropdown
                        $dept_sql = "SELECT department_name FROM Department ORDER BY department_name ASC";
                        $dept_result = $conn->query($dept_sql);
                        if ($dept_result && $dept_result->num_rows > 0) {
                            while ($dept = $dept_result->fetch_assoc()) {
                                $selected = ($department_filter === $dept['department_name']) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($dept['department_name']) . "' $selected>" . htmlspecialchars($dept['department_name']) . "</option>";
                            }
                        } else {
                            echo "<option value=''>No Departments Found</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- Role Filter -->
                <div class="col-md-3">
                    <label for="role" class="form-label">Job Title:</label>
                    <select id="role" name="role" class="form-select">
                        <option value="">-- All Roles --</option>
                        <?php
                        // Fetch job titles for the dropdown
                        $role_sql = "SELECT DISTINCT position_title FROM Position ORDER BY position_title ASC";
                        $role_result = $conn->query($role_sql);
                        if ($role_result && $role_result->num_rows > 0) {
                            while ($role = $role_result->fetch_assoc()) {
                                $selected = ($role_filter === $role['position_title']) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($role['position_title']) . "' $selected>" . htmlspecialchars($role['position_title']) . "</option>";
                            }
                        } else {
                            echo "<option value=''>No Roles Found</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- Minimum Salary Filter -->
                <div class="col-md-3">
                    <label for="salary_min" class="form-label">Minimum Salary:</label>
                    <input type="number" step="0.01" id="salary_min" name="salary_min" class="form-control"
                        placeholder="e.g., 30000" value="<?php echo htmlspecialchars($salary_min); ?>">
                </div>

                <!-- Maximum Salary Filter -->
                <div class="col-md-3">
                    <label for="salary_max" class="form-label">Maximum Salary:</label>
                    <input type="number" step="0.01" id="salary_max" name="salary_max" class="form-control"
                        placeholder="e.g., 100000" value="<?php echo htmlspecialchars($salary_max); ?>">
                </div>
            </div>



            <button type="submit" class="btn btn-primary">Generate Report</button>
        </form>

        <?php
        // Calculate Summary Totals
        $total_payroll = 0;
        $total_tax = 0;
        $total_ni = 0;
        $total_other_deductions = 0;
        $total_deductions = 0;
        $total_net_pay = 0;
        $employee_count = count($report_data);

        foreach ($report_data as $row) {
            $total_payroll += $row['gross_pay'];
            $total_tax += $row['income_tax'];
            $total_ni += $row['national_insurance'];
            $total_other_deductions += $row['other_deductions'];
            $total_deductions += $row['total_deductions'];
            $total_net_pay += $row['net_pay'];
        }

        $average_salary = $employee_count > 0 ? $total_payroll / $employee_count : 0;
        ?>

        <?php if (!empty($report_data) && empty($export_format)): ?>
            <h2 class="mt-5">Results</h2>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th onclick="sortTable(0)">Employee ID &#x25B2;&#x25BC;</th>
                        <th onclick="sortTable(1)">Name &#x25B2;&#x25BC;</th>
                        <th onclick="sortTable(2)">Department &#x25B2;&#x25BC;</th>
                        <th onclick="sortTable(3)">Position &#x25B2;&#x25BC;</th>
                        <th onclick="sortTable(4)">Gross Pay &#x25B2;&#x25BC;</th>
                        <th onclick="sortTable(5)">Income Tax &#x25B2;&#x25BC;</th>
                        <th onclick="sortTable(6)">National Insurance &#x25B2;&#x25BC;</th>
                        <th onclick="sortTable(7)">Other Deductions &#x25B2;&#x25BC;</th>
                        <th onclick="sortTable(8)">Total Deductions &#x25B2;&#x25BC;</th>
                        <th onclick="sortTable(9)">Net Pay &#x25B2;&#x25BC;</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Implement pagination
                    $total_records = count($report_data);
                    $total_pages = ceil($total_records / $records_per_page);
                    $report_data_paginated = array_slice($report_data, ($current_page - 1) * $records_per_page, $records_per_page);

                    foreach ($report_data_paginated as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['employee_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['employee_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['department']); ?></td>
                            <td><?php echo htmlspecialchars($row['position']); ?></td>
                            <td>£<?php echo number_format($row['gross_pay'], 2); ?></td>
                            <td>£<?php echo number_format($row['income_tax'], 2); ?></td>
                            <td>£<?php echo number_format($row['national_insurance'], 2); ?></td>
                            <td>£<?php echo number_format($row['other_deductions'], 2); ?></td>
                            <td>£<?php echo number_format($row['total_deductions'], 2); ?></td>
                            <td>£<?php echo number_format($row['net_pay'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Summary Totals -->
            <div class="mt-4">
                <h4>Summary Totals</h4>
                <p><strong>Total Payroll:</strong> £<?php echo number_format($total_payroll, 2); ?></p>
                <p><strong>Total Income Tax:</strong> £<?php echo number_format($total_tax, 2); ?></p>
                <p><strong>Total National Insurance:</strong> £<?php echo number_format($total_ni, 2); ?></p>
                <p><strong>Total Other Deductions:</strong> £<?php echo number_format($total_other_deductions, 2); ?></p>
                <p><strong>Total Deductions:</strong> £<?php echo number_format($total_deductions, 2); ?></p>
                <p><strong>Total Net Pay:</strong> £<?php echo number_format($total_net_pay, 2); ?></p>
                <p><strong>Average Gross Salary:</strong> £<?php echo number_format($average_salary, 2); ?></p>
            </div>

            <!-- Export Buttons -->
            <div class="no-print mt-4">
                <!-- Export to CSV or PDF via Form Submission -->
                <!-- <form method="POST" action="" class="d-inline"> -->
                <!-- <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                    <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                    <input type="hidden" name="department" value="<?php echo htmlspecialchars($department_filter); ?>">
                    <input type="hidden" name="role" value="<?php echo htmlspecialchars($role_filter); ?>">
                    <input type="hidden" name="salary_min" value="<?php echo htmlspecialchars($salary_min); ?>">
                    <input type="hidden" name="salary_max" value="<?php echo htmlspecialchars($salary_max); ?>"> -->
                <button onclick='exportCSV()' class="btn btn-success">Download CSV</button>
                <button onclick='exportPDF()' name='export_format' value='pdf' class="btn btn-danger">Generate PDF</button>
                <!-- </form> -->
            </div>

            <!-- Pagination Links -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation example">
                    <ul class="pagination justify-content-center">
                        <!-- Previous Page Link -->
                        <li class="page-item <?php if ($current_page <= 1)
                            echo 'disabled'; ?>">
                            <a class="page-link" href="?page=<?php echo $current_page - 1; ?>">Previous</a>
                        </li>

                        <!-- Page Number Links -->
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php if ($current_page == $i)
                                echo 'active'; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <!-- Next Page Link -->
                        <li class="page-item <?php if ($current_page >= $total_pages)
                            echo 'disabled'; ?>">
                            <a class="page-link" href="?page=<?php echo $current_page + 1; ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- JavaScript for Sorting -->
    <script>
        let sortDirection = {};

        function sortTable(columnIndex) {
            const table = document.querySelector("table");
            const tbody = table.tBodies[0];
            const rows = Array.from(tbody.querySelectorAll("tr"));
            const isNumeric = columnIndex === 0 || (columnIndex >= 4 && columnIndex <= 9); // Include Employee ID and financial columns as numeric

            // Toggle sort direction
            sortDirection[columnIndex] = !sortDirection[columnIndex];

            rows.sort((a, b) => {
                let cellA = a.children[columnIndex].innerText;
                let cellB = b.children[columnIndex].innerText;

                if (isNumeric) {
                    cellA = parseFloat(cellA.replace(/[^0-9.-]+/g, "")) || 0;
                    cellB = parseFloat(cellB.replace(/[^0-9.-]+/g, "")) || 0;
                } else {
                    cellA = cellA.toLowerCase();
                    cellB = cellB.toLowerCase();
                }

                if (cellA < cellB) return sortDirection[columnIndex] ? -1 : 1;
                if (cellA > cellB) return sortDirection[columnIndex] ? 1 : -1;
                return 0;
            });

            // Reattach sorted rows
            tbody.innerHTML = "";
            rows.forEach(row => tbody.appendChild(row));
        }
    </script>

    <!-- JavaScript for PDF Export using jsPDF and AutoTable -->
    <script>
        function exportCSV() {
            var reportData = <?php echo json_encode($report_data); ?>;

            if (!reportData || reportData.length === 0) {
                alert("No data available to export.");
                return;
            }

            var csvContent = "data:text/csv;charset=utf-8,";
            csvContent += 'Employee ID,Employee Name,Department,Position,Gross Pay,Income Tax,National Insurance,Other Deductions,Total Deductions,Net Pay\n';
            reportData.forEach(function (row) {
                var dataString = [
                    row['employee_id'] || '',
                    '"' + (row['employee_name'] || '') + '"',
                    '"' + (row['department'] || '') + '"',
                    '"' + (row['position'] || '') + '"',
                    parseFloat(row['gross_pay'] || 0).toFixed(2),
                    parseFloat(row['income_tax'] || 0).toFixed(2),
                    parseFloat(row['national_insurance'] || 0).toFixed(2),
                    parseFloat(row['other_deductions'] || 0).toFixed(2),
                    parseFloat(row['total_deductions'] || 0).toFixed(2),
                    parseFloat(row['net_pay'] || 0).toFixed(2)
                ].join(',');
                csvContent += dataString + "\n";
            });
            var encodedUri = encodeURI(csvContent);
            var link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "payroll_report_<?php echo $start_date; ?>_to_<?php echo $end_date; ?>.csv");
            document.body.appendChild(link); // Required for Firefox
            link.click();
            document.body.removeChild(link);
        }

        function exportPDF() {
            var reportData = <?php echo json_encode($report_data); ?>;

            if (!reportData || reportData.length === 0) {
                alert("No data available to export.");
                return;
            }

            // Create a new jsPDF instance
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            // Prepare the data for the autoTable
            const tableColumn = ["Employee ID", "Employee Name", "Department", "Position", "Gross Pay", "Income Tax", "National Insurance", "Other Deductions", "Total Deductions", "Net Pay"];
            const tableRows = [];

            reportData.forEach(row => {
                const rowData = [
                    row['employee_id'],
                    row['employee_name'],
                    row['department'],
                    row['position'],
                    "£" + parseFloat(row['gross_pay']).toFixed(2),
                    "£" + parseFloat(row['income_tax']).toFixed(2),
                    "£" + parseFloat(row['national_insurance']).toFixed(2),
                    "£" + parseFloat(row['other_deductions']).toFixed(2),
                    "£" + parseFloat(row['total_deductions']).toFixed(2),
                    "£" + parseFloat(row['net_pay']).toFixed(2)
                ];
                tableRows.push(rowData);
            });

            // Add title
            doc.setFontSize(14);
            doc.text("Payroll Report", 14, 15);
            doc.setFontSize(11);
            doc.text("Report Period: <?php echo htmlspecialchars($start_date); ?> to <?php echo htmlspecialchars($end_date); ?>", 14, 22);

            // Add autoTable
            doc.autoTable({
                startY: 25,
                head: [tableColumn],
                body: tableRows,
                theme: 'grid',
                headStyles: { fillColor: [22, 160, 133] },
                styles: { fontSize: 8 },
                margin: { top: 25 }
            });

            // Add summary totals at the end
            const finalY = doc.lastAutoTable.finalY || 25;
            doc.setFontSize(11);
            doc.text(`Total Payroll: £${parseFloat(<?php echo $total_payroll; ?>).toFixed(2)}`, 14, finalY + 10);
            doc.text(`Total Income Tax: £${parseFloat(<?php echo $total_tax; ?>).toFixed(2)}`, 14, finalY + 16);
            doc.text(`Total National Insurance: £${parseFloat(<?php echo $total_ni; ?>).toFixed(2)}`, 14, finalY + 22);
            doc.text(`Total Other Deductions: £${parseFloat(<?php echo $total_other_deductions; ?>).toFixed(2)}`, 14, finalY + 28);
            doc.text(`Total Deductions: £${parseFloat(<?php echo $total_deductions; ?>).toFixed(2)}`, 14, finalY + 34);
            doc.text(`Total Net Pay: £${parseFloat(<?php echo $total_net_pay; ?>).toFixed(2)}`, 14, finalY + 40);

            // Save the PDF
            doc.save(`payroll_report_<?php echo $start_date; ?>_to_<?php echo $end_date; ?>.pdf`);
        }
    </script>
</body>

</html>