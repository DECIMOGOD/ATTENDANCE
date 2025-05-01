<?php
require 'admin/db_connection.php'; // Connect to the database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $lrn = $_POST['lrn'];

    // Validate LRN (must be exactly 12 digits)
    if (!preg_match("/^[0-9]{12}$/", $lrn)) {
        die("<script>alert('Invalid LRN! Must be 12 digits.'); window.location.href='index.html';</script>");
    }

    // Check if the student exists in the database
    $stmt = $conn->prepare("SELECT * FROM students WHERE LRN = ?");
    $stmt->bind_param("s", $lrn);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("<script>alert('LRN not found!'); window.location.href=ome.php';</script>");
    }

    // Check if the student has already checked in today
    $date = date("Y-m-d");
    $stmt = $conn->prepare("SELECT * FROM attendance_records WHERE LRN = ? AND DATE(time_in) = ? ORDER BY time_in DESC LIMIT 1");
    $stmt->bind_param("ss", $lrn, $date);
    $stmt->execute();
    $attendance = $stmt->get_result();
    
    if ($attendance->num_rows > 0) {
        // Student has already checked in, check if they have checked out
        $row = $attendance->fetch_assoc();
        if ($row['time_out'] === NULL) {
            // Update record with time_out including full date and time
            $stmt = $conn->prepare("UPDATE attendance_records SET time_out = NOW() WHERE id = ?");
            $stmt->bind_param("i", $row['id']);
            $stmt->execute();
            echo "<script>alert('Time Out Recorded!'); window.location.href='home.php';</script>";
        } else {
            echo "<script>alert('You have already checked out for today!'); window.location.href='home.php';</script>";
        }
    } else {
        // Insert new check-in record with full timestamp
        $stmt = $conn->prepare("INSERT INTO attendance_records (LRN, time_in) VALUES (?, NOW())");
        $stmt->bind_param("s", $lrn);
        $stmt->execute();
        echo "<script>alert('Time In Recorded!'); window.location.href='home.php';</script>";
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: home.php");
    exit();
}
?>
