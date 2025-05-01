<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "library_attendance_system";

$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if admin user already exists
$check_sql = "SELECT * FROM admin_users WHERE username = 'admin'";
$result = $conn->query($check_sql);

if ($result->num_rows > 0) {
    echo "Admin user already exists!";
} else {
    // Hash the password
    $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);

    // Insert admin user
    $sql = "INSERT INTO admin_users (username, password) VALUES ('admin', '$hashed_password')";
    
    if ($conn->query($sql) === TRUE) {
        echo "Admin user inserted successfully!";
    } else {
        echo "Error: " . $conn->error;
    }
}

$conn->close();
?>
