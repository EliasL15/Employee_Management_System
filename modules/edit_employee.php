<?php
session_start();
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

// Fetch all employees
$sql = "SELECT employee_id, CONCAT(first_name, ' ', last_name) AS name FROM Employee WHERE is_deleted = 0";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    die("<div class='alert alert-info' role='alert'>No employees found.</div>");
}

// Handle form submission for updates
$message = "";
if (isset($_POST['delete_profile'])) {
    $employee_id = intval($_POST['employee_id']);

    mysqli_query($conn, "UPDATE Employee SET profile_picture = '../uploads/blank.png' WHERE employee_id = $employee_id");
    echo "Profile picture removed successfully!";
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !(isset($_POST['delete_profile']))) {
    $employee_id = intval($_POST['employee_id']);

    //upload the new image
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

    $position_id = intval($_POST['position_id']);
    $office_location_id = intval($_POST['office_location_id']);
    $first_name = htmlspecialchars($_POST['first_name']);
    $last_name = htmlspecialchars($_POST['last_name']);
    $salary_amount = floatval($_POST['salary_amount']);
    $email_address = filter_var($_POST['email_address'], FILTER_SANITIZE_EMAIL);
    $date_of_birth = $_POST['date_of_birth'];
    $home_street_address = htmlspecialchars($_POST['home_street_address']);
    $home_city = htmlspecialchars($_POST['home_city']);
    $hire_date = $_POST['hire_date'];
    $employment_contract_type = htmlspecialchars($_POST['employment_contract_type']);
    $national_insurance_number = htmlspecialchars($_POST['national_insurance_number']);
    $incentives = floatval($_POST['incentives']);
    $allowance = floatval($_POST['allowance']);
    $bonuses = floatval($_POST['bonuses']);

    // Get the promotion percentage increase
    $promotion_percentage = isset($_POST['promotion_percentage']) ? floatval($_POST['promotion_percentage']) : 0;

    // Apply the promotion increase (if selected)
    if ($promotion_percentage > 0) {
        $salary_amount = $salary_amount * (1 + $promotion_percentage / 100);
    }

    // Update employee financial history

    $current_salary_sql = "SELECT base_salary, incentives,bonuses, allowance FROM EmployeeHistory WHERE employee_id = ? AND date_modified IS NULL";

    $stmt = $conn->prepare($current_salary_sql);



    $stmt->bind_param("i", $employee_id);


    $stmt->bind_result($current_salary, $incentives, $incentives, $allowance);

    if ($stmt->fetch()) {
        // Check for NULL values and set defaults if necessary
        $current_salary = $current_salary ?? 0;
        $incentives = $incentives ?? 0;
        $allowance = $allowance ?? 0;
        $bonuses = $bonuses ?? 0;


    }

    $stmt->close();







    if ($current_salary != $salary_amount or $current_incentives != $current_allowance or $allowance != $allowance) {
        // Update the date_modified of the previous record
        $update_history_sql = "UPDATE employeeHistory SET date_modified = CURDATE() WHERE employee_id = ? AND date_modified IS NULL";
        $stmt = $conn->prepare($update_history_sql);
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        $stmt->close();

        // Insert a new record into the employeeHistory table
        $insert_history_sql = "INSERT INTO employeeHistory (employee_id, base_salary, allowance, incentives,bonuses, date_added, date_modified) VALUES (?, ?, ?, ?, ?, CURDATE(), NULL)";
        $stmt = $conn->prepare($insert_history_sql);
        $stmt->bind_param("idddd", $employee_id, $salary_amount, $allowance, $incentives, $bonuses);
        $stmt->execute();
        $stmt->close();
    }



    // Update employee details using prepared statement
    $update_sql = "UPDATE Employee
                   SET position_id = ?, office_location_id = ?, first_name = ?, last_name = ?, salary_amount = ?, email_address = ?, date_of_birth = ?, home_street_address = ?, home_city = ?, hire_date = ?, employment_contract_type = ?, national_insurance_number = ?
                   WHERE employee_id = ?";
    $stmt = $conn->prepare($update_sql);

    if ($stmt) {
        $stmt->bind_param("iissdsssssssi", $position_id, $office_location_id, $first_name, $last_name, $salary_amount, $email_address, $date_of_birth, $home_street_address, $home_city, $hire_date, $employment_contract_type, $national_insurance_number, $employee_id);

        if ($stmt->execute()) {
            $message = "<div class='alert alert-success' role='alert'>Employee updated successfully.</div>";
            // Refresh the employee list after the update
            $result = $conn->query($sql);
        } else {
            $message = "<div class='alert alert-danger' role='alert'>Error updating employee: " . htmlspecialchars($stmt->error) . "</div>";
        }
        $stmt->close();
    } else {
        $message = "<div class='alert alert-danger' role='alert'>Prepare failed: " . htmlspecialchars($conn->error) . "</div>";
    }

    // Handle Emergency Contacts (Add or Update)
    if (isset($_POST['emergency_contacts'])) {
        $emergency_contacts = $_POST['emergency_contacts'];

        foreach ($emergency_contacts as $contact) {
            $ec_first_name = htmlspecialchars($contact['first_name']);
            $ec_last_name = htmlspecialchars($contact['last_name']);
            $relationship = htmlspecialchars($contact['relationship']);
            $phone = htmlspecialchars($contact['phone']);

            if (isset($contact['contact_id']) && !empty($contact['contact_id'])) {
                // Update existing contact
                $contact_id = intval($contact['contact_id']);

                $update_ec_sql = "UPDATE EmergencyContact SET first_name = ?, last_name = ?, relationship = ?, phone = ? WHERE contact_id = ?";
                $stmt_ec = $conn->prepare($update_ec_sql);
                if ($stmt_ec) {
                    $stmt_ec->bind_param("ssssi", $ec_first_name, $ec_last_name, $relationship, $phone, $contact_id);
                    $stmt_ec->execute();
                    $stmt_ec->close();
                }
            } else {
                // Insert new contact
                $insert_ec_sql = "INSERT INTO EmergencyContact (employee_id, first_name, last_name, relationship, phone) VALUES (?, ?, ?, ?, ?)";
                $stmt_ec = $conn->prepare($insert_ec_sql);
                if ($stmt_ec) {
                    $stmt_ec->bind_param("issss", $employee_id, $ec_first_name, $ec_last_name, $relationship, $phone);
                    $stmt_ec->execute();
                    $stmt_ec->close();
                }
            }
        }

        $message .= "<div class='alert alert-success' role='alert'>Emergency contacts updated successfully.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Employee</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS (optional) -->
    <link rel="stylesheet" href="../css/styles.css">
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-5">
        <h1 class="mb-4">Edit Employee</h1>
        <?php echo $message; ?>

        <!-- Employee Selection Form -->
        <form method="POST" class="mb-4">
            <div class="mb-3">
                <label for="employee_id" class="form-label">Choose an Employee:</label>
                <select id="employee_id" name="employee_id" class="form-select" required
                    onchange="fetchEmployeeDetails(this.value)">
                    <option value="">-- Select Employee --</option>
                    <?php
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='" . htmlspecialchars($row['employee_id']) . "'>" . htmlspecialchars($row['name']) . "</option>";
                    }
                    ?>
                </select>
            </div>
        </form>

        <!-- Employee Edit Form -->
        <form method="POST" id="edit-form" style="display: none;" enctype="multipart/form-data">
            <input type="hidden" name="employee_id" id="hidden_employee_id">

            <div class="row mb-3">
                <div class="col-md-6">
                    <img id="profile_picture" class="img-thumbnail" style="max-width: 200px;">
                    <button type="button" style='float:right' id='remove_profile' class="btn btn-secondary"
                        onclick="remove_profile_pic()" value="Remove Image">Remove Image</button>

                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="first_name" class="form-label">First Name:</label>
                        <input type="text" id="first_name" name="first_name" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label for="last_name" class="form-label">Last Name:</label>
                        <input type="text" id="last_name" name="last_name" class="form-control" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="position_id" class="form-label">Position:</label>
                        <select id="position_id" name="position_id" class="form-select" required>
                            <option value="">-- Select Position --</option>
                            <?php
                            // Fetch all positions for dropdown
                            $positions = $conn->query("SELECT position_id, position_title FROM Position");
                            while ($row = $positions->fetch_assoc()) {
                                echo "<option value='" . htmlspecialchars($row['position_id']) . "'>" . htmlspecialchars($row['position_title']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="office_location_id" class="form-label">Office Location:</label>
                        <select id="office_location_id" name="office_location_id" class="form-select" required>
                            <option value="">-- Select Office Location --</option>
                            <?php
                            // Fetch all office locations for dropdown
                            $locations = $conn->query("SELECT location_id, location_name FROM Location");
                            while ($row = $locations->fetch_assoc()) {
                                echo "<option value='" . htmlspecialchars($row['location_id']) . "'>" . htmlspecialchars($row['location_name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="salary_amount" class="form-label">Salary:</label>
                        <input type="number" id="salary_amount" name="salary_amount" step="0.01" class="form-control"
                            required>
                    </div>

                    <div class="col-md-6">
                        <label for="email_address" class="form-label">Email Address:</label>
                        <input type="email" id="email_address" name="email_address" class="form-control" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="date_of_birth" class="form-label">Date of Birth:</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label for="hire_date" class="form-label">Hire Date:</label>
                        <input type="date" id="hire_date" name="hire_date" class="form-control" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="home_street_address" class="form-label">Home Street Address:</label>
                        <input type="text" id="home_street_address" name="home_street_address" class="form-control"
                            required>
                    </div>

                    <div class="col-md-6">
                        <label for="home_city" class="form-label">Home City:</label>
                        <input type="text" id="home_city" name="home_city" class="form-control" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="employment_contract_type" class="form-label">Employment Contract Type:</label>
                        <select id="employment_contract_type" name="employment_contract_type" class="form-select"
                            required>
                            <option value="Full-Time">Full-Time</option>
                            <option value="Part-Time">Part-Time</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="national_insurance_number" class="form-label">National Insurance Number:</label>
                        <input type="text" id="national_insurance_number" name="national_insurance_number"
                            pattern="^[A-Z]{2}[0-9]{6}[A-Z]$" title="Format: 2 letters, 6 numbers, 1 letter"
                            class="form-control" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="promotion_percentage" class="form-label">Promotion (Percentage Increase):</label>
                        <select name="promotion_percentage" id="promotion_percentage" class="form-select"
                            onchange="calculateNewSalary()">
                            <option value="0">No Promotion</option>
                            <option value="5">5%</option>
                            <option value="10">10%</option>
                            <option value="15">15%</option>
                            <option value="20">20%</option>

                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="new_salary" class="form-label">New Salary after Promotion:</label>
                        <input type="text" id="new_salary" class="form-control" readonly>
                    </div>

                    <div class="col-md-6">
                        <label for="incentives" class="form-label">Incentive:</label>
                        <input type="number" id="incentives" name="incentives" step="0.01" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label for="bonuses" class="form-label">Bonuses:</label>
                        <input type="number" id="bonuses" name="bonuses" step="0.01" class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label for="allowance" class="form-label">Allowance:</label>
                        <input type="number" id="allowance" name="allowance" step="0.01" class="form-control">
                    </div>


                </div>
                <label>Upload Profile Picture</label>
                <input type="file" name="profile_picture" class="form-control" accept="image/*">

                <!-- Emergency Contacts Section -->
                <h3 class="mt-4">Emergency Contacts</h3>
                <div id="emergency_contacts_container">
                    <!-- Existing Emergency Contacts will be appended here -->
                </div>
                <button type="button" class="btn btn-secondary mt-2" onclick="addEmergencyContact()">Add Emergency
                    Contact</button>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary mt-2">Update Employee</button>
        </form>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JavaScript -->
    <script>
        // Function to fetch employee details and populate the form

        function remove_profile_pic() {
            //post request to remove profile picture
            const employee_id = document.getElementById("hidden_employee_id").value;
            const xhr = new XMLHttpRequest();

            xhr.open("POST", "edit_employee.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    console.log(xhr.responseText);
                    console.log("Profile picture removed successfully");
                    document.getElementById("profile_picture").src = '../uploads/blank.png';
                    //hide remove
                    document.getElementById("remove_profile").style.display = "none";
                } else if (xhr.readyState === 4) {
                    console.error("Error removing profile picture:", xhr.responseText);
                    alert('Error removing profile picture: ' + xhr.responseText);
                }
            };
            const postData = "delete_profile=true&employee_id=" + encodeURIComponent(employee_id);

            // Send the POST request with the data
            xhr.send(postData);
        }

        function fetchEmployeeDetails(employeeId) {
            if (!employeeId) {
                document.getElementById("edit-form").style.display = "none";
                return;
            }

            // Fetch employee details from the server
            fetch(`get_employee.php?employee_id=${employeeId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert("Error: " + data.error);
                        document.getElementById("edit-form").style.display = "none";
                        return;
                    }
                    document.getElementById("profile_picture").src = data.employee.profile_picture;

                    if (data.employee.profile_picture != '../uploads/blank.png') {
                        document.getElementById("remove_profile").style.display = "block";
                    } else {
                        document.getElementById("remove_profile").style.display = "none";
                    }
                    console.log(data);

                    document.getElementById("edit-form").style.display = "block";
                    document.getElementById("hidden_employee_id").value = data.employee.employee_id;
                    document.getElementById("position_id").value = data.employee.position_id;
                    document.getElementById("office_location_id").value = data.employee.office_location_id;
                    document.getElementById("first_name").value = data.employee.first_name;
                    document.getElementById("last_name").value = data.employee.last_name;
                    document.getElementById("salary_amount").value = data.employee.salary_amount;
                    document.getElementById("email_address").value = data.employee.email_address;
                    document.getElementById("date_of_birth").value = data.employee.date_of_birth;
                    document.getElementById("home_street_address").value = data.employee.home_street_address;
                    document.getElementById("home_city").value = data.employee.home_city;
                    document.getElementById("hire_date").value = data.employee.hire_date;
                    document.getElementById("employment_contract_type").value = data.employee.employment_contract_type;
                    document.getElementById("national_insurance_number").value = data.employee.national_insurance_number;
                    document.getElementById("incentives").value = data.employee.history[0].incentives;
                    document.getElementById("allowance").value = data.employee.history[0].allowance;
                    document.getElementById("bonuses").value = data.employee.history[0].bonuses;


                    // Populate Emergency Contacts
                    const contactsContainer = document.getElementById("emergency_contacts_container");
                    contactsContainer.innerHTML = ''; // Clear existing contacts

                    if (data.emergency_contacts.length > 0) {
                        data.emergency_contacts.forEach(contact => {
                            addEmergencyContact(contact);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error fetching employee details:', error);
                    alert('Failed to fetch employee details. Please try again.');
                });
        }

        // Calculate new salary based on promotion percentage
        function calculateNewSalary() {
            const salary = parseFloat(document.getElementById("salary_amount").value);
            const promotionPercentage = parseFloat(document.getElementById("promotion_percentage").value);

            if (salary && promotionPercentage) {
                const newSalary = salary * (1 + promotionPercentage / 100);
                document.getElementById("new_salary").value = newSalary.toFixed(2); // Display new salary
            } else {
                document.getElementById("new_salary").value = '';
            }
        }

        // Function to add a new Emergency Contact form group
        function addEmergencyContact(contact = {}) {
            const container = document.getElementById("emergency_contacts_container");
            const contactIndex = container.children.length;

            const contactDiv = document.createElement("div");
            contactDiv.classList.add("card", "mb-3");

            contactDiv.innerHTML = `
                <div class="card-body">
                    <h5 class="card-title">Emergency Contact ${contactIndex + 1}</h5>
                    <input type="hidden" name="emergency_contacts[${contactIndex}][contact_id]" value="${contact.contact_id || ''}">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name:</label>
                            <input type="text" name="emergency_contacts[${contactIndex}][first_name]" class="form-control" value="${contact.first_name || ''}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name:</label>
                            <input type="text" name="emergency_contacts[${contactIndex}][last_name]" class="form-control" value="${contact.last_name || ''}" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Relationship:</label>
                            <input type="text" name="emergency_contacts[${contactIndex}][relationship]" class="form-control" value="${contact.relationship || ''}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone:</label>
                            <input type="text" name="emergency_contacts[${contactIndex}][phone]" class="form-control" value="${contact.phone || ''}" required>
                        </div>
                    </div>
                    <button type="button" class="btn btn-danger remove-contact">Remove Contact</button>
                </div>
            `;
            container.appendChild(contactDiv);

            // Attach event listener to the remove button
            const removeButton = contactDiv.querySelector('.remove-contact');
            removeButton.addEventListener('click', function () {
                const contactIdInput = contactDiv.querySelector('input[name$="[contact_id]"]');
                const contactId = contactIdInput ? contactIdInput.value : null;
                removeEmergencyContact(this, contactId);
            });
        }

        // Function to remove an Emergency Contact form group
        function removeEmergencyContact(button) {
            console.log("Removing contact:");
            const contactDiv = button.closest('.card');
            if (!contactDiv) return;

            const contactIdInput = contactDiv.querySelector('input[name$="[contact_id]"]');
            const contactId = contactIdInput ? contactIdInput.value : null;

            console.log("Contact ID:", contactId);

            if (contactId && contactId !== '') {
                // Existing contact, send AJAX request to delete the contact
                const employeeId = document.getElementById("hidden_employee_id").value;
                const xhr = new XMLHttpRequest();
                xhr.open("POST", "delete_contact.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function () {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        console.log("Contact deleted successfully:", contactId);
                        // Remove the contact div
                        contactDiv.remove();
                        // Re-index the remaining contacts
                        reindexEmergencyContacts();
                    } else if (xhr.readyState === 4) {
                        console.error("Error deleting contact:", xhr.responseText);
                        alert('Error deleting contact: ' + xhr.responseText);
                    }
                };
                xhr.send("contact_id=" + encodeURIComponent(contactId) + "&employee_id=" + encodeURIComponent(employeeId));
            } else {
                // New contact, just remove the contact div
                contactDiv.remove();
                // Re-index the remaining contacts
                reindexEmergencyContacts();
            }
        }

        // Function to re-index emergency contacts after removal
        function reindexEmergencyContacts() {
            const container = document.getElementById("emergency_contacts_container");
            Array.from(container.children).forEach((contactDiv, index) => {
                contactDiv.querySelector('.card-title').innerText = `Emergency Contact ${index + 1}`;
                contactDiv.querySelectorAll('input, select').forEach(input => {
                    const name = input.name;
                    const newName = name.replace(/emergency_contacts\[\d+\]/, `emergency_contacts[${index}]`);
                    input.name = newName;
                });
            });
        }
    </script>
</body>

</html>
<?php
// Close the database connection
if (isset($conn)) {
    $conn->close();
}
?>