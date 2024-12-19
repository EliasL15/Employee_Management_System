<?php
include "../includes/dp_connect.php";
session_start();

// Check if the user is logged in and has the required role
if (!isset($_SESSION['employee_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['executive', 'jarvis'])) {
    // Redirect to login page or show an error
    header('Location: ../login.php');
    exit;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch employee details
    if (!isset($_GET['employee_id'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Employee ID not provided']);
        exit;
    }

    $employee_id = intval($_GET['employee_id']);

    // Join Employee with Position to get position title
    $sql = "SELECT 
                e.employee_id, 
                e.first_name, 
                e.last_name, 
                e.email_address, 
                e.salary_amount, 
                e.office_location_id, 
                e.date_of_birth, 
                e.home_street_address, 
                e.home_city, 
                e.hire_date, 
                e.employment_contract_type, 
                e.national_insurance_number,
                e.profile_picture,
                p.position_id, 
                p.position_title
            FROM 
                Employee e
            LEFT JOIN 
                Position p 
            ON 
                e.position_id = p.position_id
            WHERE 
                e.employee_id = ? AND e.is_deleted = 0";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500); // Internal Server Error
        echo json_encode(['error' => 'Database prepare failed: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param("i", $employee_id);
    if (!$stmt->execute()) {
        http_response_code(500); // Internal Server Error
        echo json_encode(['error' => 'Database execute failed: ' . $stmt->error]);
        exit;
    }
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404); // Not Found
        echo json_encode(['error' => 'Employee not found']);
        exit;
    }

    $employee = $result->fetch_assoc();

    // Fetch Emergency Contact Details
    $contact_sql = "SELECT 
                        contact_id, 
                        first_name, 
                        last_name, 
                        relationship, 
                        phone 
                   FROM 
                        EmergencyContact 
                   WHERE 
                        employee_id = ?";

    $stmt_contact = $conn->prepare($contact_sql);
    if (!$stmt_contact) {
        http_response_code(500); // Internal Server Error
        echo json_encode(['error' => 'Database prepare failed for emergency contacts: ' . $conn->error]);
        exit;
    }
    $stmt_contact->bind_param("i", $employee_id);
    if (!$stmt_contact->execute()) {
        http_response_code(500); // Internal Server Error
        echo json_encode(['error' => 'Database execute failed for emergency contacts: ' . $stmt_contact->error]);
        exit;
    }
    $contact_result = $stmt_contact->get_result();

    $emergency_contacts = [];
    if ($contact_result->num_rows > 0) {
        while ($contact = $contact_result->fetch_assoc()) {
            $emergency_contacts[] = $contact;
        }
    }

    $stmt_contact->close();

    // Fetch Employee History
    $history_sql = "SELECT      
                        employee_id, 
                        base_salary, 
                        allowance, 
                        incentives,
                        bonuses, 
                        date_added, 
                        date_modified 
                    FROM 
                        employeeHistory 
                    WHERE 
                        employee_id = ? and date_modified is NULL
                    ORDER BY 
                        date_added DESC";

    $stmt_history = $conn->prepare($history_sql);
    if (!$stmt_history) {
        http_response_code(500); // Internal Server Error
        echo json_encode(['error' => 'Database prepare failed for employee history: ' . $conn->error]);
        exit;
    }
    $stmt_history->bind_param("i", $employee_id);
    if (!$stmt_history->execute()) {
        http_response_code(500); // Internal Server Error
        echo json_encode(['error' => 'Database execute failed for employee history: ' . $stmt_history->error]);
        exit;
    }
    $history_result = $stmt_history->get_result();

    $employee_history = [];
    if ($history_result->num_rows > 0) {
        while ($history = $history_result->fetch_assoc()) {
            $employee_history[] = $history;
        }
    }

    $stmt_history->close();
    $stmt->close();

    $employee['history'] = $employee_history;
    // Structure the JSON response
    $response = [
        'employee' => $employee,
        'emergency_contacts' => $emergency_contacts
    ];

    echo json_encode($response);

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update employee details
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['employee_id'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Employee ID not provided for update']);
        exit;
    }

    $employee_id = intval($data['employee_id']);
    $position_id = isset($data['position_id']) ? intval($data['position_id']) : null;
    $office_location_id = isset($data['office_location_id']) ? intval($data['office_location_id']) : null;
    $first_name = isset($data['first_name']) ? $data['first_name'] : null;
    $last_name = isset($data['last_name']) ? $data['last_name'] : null;
    $email_address = isset($data['email_address']) ? $data['email_address'] : null;
    $salary_amount = isset($data['salary_amount']) ? floatval($data['salary_amount']) : null;
    $date_of_birth = isset($data['date_of_birth']) ? $data['date_of_birth'] : null;
    $home_street_address = isset($data['home_street_address']) ? $data['home_street_address'] : null;
    $home_city = isset($data['home_city']) ? $data['home_city'] : null;
    $hire_date = isset($data['hire_date']) ? $data['hire_date'] : null;
    $employment_contract_type = isset($data['employment_contract_type']) ? $data['employment_contract_type'] : null;
    $national_insurance_number = isset($data['national_insurance_number']) ? $data['national_insurance_number'] : null;
    $profile_picture = isset($data['profile_picture']) ? $data['profile_picture'] : '../uploads/blank.png';

    // Prepare the UPDATE statement with dynamic fields
    $update_fields = [];
    $params = [];
    $types = '';

    if (!is_null($position_id)) {
        $update_fields[] = 'position_id = ?';
        $params[] = $position_id;
        $types .= 'i';
    }
    if (!is_null($office_location_id)) {
        $update_fields[] = 'office_location_id = ?';
        $params[] = $office_location_id;
        $types .= 'i';
    }
    if (!is_null($first_name)) {
        $update_fields[] = 'first_name = ?';
        $params[] = $first_name;
        $types .= 's';
    }
    if (!is_null($last_name)) {
        $update_fields[] = 'last_name = ?';
        $params[] = $last_name;
        $types .= 's';
    }
    if (!is_null($email_address)) {
        $update_fields[] = 'email_address = ?';
        $params[] = $email_address;
        $types .= 's';
    }
    if (!is_null($salary_amount)) {
        $update_fields[] = 'salary_amount = ?';
        $params[] = $salary_amount;
        $types .= 'd';
    }
    if (!is_null($date_of_birth)) {
        $update_fields[] = 'date_of_birth = ?';
        $params[] = $date_of_birth;
        $types .= 's';
    }
    if (!is_null($home_street_address)) {
        $update_fields[] = 'home_street_address = ?';
        $params[] = $home_street_address;
        $types .= 's';
    }
    if (!is_null($home_city)) {
        $update_fields[] = 'home_city = ?';
        $params[] = $home_city;
        $types .= 's';
    }
    if (!is_null($hire_date)) {
        $update_fields[] = 'hire_date = ?';
        $params[] = $hire_date;
        $types .= 's';
    }
    if (!is_null($employment_contract_type)) {
        $update_fields[] = 'employment_contract_type = ?';
        $params[] = $employment_contract_type;
        $types .= 's';
    }
    if (!is_null($national_insurance_number)) {
        $update_fields[] = 'national_insurance_number = ?';
        $params[] = $national_insurance_number;
        $types .= 's';
    }

    if (!is_null($profile_picture)) {
        $update_fields[] = 'profile_picture = ?';
        $params[] = $profile_picture;
        $types .= 's';
    }

    if (empty($update_fields)) {
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'No fields provided for update']);
        exit;
    }

    $update_sql = "UPDATE Employee SET " . implode(', ', $update_fields) . " WHERE employee_id = ?";
    $stmt = $conn->prepare($update_sql);

    if (!$stmt) {
        http_response_code(500); // Internal Server Error
        echo json_encode(['error' => 'Database prepare failed for update: ' . $conn->error]);
        exit;
    }

    // Bind parameters dynamically
    $types .= 'i'; // for employee_id
    $params[] = $employee_id;

    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Employee updated successfully']);
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(['error' => 'Failed to update employee: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Unsupported HTTP method']);
}
?>