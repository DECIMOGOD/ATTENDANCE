<?php
session_start();
include 'db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_username'])) {
    header("Location: login.php");
    exit();
}

// Get LRN from URL
$lrn = $_GET['lrn'] ?? '';

if (empty($lrn)) {
    header("Location: librarypatrons.php");
    exit();
}

// Fetch patron data
$query = "SELECT * FROM students WHERE LRN = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $lrn);
$stmt->execute();
$result = $stmt->get_result();
$patron = $result->fetch_assoc();

if (!$patron) {
    header("Location: librarypatrons.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $grade_level = $_POST['grade_level'];
    $section = $_POST['section'];
    $strand = $_POST['strand'];
    $status = $_POST['status'];

    $update_query = "UPDATE students SET 
                    Name = ?, Grade_Level = ?, Section = ?, Strand = ?, Status = ?
                    WHERE LRN = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param('ssssss', $name, $grade_level, $section, $strand, $status, $lrn);

    if ($stmt->execute()) {
        echo "<script>alert('Patron updated successfully!'); window.location.href = 'librarypatrons.php';</script>";
    } else {
        echo "<script>alert('Error updating patron: " . addslashes($conn->error) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Patron</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-indigo-950/5 flex min-h-screen">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 p-4">
        <div class="w-full mx-auto max-w-4xl">
            <!-- Header section -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-indigo-950">Edit Patron</h1>
                <p class="text-indigo-600/70">Update student information</p>
            </div>

            <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-indigo-100">
                <div class="p-4 bg-gradient-to-r from-indigo-900 via-blue-950 to-indigo-950 text-white">
                    <div class="flex items-center">
                        <div class="bg-white/20 p-2 rounded-lg shadow-inner mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white">Edit Student Information</h2>
                            <p class="text-xs text-blue-200">Update the details for <?php echo htmlspecialchars($patron['Name']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Edit Form -->
                <form method="POST" class="p-6">
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- LRN (readonly) -->
                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-indigo-900">LRN</label>
                                <input type="text" value="<?php echo htmlspecialchars($patron['LRN']); ?>" readonly class="w-full px-3 py-2 border border-indigo-200 rounded-lg bg-indigo-50/50 text-sm text-indigo-700">
                            </div>

                            <!-- Name -->
                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-indigo-900">Name</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($patron['Name']); ?>" required class="w-full px-3 py-2 border border-indigo-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-indigo-50/50 text-sm">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Grade Level -->
                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-indigo-900">Grade Level</label>
                                <select name="grade_level" required class="w-full px-3 py-2 border border-indigo-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-indigo-50/50 text-sm">
                                    <option value="Grade 7" <?php echo $patron['Grade_Level'] === 'Grade 7' ? 'selected' : ''; ?>>Grade 7</option>
                                    <option value="Grade 8" <?php echo $patron['Grade_Level'] === 'Grade 8' ? 'selected' : ''; ?>>Grade 8</option>
                                    <option value="Grade 9" <?php echo $patron['Grade_Level'] === 'Grade 9' ? 'selected' : ''; ?>>Grade 9</option>
                                    <option value="Grade 10" <?php echo $patron['Grade_Level'] === 'Grade 10' ? 'selected' : ''; ?>>Grade 10</option>
                                    <option value="Grade 11" <?php echo $patron['Grade_Level'] === 'Grade 11' ? 'selected' : ''; ?>>Grade 11</option>
                                    <option value="Grade 12" <?php echo $patron['Grade_Level'] === 'Grade 12' ? 'selected' : ''; ?>>Grade 12</option>
                                </select>
                            </div>

                            <!-- Section -->
                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-indigo-900">Section</label>
                                <input type="text" name="section" value="<?php echo htmlspecialchars($patron['Section']); ?>" required class="w-full px-3 py-2 border border-indigo-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-indigo-50/50 text-sm">
                            </div>
                        </div>

                        <!-- Strand -->
                        <div class="space-y-1">
                            <label class="block text-sm font-medium text-indigo-900">Strand</label>
                            <select name="strand" required class="w-full px-3 py-2 border border-indigo-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-indigo-50/50 text-sm">
                                <option value="STEM" <?php echo $patron['Strand'] === 'STEM' ? 'selected' : ''; ?>>STEM</option>
                                <option value="ABM" <?php echo $patron['Strand'] === 'ABM' ? 'selected' : ''; ?>>ABM</option>
                                <option value="HUMSS" <?php echo $patron['Strand'] === 'HUMSS' ? 'selected' : ''; ?>>HUMSS</option>
                                <option value="ICT" <?php echo $patron['Strand'] === 'ICT' ? 'selected' : ''; ?>>ICT</option>
                                <option value="HE" <?php echo $patron['Strand'] === 'HE' ? 'selected' : ''; ?>>HE</option>
                            </select>
                        </div>

                        <!-- Status -->
                        <div class="space-y-1">
                            <label class="block text-sm font-medium text-indigo-900">Status</label>
                            <div class="flex space-x-4 mt-1">
                                <label class="inline-flex items-center">
                                    <input type="radio" name="status" value="Active" class="form-radio text-indigo-600 focus:ring-indigo-500" <?php echo $patron['Status'] === 'Active' ? 'checked' : ''; ?>>
                                    <span class="ml-2 text-sm text-indigo-800">Active</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="status" value="Inactive" class="form-radio text-indigo-600 focus:ring-indigo-500" <?php echo $patron['Status'] === 'Inactive' ? 'checked' : ''; ?>>
                                    <span class="ml-2 text-sm text-indigo-800">Inactive</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <a href="librarypatrons.php"px-4 py-2  border-indigo-200 rounded-lg text-indigo-700 font-medium hover:bg-indigo-50 transition text-sm">
                            Cancel
                        </a>
                        <button type="submit" class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-blue-700 rounded-lg text-white font-medium hover:from-indigo-500 hover:to-blue-600 transition shadow-lg text-sm">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>