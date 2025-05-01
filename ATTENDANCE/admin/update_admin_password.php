<?php
$servername = "localhost";
$username = "root"; // Change if needed
$password = "";
$database = "library_attendance_system";

// Connect to the database
$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set new password (CHANGE THIS if needed)
$admin_username = "admin";
$new_password = "admin123"; // Change this to the actual password you want
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Update the database with the hashed password
$sql = "UPDATE admin_users SET password = '$hashed_password' WHERE username = '$admin_username'";

if ($conn->query($sql) === TRUE) {
    echo "Admin password updated successfully!";
} else {
    echo "Error updating password: " . $conn->error;
}

$conn->close();
?>
