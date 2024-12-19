<?php
// delete_contact.php
include "../includes/dp_connect.php";
session_start();

// Check if the user is logged in and has the required role
if (!isset($_SESSION['employee_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['executive', 'jarvis'])) {
    http_response_code(403);
    echo "Unauthorized access";
    exit;
}

// Check if contact_id is provided
if (!isset($_POST['contact_id'])) {
    http_response_code(400);
    echo "Contact ID not provided";
    exit;
}

$contact_id = intval($_POST['contact_id']);

// Delete the contact from the database based on contact_id only
$delete_ec_sql = "DELETE FROM EmergencyContact WHERE contact_id = ?";
$stmt_del = $conn->prepare($delete_ec_sql);
if ($stmt_del) {
    $stmt_del->bind_param("i", $contact_id);
    if ($stmt_del->execute()) {
        echo "Contact deleted successfully";
    } else {
        http_response_code(500);
        echo "Error deleting contact: " . htmlspecialchars($stmt_del->error);
    }
    $stmt_del->close();
} else {
    http_response_code(500);
    echo "Error preparing statement: " . htmlspecialchars($conn->error);
}

// Close the database connection
if (isset($conn)) {
    $conn->close();
}
?>