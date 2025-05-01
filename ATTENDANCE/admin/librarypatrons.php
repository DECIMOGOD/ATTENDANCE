<?php
session_start();
include 'db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_username'])) {
    header("Location: login.php");
    exit();
}

// Initialize variables
$search = '';
$grade_filter = '';
$strand_filter = '';
$status_filter = '';

// Handle search and filters
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $grade_filter = isset($_GET['grade']) ? $_GET['grade'] : '';
    $strand_filter = isset($_GET['strand']) ? $_GET['strand'] : '';
    $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
}

// Build base query
$query = "SELECT * FROM students WHERE 1=1";
$params = [];
$types = '';

// Add search condition
if (!empty($search)) {
    $query .= " AND (Name LIKE ? OR LRN LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}

// Add filters
if (!empty($grade_filter) && $grade_filter !== 'All Grades') {
    $query .= " AND Grade_Level = ?";
    $params[] = $grade_filter;
    $types .= 's';
}

if (!empty($strand_filter) && $strand_filter !== 'All Strands') {
    $query .= " AND Strand = ?";
    $params[] = $strand_filter;
    $types .= 's';
}

if (!empty($status_filter) && $status_filter !== 'All Status') {
    $query .= " AND Status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

// Prepare and execute query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Export CSV
if (isset($_POST['export_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=library_patrons.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['LRN', 'Name', 'Grade Level', 'Section', 'Strand', 'Status']);
    
    // Re-run query for export to ensure all data is included
    $export_stmt = $conn->prepare($query);
    if (!empty($params)) {
        $export_stmt->bind_param($types, ...$params);
    }
    $export_stmt->execute();
    $export_result = $export_stmt->get_result();
    
    while ($row = $export_result->fetch_assoc()) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit();
}

// Import CSV
if (isset($_POST['import_csv'])) {
    if ($_FILES['csv_file']['error'] == 0) {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        
        // Skip header row
        fgetcsv($handle);
        
        $conn->begin_transaction();
        
        try {
            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                if (count($data) < 6) continue; // Skip incomplete rows
                
                $lrn = $data[0];
                $name = $data[1];
                $grade_level = $data[2];
                $section = $data[3];
                $strand = $data[4];
                $status = $data[5];
                
                // Check if student exists
                $check_query = "SELECT LRN FROM students WHERE LRN = ?";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bind_param('s', $lrn);
                $check_stmt->execute();
                $exists = $check_stmt->get_result()->num_rows > 0;
                
                if ($exists) {
                    // Update existing record
                    $query = "UPDATE students SET 
                              Name = ?, Grade_Level = ?, Section = ?, Strand = ?, Status = ?
                              WHERE LRN = ?";
                } else {
                    // Insert new record
                    $query = "INSERT INTO students 
                              (Name, Grade_Level, Section, Strand, Status, LRN)
                              VALUES (?, ?, ?, ?, ?, ?)";
                }
                
                $stmt = $conn->prepare($query);
                if ($exists) {
                    $stmt->bind_param('ssssss', $name, $grade_level, $section, $strand, $status, $lrn);
                } else {
                    $stmt->bind_param('ssssss', $name, $grade_level, $section, $strand, $status, $lrn);
                }
                $stmt->execute();
            }
            
            $conn->commit();
            echo "<script>alert('CSV imported successfully!'); window.location.href = window.location.href;</script>";
        } catch (Exception $e) {
            $conn->rollback();
            echo "<script>alert('Error importing CSV: " . addslashes($e->getMessage()) . "');</script>";
        }
        
        fclose($handle);
    } else {
        echo "<script>alert('Error uploading CSV file.');</script>";
    }
}

// Add New Patron
if (isset($_POST['add_patron'])) {
    $lrn = $_POST['lrn'];
    $name = $_POST['name'];
    $grade_level = $_POST['grade_level'];
    $section = $_POST['section'];
    $strand = $_POST['strand'];
    $status = $_POST['status'];
    
    // Check if student exists
    $check_query = "SELECT LRN FROM students WHERE LRN = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param('s', $lrn);
    $check_stmt->execute();
    $exists = $check_stmt->get_result()->num_rows > 0;
    
    if ($exists) {
        // Update existing record
        $query = "UPDATE students SET 
                  Name = ?, Grade_Level = ?, Section = ?, Strand = ?, Status = ?
                  WHERE LRN = ?";
    } else {
        // Insert new record
        $query = "INSERT INTO students 
                  (Name, Grade_Level, Section, Strand, Status, LRN)
                  VALUES (?, ?, ?, ?, ?, ?)";
    }
    
    $stmt = $conn->prepare($query);
    if ($exists) {
        $stmt->bind_param('ssssss', $name, $grade_level, $section, $strand, $status, $lrn);
    } else {
        $stmt->bind_param('ssssss', $name, $grade_level, $section, $strand, $status, $lrn);
    }
    
    if ($stmt->execute()) {
        echo "<script>alert('Patron " . ($exists ? 'updated' : 'added') . " successfully!'); window.location.href = window.location.href;</script>";
    } else {
        echo "<script>alert('Error: " . addslashes($conn->error) . "');</script>";
    }
}

// Delete Patron
if (isset($_GET['delete'])) {
    $lrn = $_GET['delete'];
    $query = "DELETE FROM students WHERE LRN = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $lrn);
    
    if ($stmt->execute()) {
        echo "<script>alert('Patron deleted successfully!'); window.location.href = window.location.href.split('?')[0];</script>";
    } else {
        echo "<script>alert('Error deleting patron.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Patrons</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .modal-wrapper {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
            visibility: hidden;
            opacity: 0;
            transition: visibility 0s, opacity 0.2s ease-in-out;
        }
        .modal-wrapper.active {
            visibility: visible;
            opacity: 1;
        }
    </style>
</head>
<body class="bg-indigo-950/5 flex min-h-screen">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 p-4">
        <div class="w-full mx-auto">
            <!-- Header section with greeting -->
            <div class="mb-4">
                <h1 class="text-2xl font-bold text-indigo-950">Library Patrons</h1>
                <p class="text-indigo-600/70">Manage student access and information</p>
            </div>

            <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-indigo-100">
                <div class="p-4 bg-gradient-to-r from-indigo-900 via-blue-950 to-indigo-950 text-white">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center">
                            <div class="bg-white/20 p-2 rounded-lg shadow-inner mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-white">Student Directory</h2>
                                <p class="text-xs text-blue-200">Manage library access permissions</p>
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <button class="bg-gradient-to-r from-blue-500 to-indigo-500 text-white px-3 py-2 rounded-lg shadow-lg hover:from-blue-400 hover:to-indigo-400 transition duration-200 flex items-center text-sm" onclick="toggleModal('addModal')">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Add Patron
                            </button>
                            <form method="POST">
                                <button name="export_csv" class="bg-gradient-to-r from-green-600 to-emerald-600 text-white px-3 py-2 rounded-lg shadow-lg hover:from-green-500 hover:to-emerald-500 transition duration-200 flex items-center text-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    Export CSV
                                </button>
                            </form>
                            <button class="bg-gradient-to-r from-purple-600 to-pink-600 text-white px-3 py-2 rounded-lg shadow-lg hover:from-purple-500 hover:to-pink-500 transition duration-200 flex items-center text-sm" onclick="toggleModal('importModal')">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                Import CSV
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Search and filter bar -->
                <div class="bg-indigo-50/70 p-3 border-b border-indigo-100 flex flex-wrap items-center justify-between gap-2">
                    <form method="GET" class="relative flex-grow max-w-md">
                        <input type="text" name="search" placeholder="Search students..." value="<?php echo htmlspecialchars($search); ?>" class="w-full pl-10 pr-4 py-2 rounded-lg border border-indigo-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                        <div class="absolute left-3 top-2.5 text-indigo-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <button type="submit" class="hidden">Search</button>
                    </form>
                    <div class="flex items-center space-x-2">
                        <form method="GET" class="flex items-center space-x-2">
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                            <select name="grade" class="px-3 py-2 border border-indigo-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm bg-white" onchange="this.form.submit()">
                                <option value="All Grades" <?php echo $grade_filter === 'All Grades' || empty($grade_filter) ? 'selected' : ''; ?>>All Grades</option>
                                <option value="Grade 7" <?php echo $grade_filter === 'Grade 7' ? 'selected' : ''; ?>>Grade 7</option>
                                <option value="Grade 8" <?php echo $grade_filter === 'Grade 8' ? 'selected' : ''; ?>>Grade 8</option>
                                <option value="Grade 9" <?php echo $grade_filter === 'Grade 9' ? 'selected' : ''; ?>>Grade 9</option>
                                <option value="Grade 10" <?php echo $grade_filter === 'Grade 10' ? 'selected' : ''; ?>>Grade 10</option>
                                <option value="Grade 11" <?php echo $grade_filter === 'Grade 11' ? 'selected' : ''; ?>>Grade 11</option>
                                <option value="Grade 12" <?php echo $grade_filter === 'Grade 12' ? 'selected' : ''; ?>>Grade 12</option>
                            </select>
                            <select name="strand" class="px-3 py-2 border border-indigo-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm bg-white" onchange="this.form.submit()">
                                <option value="All Strands" <?php echo $strand_filter === 'All Strands' || empty($strand_filter) ? 'selected' : ''; ?>>All Strands</option>
                                <option value="STEM" <?php echo $strand_filter === 'STEM' ? 'selected' : ''; ?>>STEM</option>
                                <option value="ABM" <?php echo $strand_filter === 'ABM' ? 'selected' : ''; ?>>ABM</option>
                                <option value="HUMSS" <?php echo $strand_filter === 'HUMSS' ? 'selected' : ''; ?>>HUMSS</option>
                                <option value="ICT" <?php echo $strand_filter === 'ICT' ? 'selected' : ''; ?>>ICT</option>
                                <option value="HE" <?php echo $strand_filter === 'HE' ? 'selected' : ''; ?>>HE</option>
                            </select>
                            <select name="status" class="px-3 py-2 border border-indigo-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm bg-white" onchange="this.form.submit()">
                                <option value="All Status" <?php echo $status_filter === 'All Status' || empty($status_filter) ? 'selected' : ''; ?>>All Status</option>
                                <option value="Active" <?php echo $status_filter === 'Active' ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo $status_filter === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                            <?php if ($search || $grade_filter !== 'All Grades' || $strand_filter !== 'All Strands' || $status_filter !== 'All Status'): ?>
                                <a href="?" class="px-3 py-2 text-indigo-600 hover:text-indigo-800 text-sm">Clear Filters</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full bg-white">
                        <thead>
                            <tr class="bg-indigo-50 border-b border-indigo-100">
                                <th class="px-3 py-3 text-left text-xs font-semibold text-indigo-800 uppercase tracking-wider">LRN</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-indigo-800 uppercase tracking-wider">Name</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-indigo-800 uppercase tracking-wider">Grade Level</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-indigo-800 uppercase tracking-wider">Section</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-indigo-800 uppercase tracking-wider">Strand</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-indigo-800 uppercase tracking-wider">Status</th>
                                <th class="px-3 py-3 text-right text-xs font-semibold text-indigo-800 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-indigo-100">
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                <tr class="hover:bg-indigo-50/30 transition duration-150">
                                    <td class="px-3 py-3 text-sm text-indigo-700"><?php echo htmlspecialchars($row['LRN']); ?></td>
                                    <td class="px-3 py-3 text-sm font-medium text-indigo-900"><?php echo htmlspecialchars($row['Name']); ?></td>
                                    <td class="px-3 py-3 text-sm text-indigo-700"><?php echo htmlspecialchars($row['Grade_Level']); ?></td>
                                    <td class="px-3 py-3 text-sm text-indigo-700"><?php echo htmlspecialchars($row['Section']); ?></td>
                                    <td class="px-3 py-3 text-sm text-indigo-700"><?php echo htmlspecialchars($row['Strand']); ?></td>
                                    <td class="px-3 py-3 text-sm">
                                        <?php if($row['Status'] == 'Active'): ?>
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 border border-green-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 mr-1 self-center"></span>
                                                <?php echo htmlspecialchars($row['Status']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 border border-red-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-500 mr-1 self-center"></span>
                                                <?php echo htmlspecialchars($row['Status']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3 py-3 text-sm text-right">
                                        <a href="edit_patron.php?lrn=<?php echo urlencode($row['LRN']); ?>" class="bg-white border border-indigo-200 text-indigo-700 p-1.5 rounded-lg hover:bg-indigo-100 transition duration-150 mr-1 shadow-sm inline-block">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                        <a href="?delete=<?php echo urlencode($row['LRN']); ?>" onclick="return confirm('Are you sure you want to delete this patron?')" class="bg-white border border-red-200 text-red-600 p-1.5 rounded-lg hover:bg-red-50 transition duration-150 shadow-sm inline-block">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="px-3 py-4 text-sm text-center text-indigo-700">No students found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="bg-indigo-50/50 px-4 py-3 border-t border-indigo-100 flex items-center justify-between">
                    <div class="text-xs text-indigo-700">Showing <span class="font-medium"><?php echo $result->num_rows; ?></span> entries</div>
                    <div class="flex items-center space-x-1">
                        <!-- Pagination would go here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Modal -->
    <div id="addModal" class="modal-wrapper bg-indigo-950/40 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl p-5 max-w-md w-full border border-indigo-100 mx-4">
            <div class="flex justify-between items-center mb-4">
                <div class="flex items-center">
                    <div class="bg-gradient-to-br from-blue-400 to-indigo-500 p-2 rounded-md shadow-lg mr-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                    </div>
                    <h2 class="text-lg font-bold text-indigo-950">Add New Patron</h2>
                </div>
                <button onclick="toggleModal('addModal')" class="text-indigo-400 hover:text-indigo-600 bg-indigo-50 p-1.5 rounded-lg transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Form for adding new patron -->
            <form method="POST" action="">
                <div class="space-y-3 mb-4">
                    <p class="text-xs text-indigo-600/70 mb-3">Enter the details of the new library patron</p>

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-indigo-900">LRN</label>
                        <input type="text" name="lrn" required class="w-full px-3 py-2 border border-indigo-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-indigo-50/50 text-sm">
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-indigo-900">Name</label>
                        <input type="text" name="name" required class="w-full px-3 py-2 border border-indigo-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-indigo-50/50 text-sm">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block text-xs font-medium text-indigo-900">Grade Level</label>
                            <select name="grade_level" required class="w-full px-3 py-2 border border-indigo-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-indigo-50/50 text-sm">
                                <option value="">Select Grade</option>
                                <option value="Grade 7">Grade 7</option>
                                <option value="Grade 8">Grade 8</option>
                                <option value="Grade 9">Grade 9</option>
                                <option value="Grade 10">Grade 10</option>
                                <option value="Grade 11">Grade 11</option>
                                <option value="Grade 12">Grade 12</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-medium text-indigo-900">Section</label>
                            <input type="text" name="section" required class="w-full px-3 py-2 border border-indigo-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-indigo-50/50 text-sm">
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-indigo-900">Strand</label>
                        <select name="strand" required class="w-full px-3 py-2 border border-indigo-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-indigo-50/50 text-sm">
                            <option value="">Select Strand</option>
                            <option value="STEM">STEM</option>
                            <option value="ABM">ABM</option>
                            <option value="HUMSS">HUMSS</option>
                            <option value="ICT">ICT</option>
                            <option value="HE">HE</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-indigo-900">Status</label>
                        <div class="flex space-x-4 mt-1">
                            <label class="inline-flex items-center">
                                <input type="radio" name="status" value="Active" class="form-radio text-indigo-600 focus:ring-indigo-500" checked>
                                <span class="ml-2 text-sm text-indigo-800">Active</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="status" value="Inactive" class="form-radio text-indigo-600 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-indigo-800">Inactive</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="toggleModal('addModal')" class="px-3 py-2 border border-indigo-200 rounded-lg text-indigo-700 font-medium hover:bg-indigo-50 transition text-sm">Cancel</button>
                    <button type="submit" name="add_patron" class="px-3 py-2 bg-gradient-to-r from-indigo-600 to-blue-700 rounded-lg text-white font-medium hover:from-indigo-500 hover:to-blue-600 transition shadow-lg text-sm">Save Patron</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Import CSV Modal -->
    <div id="importModal" class="modal-wrapper bg-indigo-950/40 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl p-5 max-w-md w-full border border-indigo-100 mx-4">
            <div class="flex justify-between items-center mb-4">
                <div class="flex items-center">
                    <div class="bg-gradient-to-br from-purple-400 to-pink-500 p-2 rounded-md shadow-lg mr-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                    </div>
                    <h2 class="text-lg font-bold text-indigo-950">Import CSV</h2>
                </div>
                <button onclick="toggleModal('importModal')" class="text-indigo-400 hover:text-indigo-600 bg-indigo-50 p-1.5 rounded-lg transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Form for importing CSV -->
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="space-y-3 mb-4">
                    <p class="text-xs text-indigo-600/70 mb-3">Upload a CSV file to import student data</p>

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-indigo-900">CSV File</label>
                        <input type="file" name="csv_file" accept=".csv" required class="w-full px-3 py-2 border border-indigo-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-indigo-50/50 text-sm">
                    </div>
                    <div class="text-xs text-indigo-600/70">
                        <p>CSV format should have these columns in order:</p>
                        <p class="font-mono mt-1">LRN, Name, Grade_Level, Section, Strand, Status</p>
                    </div>
                </div>

                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="toggleModal('importModal')" class="px-3 py-2 border border-indigo-200 rounded-lg text-indigo-700 font-medium hover:bg-indigo-50 transition text-sm">Cancel</button>
                    <button type="submit" name="import_csv" class="px-3 py-2 bg-gradient-to-r from-purple-600 to-pink-700 rounded-lg text-white font-medium hover:from-purple-500 hover:to-pink-600 transition shadow-lg text-sm">Import CSV</button>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript for modal toggle -->
    <script>
        function toggleModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal.classList.contains('active')) {
                modal.classList.remove('active');
            } else {
                modal.classList.add('active');
            }
        }
    </script>
</body>
</html>