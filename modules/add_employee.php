<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session for access restriction
session_start();

// Check if the user is logged in and has the required role
if (!isset($_SESSION['employee_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['executive', 'jarvis'])) {
    // Redirect to login page
    header('Location: ../login.php');
    exit;
}

// Include database connection
include "../includes/dp_connect.php";

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = htmlspecialchars(trim($_POST['first_name']));
    $last_name = htmlspecialchars(trim($_POST['last_name']));
    $position_id = intval($_POST['position_id']);
    $office_location_id = intval($_POST['office_location_id']);
    $salary = floatval($_POST['salary']);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $date_of_birth = $_POST['date_of_birth'];
    $home_street_address = htmlspecialchars(trim($_POST['home_street_address']));
    $home_city = htmlspecialchars(trim($_POST['home_city']));
    $hire_date = $_POST['hire_date'];
    $contract_type = htmlspecialchars(trim($_POST['contract_type']));
    $national_insurance_number = htmlspecialchars(trim($_POST['national_insurance_number']));
    $allowance = floatval($_POST['allowance']);
    $incentives = floatval($_POST['incentives']);
    $bonuses = floatval($_POST['bonuses']);

    $username = htmlspecialchars(trim($_POST['username']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate required fields
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $message = "<div class='alert alert-danger' role='alert'>Username and Password fields are required.</div>";
    } elseif ($password !== $confirm_password) {
        $message = "<div class='alert alert-danger' role='alert'>Passwords do not match.</div>";
    } else {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        $conn->begin_transaction();

        try {
            // Insert into Employee table
            $stmt = $conn->prepare("INSERT INTO Employee (
                first_name, last_name, position_id, office_location_id, 
                salary_amount, email_address, date_of_birth, home_street_address,
                home_city, hire_date, employment_contract_type, national_insurance_number
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            if ($stmt) {
                $stmt->bind_param(
                    "ssiidsssssss",
                    $first_name,
                    $last_name,
                    $position_id,
                    $office_location_id,
                    $salary,
                    $email,
                    $date_of_birth,
                    $home_street_address,
                    $home_city,
                    $hire_date,
                    $contract_type,
                    $national_insurance_number
                );

                if ($stmt->execute()) {
                    // Get the inserted employee_id
                    $employee_id = $conn->insert_id;
                    $stmt->close();

                    // Insert into Users table
                    $role = 'employee'; // Default role
                    $created_at = date('Y-m-d H:i:s');

                    $stmt_user = $conn->prepare("INSERT INTO Users (
                        employee_id, username, password, role, created_at
                    ) VALUES (?, ?, ?, ?, ?)");

                    if ($stmt_user) {
                        $stmt_user->bind_param(
                            "issss",
                            $employee_id,
                            $username,
                            $hashed_password,
                            $role,
                            $created_at
                        );

                        if ($stmt_user->execute()) {

                            $conn->commit();

                            // Add the data to the employee history
                            $query = "INSERT INTO EmployeeHistory (employee_id, base_salary, allowance, incentives,bonuses, date_added) VALUES (?,?,?,?,?,NOW())";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("idddd", $employee_id, $salary, $allowance, $incentives, $bonuses);
                            $stmt->execute();
                            $stmt->close();

                            $message = "<div class='alert alert-success' role='alert'>Employee and user account added successfully!</div>";
                        } else {
                            throw new Exception("Error creating user account: " . $stmt_user->error);
                        }

                        $stmt_user->close();
                    } else {
                        throw new Exception("Prepare failed for Users table: " . $conn->error);
                    }
                } else {
                    throw new Exception("Error adding employee: " . $stmt->error);
                }
            } else {
                throw new Exception("Prepare failed for Employee table: " . $conn->error);
            }
        } catch (Exception $e) {
            $conn->rollback();
            $message = "<div class='alert alert-danger' role='alert'>" . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }

    //check if file is uploaded
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file_name = $_FILES['profile_picture']['name'];
        $file_tmp = $_FILES['profile_picture']['tmp_name'];
        $file_size = $_FILES['profile_picture']['size'];
        $file_error = $_FILES['profile_picture']['error'];
        $file_type = $_FILES['profile_picture']['type'];

        // Get the file extension
        $file_ext = explode('.', $file_name);
        $file_ext = strtolower(end($file_ext));

        // Allowed extensions
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($file_ext, $allowed)) {
            if ($file_error === 0) {
                if ($file_size <= 2097152) {
                    $file_name_new = uniqid('', true) . '.' . $file_ext;
                    $file_destination = '../uploads/' . $file_name_new;

                    if (move_uploaded_file($file_tmp, $file_destination)) {
                        //Update in the database
                        $stmt = $conn->prepare("UPDATE Employee SET profile_picture = ? WHERE employee_id = ?");
                        $stmt->bind_param("si", $file_destination, $employee_id);
                        $stmt->execute();

                        $message .= "<div class='alert alert-success' role='alert'>File uploaded successfully.</div>";
                    } else {
                        $message .= "<div class='alert alert-danger' role='alert'>Error uploading file.</div>";
                    }
                } else {
                    $message .= "<div class='alert alert-danger' role='alert'>File size exceeds the limit.</div>";
                }
            } else {
                $message .= "<div class='alert alert-danger' role='alert'>Error uploading file.</div>";
            }
        } else {
            $message .= "<div class='alert alert-danger' role='alert'>Invalid file type.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Add Employee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-5">
        <h1 class="mb-4">Add Employee</h1>
        <?php echo $message; ?>
        <form method="POST" class="row g-3" enctype='multipart/form-data'>
            <div class="col-md-6">
                <label class="form-label">First Name:</label>
                <input type="text" name="first_name" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Last Name:</label>
                <input type="text" name="last_name" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Position:</label>
                <select name="position_id" class="form-select" required>
                    <option value="">-- Select Position --</option>
                    <?php
                    // Fetch all positions for the dropdown
                    $positions = $conn->query("SELECT position_id, position_title FROM Position");
                    while ($row = $positions->fetch_assoc()) {
                        echo "<option value='{$row['position_id']}'>{$row['position_title']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Office Location:</label>
                <select name="office_location_id" class="form-select" required>
                    <option value="">-- Select Office Location --</option>
                    <?php
                    // Fetch all office locations for the dropdown
                    $locations = $conn->query("SELECT location_id, location_name FROM Location");
                    while ($row = $locations->fetch_assoc()) {
                        echo "<option value='{$row['location_id']}'>{$row['location_name']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Salary:</label>
                <input type="number" name="salary" step="0.01" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Allowance:</label>
                <input type="number" name="allowance" step="0.01" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Incentives:</label>
                <input type="number" name="incentives" step="0.01" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Bonuses:</label>
                <input type="number" name="bonuses" step="0.01" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Email:</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Date of Birth:</label>
                <input type="date" name="date_of_birth" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Home Street Address:</label>
                <input type="text" name="home_street_address" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Home City:</label>
                <input type="text" name="home_city" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Hire Date:</label>
                <input type="date" name="hire_date" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Employment Contract Type:</label>
                <select name="contract_type" class="form-select" required>
                    <option value="Full-Time">Full-Time</option>
                    <option value="Part-Time">Part-Time</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">National Insurance Number:</label>
                <input type="text" name="national_insurance_number" pattern="^[A-Z]{2}[0-9]{6}[A-Z]$"
                    title="Format: 2 letters, 6 numbers, 1 letter" class="form-control" required>
            </div>

            <!-- Create User Fields -->
            <div class="col-md-6">
                <label class="form-label">Username:</label>
                <input type="text" name="username" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Password:</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Confirm Password:</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>

            <div class="col-12">
                <label class="form-label">Profile Picture:</label>
                <input type="file" name="profile_picture" class="form-control">
            </div>

            <!-- Submit Button -->
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Add Employee</button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>