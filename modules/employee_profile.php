<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// Correct the include path (assuming the correct file is dp_connect.php)
include "../includes/dp_connect.php";

// Check if the user is logged in and has the required role
if (!isset($_SESSION['employee_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['executive', 'jarvis'])) {
    // Redirect to login page or show an error
    header('Location: ../login.php');
    exit;
}

// Check if the connection was successful
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Validate the presence of employee_id in the URL
if (!isset($_GET['employee_id'])) {
    die("Employee ID is missing.");
}

$employee_id = intval($_GET['employee_id']);

// Fetch employee details
$employee_sql = "SELECT e.*, p.position_title, l.location_name, d.department_name
                 FROM Employee e
                 LEFT JOIN Position p ON e.position_id = p.position_id
                 LEFT JOIN Department d ON p.department_id = d.department_id
                 LEFT JOIN Location l ON e.office_location_id = l.location_id
                 WHERE e.employee_id = ? AND e.is_deleted = 0";

$stmt = $conn->prepare($employee_sql);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $employee_id);

if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Employee not found.");
}

$employee = $result->fetch_assoc();

// Fetch Emergency Contact Details
$contact_sql = "SELECT contact_id, first_name, last_name, relationship, phone
               FROM EmergencyContact 
               WHERE employee_id = ?";

$stmt_contact = $conn->prepare($contact_sql);

if (!$stmt_contact) {
    die("Prepare failed for emergency contacts: " . $conn->error);
}

$stmt_contact->bind_param("i", $employee_id);

if (!$stmt_contact->execute()) {
    die("Execute failed for emergency contacts: " . $stmt_contact->error);
}

$contact_result = $stmt_contact->get_result();

$emergency_contacts = [];
if ($contact_result->num_rows > 0) {
    while ($contact = $contact_result->fetch_assoc()) {
        // Concatenate first and last name to form full name
        $contact['full_name'] = $contact['first_name'] . ' ' . $contact['last_name'];
        $emergency_contacts[] = $contact;
    }
}

$stmt_contact->close();

// Fetch employment history
$history_sql = "SELECT * FROM employeeHistory WHERE employee_id = ?";
$stmt_history = $conn->prepare($history_sql);

if (!$stmt_history) {
    die("Prepare failed for history: " . $conn->error);
}

$stmt_history->bind_param("i", $employee_id);

if (!$stmt_history->execute()) {
    die("Execute failed for history: " . $stmt_history->error);
}

$history_result = $stmt_history->get_result();

$stmt->close();
$stmt_history->close();




$employee_id = intval($_GET['employee_id']);

// Fetch employee financial history
$history_sql = "SELECT * FROM employeeHistory WHERE employee_id = ? ORDER BY date_added DESC";
$stmt = $conn->prepare($history_sql);
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$history_result = $stmt->get_result();
$employee_history = [];
while ($row = $history_result->fetch_assoc()) {
    $employee_history[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Profile - <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
    </title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .profile-container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .profile-container h1 {
            margin-bottom: 20px;
        }

        .profile-section {
            margin-bottom: 30px;
        }

        .employment-history ul {
            list-style-type: none;
            padding-left: 0;
        }

        .employment-history li {
            background-color: #f1f1f1;
            margin-bottom: 10px;
            padding: 15px;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <?php include '../includes/navbar.php'; // Ensure this file exists and contains your navbar ?>

    <div class="container my-5">
        <div class="profile-container">
            <h1 class="text-center">Employee Profile:
                <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
            </h1>
            <img src="../uploads/<?php echo $employee["profile_picture"] ? $employee["profile_picture"] : '../uploads/blank.png' ?> "
                width="200px" alt="Profile Picture" class="img-thumbnail">
            <!-- Contact Details Section -->
            <div class="profile-section">
                <h2>Contact Details</h2>
                <table class="table table-bordered">
                    <tr>
                        <th>Email</th>
                        <td><?php echo htmlspecialchars($employee['email_address']); ?></td>
                    </tr>
                    <tr>
                        <th>Address</th>
                        <td><?php echo htmlspecialchars($employee['home_street_address'] . ', ' . $employee['home_city']); ?>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Employment Details Section -->
            <div class="profile-section">
                <h2>Employment Details</h2>
                <table class="table table-bordered">
                    <tr>
                        <th>Position</th>
                        <td><?php echo htmlspecialchars($employee['position_title']); ?></td>
                    </tr>
                    <tr>
                        <th>Department</th>
                        <td><?php echo htmlspecialchars($employee['department_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Office Location</th>
                        <td><?php echo htmlspecialchars($employee['location_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Hire Date</th>
                        <td><?php echo htmlspecialchars($employee['hire_date']); ?></td>
                    </tr>
                    <tr>
                        <th>Employment Contract Type</th>
                        <td><?php echo htmlspecialchars($employee['employment_contract_type']); ?></td>
                    </tr>
                </table>
            </div>

            <!-- Emergency Contact Details Section -->
            <div class="profile-section">
                <h2>Emergency Contact Details</h2>
                <?php if (!empty($emergency_contacts)): ?>
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Relationship</th>
                                <th>Phone Number</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($emergency_contacts as $contact): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($contact['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($contact['relationship']); ?></td>
                                    <td><?php echo htmlspecialchars($contact['phone']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No emergency contact details available.</p>
                <?php endif; ?>
            </div>

            <!-- Employment History Section -->
            <div class="profile-section employment-history">
                <h2>Employee Financial History</h2>
                <?php if (!empty($employee_history)): ?>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Base Salary</th>
                                <th>Allowance</th>
                                <th>Incentives</th>
                                <th>Bonus</th>
                                <th>Date Added</th>
                                <th>Date Modified</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employee_history as $history): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($history['id']); ?></td>
                                    <td><?php echo htmlspecialchars($history['base_salary']); ?></td>
                                    <td><?php echo htmlspecialchars($history['allowance']); ?></td>
                                    <td><?php echo htmlspecialchars($history['incentives']); ?></td>
                                    <td><?php echo htmlspecialchars($history['bonuses'] ?? 0); ?></td>
                                    <td><?php echo htmlspecialchars($history['date_added']); ?></td>
                                    <td><?php echo htmlspecialchars($history['date_modified'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No financial history available for this employee.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
<?php
// Close the database connection
if (isset($conn)) {
    $conn->close();
}
?>