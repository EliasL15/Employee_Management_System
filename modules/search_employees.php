<?php
include "../includes/dp_connect.php";
session_start();

// Check if the user is logged in and has the required role
if (!isset($_SESSION['employee_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['executive', 'jarvis'])) {
    // Redirect to login page or show an error
    header('Location: ../login.php');
    exit;
}

// Get search parameters and sanitize them
$name = isset($_GET['name']) ? "%" . $_GET['name'] . "%" : "%";
$department = isset($_GET['department']) ? "%" . $_GET['department'] . "%" : "%";
$position = isset($_GET['position']) ? "%" . $_GET['position'] . "%" : "%";
$location = isset($_GET['location']) ? "%" . $_GET['location'] . "%" : "%";
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : ""; // Adjusted to handle empty dates

// Prepare the SQL query with the fixed logic
$sql = "SELECT e.employee_id, e.first_name, e.last_name, e.email_address, e.salary_amount, e.office_location_id, 
               e.date_of_birth, e.home_street_address, e.home_city, e.hire_date, e.employment_contract_type, 
               e.national_insurance_number, p.position_title, d.department_name, l.location_name 
        FROM Employee e
        LEFT JOIN Position p ON e.position_id = p.position_id
        LEFT JOIN Department d ON p.department_id = d.department_id
        LEFT JOIN Location l ON e.office_location_id = l.location_id
        WHERE e.first_name LIKE ? 
          AND d.department_name LIKE ? 
          AND p.position_title LIKE ? 
          AND l.location_name LIKE ?
          AND e.is_deleted = 0 ";

// If a start date is provided, modify the query to include it in the WHERE clause
if (!empty($startDate)) {
    // Assume that startDate is a valid date format 'YYYY-MM-DD'
    $sql .= " AND e.hire_date >= ?";
}

// Prepare the statement
$stmt = $conn->prepare($sql);

// If a start date is provided, bind it
if (!empty($startDate)) {
    $stmt->bind_param("sssss", $name, $department, $position, $location, $startDate);
} else {
    // Bind the parameters without the start date if it's not provided
    $stmt->bind_param("ssss", $name, $department, $position, $location);
}

$stmt->execute();
$result = $stmt->get_result();

$employees = [];
while ($row = $result->fetch_assoc()) {
    $employees[] = $row;
}

// Return the result as JSON
echo json_encode($employees);

$stmt->close();
?>