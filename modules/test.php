<?php
// Include database connection
include "../includes/dp_connect.php";

// Define users to insert
$users = [
    ['executive_user', 'password123', 'executive'],
    ['jarvis_user', 'password123', 'jarvis'],
    ['employee_user', 'password123', 'employee']
];

foreach ($users as $user) {
    $username = $user[0];
    $password_plain = $user[1];
    $role = $user[2];

    // Hash the password
    $hashed_password = password_hash($password_plain, PASSWORD_BCRYPT);

    // Insert the user
    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sss", $username, $hashed_password, $role);
        if ($stmt->execute()) {
            echo "User '$username' added successfully.<br>";
        } else {
            echo "Error adding user '$username': " . $stmt->error . "<br>";
        }
        $stmt->close();
    } else {
        echo "Prepare failed for user '$username': " . $conn->error . "<br>";
    }
}
?>