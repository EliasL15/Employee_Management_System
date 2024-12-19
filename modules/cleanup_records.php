<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Include database connection
include "../includes/dp_connect.php";

// Check if the user is logged in and has the required role
if (!isset($_SESSION['employee_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['executive', 'jarvis'])) {
    header('Location: ../login.php');
    exit;
}

// Set the @admin_id session variable
$admin_id = $_SESSION['employee_id'];
$conn->query("SET @admin_id = $admin_id");

// Function to clean up deleted records
function cleanupDeletedRecords($conn)
{
    // Start transaction
    $conn->begin_transaction();

    try {
        // Get employee IDs of deleted employees older than 3 years
        $employeeIdsQuery = "
            SELECT employee_id FROM Employee WHERE is_deleted = 1 AND deleted_at <= DATE_SUB(NOW(), INTERVAL 3 YEAR)
        ";
        $result = $conn->query($employeeIdsQuery);

        if ($result && $result->num_rows > 0) {
            $employeeIds = [];
            while ($row = $result->fetch_assoc()) {
                $employeeIds[] = $row['employee_id'];
            }
            $employeeIdsList = implode(',', $employeeIds);

            // Update Employee table
            $employee_query = "
                UPDATE Employee
                SET 
                    position_id = NULL,
                    office_location_id = NULL,
                    first_name = '',
                    last_name = '',
                    salary_amount = NULL,
                    email_address = '',
                    date_of_birth = NULL,
                    home_street_address = '',
                    home_city = '',
                    hire_date = NULL,
                    employment_contract_type = '',
                    national_insurance_number = '',
                    is_deleted = 1
                WHERE employee_id IN ($employeeIdsList)
            ";
            if (!$conn->query($employee_query)) {
                throw new Exception("Error updating Employee records: " . $conn->error);
            }

            // Delete records from EmployeeHistory table
            $history_query = "
                DELETE FROM EmployeeHistory
                WHERE employee_id IN ($employeeIdsList)
            ";
            if (!$conn->query($history_query)) {
                throw new Exception("Error deleting EmployeeHistory records: " . $conn->error);
            }

            // Delete records from LeaveBalances table
            $leave_balances_query = "
                DELETE FROM LeaveBalances
                WHERE employee_id IN ($employeeIdsList)
            ";
            if (!$conn->query($leave_balances_query)) {
                throw new Exception("Error deleting LeaveBalances records: " . $conn->error);
            }

            // Delete records from LeaveRequests table
            $leave_requests_query = "
                DELETE FROM LeaveRequests
                WHERE employee_id IN ($employeeIdsList)
            ";
            if (!$conn->query($leave_requests_query)) {
                throw new Exception("Error deleting LeaveRequests records: " . $conn->error);
            }

            // Commit transaction
            $conn->commit();

            echo "Cleanup successful: Records older than 3 years were deleted.";
        } else {
            echo "No records older than 3 years were found for cleanup.";
        }
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        echo "Error during cleanup: " . $e->getMessage();
    }
}

// Call the cleanup function
cleanupDeletedRecords($conn);

// Close the database connection
$conn->close();
?>