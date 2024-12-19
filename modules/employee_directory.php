<?php
session_start();
// Enable error reporting (only in development)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
include "../includes/dp_connect.php";

// Check if the user is logged in and has the required role
if (!isset($_SESSION['employee_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['executive', 'jarvis'])) {
    header('Location: ../login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Directory</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .profile-card {
            border: 1px solid #ccc;
            padding: 20px;
            margin-bottom: 20px;
            transition: background-color 0.3s ease;
            cursor: pointer;
        }

        .profile-card:hover {
            background-color: #f0f0f0;
        }
    </style>
</head>

<body>
    <?php include '../includes/navbar.php'; // Create a navbar.php with links as needed ?>

    <div class="container my-5">
        <h1 class="mb-4 text-center">Employee Directory</h1>

        <!-- Search Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form id="search-form">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="search_name" class="form-label">Name:</label>
                            <input type="text" id="search_name" class="form-control" placeholder="Search by name"
                                onkeyup="filterEmployees()">
                        </div>

                        <div class="col-md-4">
                            <label for="search_department" class="form-label">Department:</label>
                            <input type="text" id="search_department" class="form-control"
                                placeholder="Search by department" onkeyup="filterEmployees()">
                        </div>

                        <div class="col-md-4">
                            <label for="search_position" class="form-label">Position:</label>
                            <input type="text" id="search_position" class="form-control"
                                placeholder="Search by position" onkeyup="filterEmployees()">
                        </div>

                        <div class="col-md-4">
                            <label for="search_location" class="form-label">Location:</label>
                            <input type="text" id="search_location" class="form-control"
                                placeholder="Search by location" onkeyup="filterEmployees()">
                        </div>

                        <div class="col-md-4">
                            <label for="search_start_date" class="form-label">Start Date:</label>
                            <input type="date" id="search_start_date" class="form-control" onchange="filterEmployees()">
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Employee List -->
        <div id="employee-list" class="row"></div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterEmployees() {
            const name = $('#search_name').val() || '';  // Default to empty string if no input
            const department = $('#search_department').val() || '';
            const position = $('#search_position').val() || '';
            const location = $('#search_location').val() || '';
            const startDate = $('#search_start_date').val() || '';


            $.ajax({
                url: 'search_employees.php',
                type: 'GET',
                data: {
                    name: name,
                    department: department,
                    position: position,
                    location: location,
                    start_date: startDate
                },
                success: function (response) {
                    const employees = JSON.parse(response);
                    let html = '';

                    if (employees.length === 0) {
                        html = `
                            <div class="col-12">
                                <div class="alert alert-info">No employees found.</div>
                            </div>
                        `;
                    } else {
                        employees.forEach(function (employee) {
                            html += `
                                <div class="col-md-4">
                                    <div class="profile-card card shadow-sm" onclick="viewEmployee(${employee.employee_id})">
                                        <div class="card-body">
                                            <h5 class="card-title">${employee.first_name} ${employee.last_name}</h5>
                                            <p class="card-text"><strong>Position:</strong> ${employee.position_title}</p>
                                            <p class="card-text"><strong>Department:</strong> ${employee.department_name}</p>
                                            <p class="card-text"><strong>Location:</strong> ${employee.location_name}</p>
                                            <p class="card-text"><strong>Start Date:</strong> ${employee.hire_date}</p>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                    }

                    $('#employee-list').html(html);
                },
                error: function () {
                    $('#employee-list').html(`
                        <div class="col-12">
                            <div class="alert alert-danger">Error fetching employees. Please try again later.</div>
                        </div>
                    `);
                }
            });
        }

        $(document).ready(function () {
            filterEmployees(); // Fetch all employees on page load
        });

        function viewEmployee(employeeId) {
            window.location.href = `employee_profile.php?employee_id=${employeeId}`;
        }
    </script>
</body>

</html>