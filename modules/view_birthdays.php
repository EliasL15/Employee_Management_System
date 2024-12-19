<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();

// Role-Based Access Control
if (!isset($_SESSION['employee_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['executive', 'jarvis'])) {
    // Redirect unauthorized users to login or an error page
    header('Location: unauthorized.php'); // Create an unauthorized.php page as needed
    exit;
}

// Include database connection
include "../includes/dp_connect.php";

// Check if the request is an AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Initialize variables
$birthdays = [];
$selected_month = date('m'); // Default to current month

if ($isAjax) {
    // Handle AJAX request
    if (isset($_POST['month'])) {
        $selected_month = intval($_POST['month']);
        if ($selected_month < 1 || $selected_month > 12) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid month selected.']);
            exit;
        }

        // Prepare SQL query
        $sql = "
            SELECT 
                employee_id,
                first_name,
                last_name,
                DATE_FORMAT(date_of_birth, '%Y-%m-%d') AS date_of_birth,
                email_address,
                home_city
            FROM 
                Employee
            WHERE 
                MONTH(date_of_birth) = ? AND is_deleted = 0
            ORDER BY 
                DAY(date_of_birth) ASC
        ";

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $selected_month);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $birthdays[] = $row;
            }

            $stmt->close();
            echo json_encode(['status' => 'success', 'data' => $birthdays]);
            exit;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . htmlspecialchars($conn->error)]);
            exit;
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Month not provided.']);
        exit;
    }
}

// Handle standard page load
$message = "";

// Handle form submission (initial load)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['month'])) {
        $selected_month = intval($_POST['month']);
        if ($selected_month < 1 || $selected_month > 12) {
            $message = "<div class='alert alert-danger' role='alert'>Invalid month selected.</div>";
        }
    }
}

// Fetch birthdays for initial page load
$sql = "
    SELECT 
        employee_id,
        first_name,
        last_name,
        DATE_FORMAT(date_of_birth, '%Y-%m-%d') AS date_of_birth,
        email_address,
        home_city
    FROM 
        Employee
    WHERE 
        MONTH(date_of_birth) = ? AND is_deleted = 0
    ORDER BY 
        DAY(date_of_birth) ASC
";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $selected_month);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $birthdays[] = $row;
    }

    $stmt->close();
} else {
    $message = "<div class='alert alert-danger' role='alert'>Prepare failed: " . htmlspecialchars($conn->error) . "</div>";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Birthdays</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .birthday-container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .birthday-table th,
        .birthday-table td {
            vertical-align: middle;
        }
    </style>
</head>

<body>
    <!-- Navigation Bar -->
    <?php include '../includes/navbar.php'; // Create a navbar.php with links as needed ?>

    <div class="container my-5">
        <div class="birthday-container">
            <h2 class="mb-4">View Employee Birthdays</h2>
            <?php echo $message; ?>

            <form id="monthForm" class="row g-3 mb-4">
                <div class="col-md-4">
                    <label for="month" class="form-label">Select Month:</label>
                    <select id="month" name="month" class="form-select" required>
                        <option value="">-- Select Month --</option>
                        <?php
                        for ($m = 1; $m <= 12; $m++) {
                            $monthName = date('F', mktime(0, 0, 0, $m, 10));
                            $selected = ($m == $selected_month) ? 'selected' : '';
                            echo "<option value='$m' $selected>$monthName</option>";
                        }
                        ?>
                    </select>
                </div>
            </form>

            <div id="birthdayResults">
                <?php if (!empty($birthdays)): ?>
                    <h4>Employees with Birthdays in <?php echo date('F', mktime(0, 0, 0, $selected_month, 10)); ?>:</h4>
                    <table class="table table-bordered birthday-table">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Employee ID</th>
                                <th>Name</th>
                                <th>Date of Birth</th>
                                <th>Email</th>
                                <th>City</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($birthdays as $index => $employee): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($employee['employee_id']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($employee['date_of_birth']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['email_address']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['home_city']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                    <div class="alert alert-info" role="alert">
                        No employees have birthdays in the selected month.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS for AJAX -->
    <script>
        document.getElementById('month').addEventListener('change', function () {
            const selectedMonth = this.value;
            if (selectedMonth === '') {
                document.getElementById('birthdayResults').innerHTML = '';
                return;
            }

            const formData = new FormData();
            formData.append('month', selectedMonth);

            fetch('view_birthdays.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        if (data.data.length > 0) {
                            let tableHTML = `<h4>Employees with Birthdays in ${new Date(0, selectedMonth - 1).toLocaleString('default', { month: 'long' })}:</h4>`;
                            tableHTML += `
                            <table class="table table-bordered birthday-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Employee ID</th>
                                        <th>Name</th>
                                        <th>Date of Birth</th>
                                        <th>Email</th>
                                        <th>City</th>
                                    </tr>
                                </thead>
                                <tbody>
                        `;
                            data.data.forEach((employee, index) => {
                                tableHTML += `
                                <tr>
                                    <td>${index + 1}</td>
                                    <td>${employee.employee_id}</td>
                                    <td>${employee.first_name} ${employee.last_name}</td>
                                    <td>${employee.date_of_birth}</td>
                                    <td>${employee.email_address}</td>
                                    <td>${employee.home_city}</td>
                                </tr>
                            `;
                            });
                            tableHTML += `
                                </tbody>
                            </table>
                        `;
                            document.getElementById('birthdayResults').innerHTML = tableHTML;
                        } else {
                            document.getElementById('birthdayResults').innerHTML = `
                            <div class='alert alert-info' role='alert'>
                                No employees have birthdays in the selected month.
                            </div>
                        `;
                        }
                    } else {
                        document.getElementById('birthdayResults').innerHTML = `
                        <div class='alert alert-danger' role='alert'>
                            ${data.message}
                        </div>
                    `;
                    }
                })
                .catch(error => {
                    console.error('Error fetching birthdays:', error);
                    document.getElementById('birthdayResults').innerHTML = `
                    <div class='alert alert-danger' role='alert'>
                        An error occurred while fetching birthdays.
                    </div>
                `;
                });
        });
    </script>
</body>

</html>