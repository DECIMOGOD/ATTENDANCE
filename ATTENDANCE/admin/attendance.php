<?php 
session_start(); 
include 'db_connection.php';  

// Initialize filter variables
$dateFilter = $_GET['date_filter'] ?? 'all';
$searchTerm = $_GET['search'] ?? '';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

// Base query
$query = "SELECT a.LRN, s.Name, a.time_in, a.time_out            
          FROM attendance_records a            
          JOIN students s ON a.LRN = s.LRN";

// Apply filters
$whereClauses = [];
$params = [];
$types = '';

switch ($dateFilter) {
    case 'today':
        $whereClauses[] = "DATE(a.time_in) = CURDATE()";
        break;
    case 'yesterday':
        $whereClauses[] = "DATE(a.time_in) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        break;
    case 'week':
        $whereClauses[] = "YEARWEEK(a.time_in, 1) = YEARWEEK(CURDATE(), 1)";
        break;
    case 'month':
        $whereClauses[] = "MONTH(a.time_in) = MONTH(CURDATE()) AND YEAR(a.time_in) = YEAR(CURDATE())";
        break;
    case 'custom':
        if (!empty($startDate)) {
            $whereClauses[] = "DATE(a.time_in) >= ?";
            $params[] = $startDate;
            $types .= 's';
        }
        if (!empty($endDate)) {
            $whereClauses[] = "DATE(a.time_in) <= ?";
            $params[] = $endDate;
            $types .= 's';
        }
        break;
}

// Apply search filter
if (!empty($searchTerm)) {
    $whereClauses[] = "(a.LRN LIKE ? OR s.Name LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params = array_merge($params, array_fill(0, 2, $searchParam));
    $types .= 'ss';
}

// Combine where clauses
if (!empty($whereClauses)) {
    $query .= " WHERE " . implode(" AND ", $whereClauses);
}

// Complete query
$query .= " ORDER BY a.time_in DESC";

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
    header('Content-Disposition: attachment; filename=attendance_records.csv');     
    $output = fopen('php://output', 'w');     
    fputcsv($output, ['LRN', 'Name', 'Time In', 'Time Out']);     
    while ($row = $result->fetch_assoc()) {         
        fputcsv($output, $row);     
    }     
    fclose($output);     
    exit(); 
} 

// Pagination
$totalRecords = $result->num_rows;
$recordsPerPage = 10;
$totalPages = ceil($totalRecords / $recordsPerPage);
$currentPage = isset($_GET['page']) ? max(1, min($totalPages, intval($_GET['page']))) : 1;
$offset = ($currentPage - 1) * $recordsPerPage;

// Modify query for pagination
$query .= " LIMIT ? OFFSET ?";
$params[] = $recordsPerPage;
$params[] = $offset;
$types .= 'ii';

// Re-execute with pagination
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?> 

<!DOCTYPE html> 
<html lang="en"> 
<head>     
    <meta charset="UTF-8">     
    <meta name="viewport" content="width=device-width, initial-scale=1.0">     
    <title>Attendance Records</title>     
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        input[type="date"] {
            position: relative;
            padding-right: 1.5rem;
            min-width: 120px;
        }
        input[type="date"]::-webkit-calendar-picker-indicator {
            position: absolute;
            right: 0.5rem;
            opacity: 0.7;
            cursor: pointer;
        }
        input[type="date"]:hover::-webkit-calendar-picker-indicator {
            opacity: 1;
        }
        .modal {
            transition: all 0.3s ease;
            transform: translateY(20px);
            opacity: 0;
        }
        .modal.active {
            transform: translateY(0);
            opacity: 1;
        }
        .backdrop-blur {
            backdrop-filter: blur(5px);
            background-color: rgba(0, 0, 0, 0.5);
        }
    </style>
</head> 
<body class="bg-indigo-950/5 flex min-h-screen">     
    <!-- Sidebar -->     
    <?php include 'sidebar.php'; ?>          
    
    <!-- Main Content -->     
    <div class="flex-1 p-4">         
        <div class="w-full mx-auto">
            <div class="mb-4">
                <h1 class="text-2xl font-bold text-indigo-950">Attendance Records</h1>
                <p class="text-indigo-600/70">Track student library visits and access times</p>
            </div>
            
            <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-indigo-100">
                <div class="p-4 bg-gradient-to-r from-indigo-900 via-blue-950 to-indigo-950 text-white">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center">
                            <div class="bg-white/20 p-2 rounded-lg shadow-inner mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-white">Attendance Logs</h2>
                                <p class="text-xs text-blue-200">Record of student library visits</p>
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <form method="POST">                     
                                <button name="export_csv" class="bg-gradient-to-r from-green-600 to-emerald-600 text-white px-3 py-2 rounded-lg shadow-lg hover:from-green-500 hover:to-emerald-500 transition duration-200 flex items-center text-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    Export CSV
                                </button>                 
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Combined search and filter form -->
                <form method="GET" id="filterForm" class="bg-indigo-50/70 p-3 border-b border-indigo-100 flex flex-wrap items-center justify-between gap-2">
                    <div class="flex-grow max-w-md">
                        <div class="relative">
                            <input type="text" name="search" id="searchInput" placeholder="Search by LRN or name..." 
                                   value="<?php echo htmlspecialchars($searchTerm); ?>" 
                                   class="w-full pl-10 pr-4 py-2 rounded-lg border border-indigo-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                            <div class="absolute left-3 top-2.5 text-indigo-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2 flex-wrap">
                        <select name="date_filter" id="dateFilter" class="px-3 py-2 border border-indigo-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm bg-white">
                            <option value="all" <?= $dateFilter === 'all' ? 'selected' : '' ?>>All Dates</option>
                            <option value="today" <?= $dateFilter === 'today' ? 'selected' : '' ?>>Today</option>
                            <option value="yesterday" <?= $dateFilter === 'yesterday' ? 'selected' : '' ?>>Yesterday</option>
                            <option value="week" <?= $dateFilter === 'week' ? 'selected' : '' ?>>This Week</option>
                            <option value="month" <?= $dateFilter === 'month' ? 'selected' : '' ?>>This Month</option>
                            <option value="custom" <?= $dateFilter === 'custom' ? 'selected' : '' ?>>Custom Range</option>
                        </select>
                        
                        <div id="customDateRange" class="flex items-center space-x-2 <?= $dateFilter !== 'custom' ? 'hidden' : '' ?>">
                            <div class="relative">
                                <input type="date" name="start_date" id="startDate" value="<?= htmlspecialchars($startDate) ?>" 
                                       class="px-3 py-2 border border-indigo-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                                <label for="startDate" class="absolute -top-2 left-2 bg-white px-1 text-xs text-indigo-600">From</label>
                            </div>
                            <span class="text-indigo-400">to</span>
                            <div class="relative">
                                <input type="date" name="end_date" id="endDate" value="<?= htmlspecialchars($endDate) ?>" 
                                       class="px-3 py-2 border border-indigo-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                                <label for="endDate" class="absolute -top-2 left-2 bg-white px-1 text-xs text-indigo-600">To</label>
                            </div>
                            <button type="button" id="applyDateRange" class="bg-indigo-600 text-white px-3 py-2 rounded-lg hover:bg-indigo-700 transition duration-200 text-sm">
                                Apply
                            </button>
                        </div>
                    </div>
                </form>
                
                <div class="overflow-x-auto">
                    <table id="attendanceTable" class="w-full bg-white">                
                        <thead>                     
                            <tr class="bg-indigo-50 border-b border-indigo-100">                         
                                <th class="px-3 py-3 text-left text-xs font-semibold text-indigo-800 uppercase tracking-wider">LRN</th>                         
                                <th class="px-3 py-3 text-left text-xs font-semibold text-indigo-800 uppercase tracking-wider">Name</th>                         
                                <th class="px-3 py-3 text-left text-xs font-semibold text-indigo-800 uppercase tracking-wider">Time In</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-indigo-800 uppercase tracking-wider">Time Out</th>                         
                                <th class="px-3 py-3 text-right text-xs font-semibold text-indigo-800 uppercase tracking-wider">Actions</th>                     
                            </tr>                 
                        </thead>                 
                        <tbody class="divide-y divide-indigo-100">                     
                            <?php while ($row = $result->fetch_assoc()): ?>                         
                            <tr class="hover:bg-indigo-50/30 transition duration-150">                             
                                <td class="px-3 py-3 text-sm text-indigo-700"><?= htmlspecialchars($row['LRN']) ?></td>                             
                                <td class="px-3 py-3 text-sm font-medium text-indigo-900"><?= htmlspecialchars($row['Name']) ?></td>                             
                                <td class="px-3 py-3 text-sm text-indigo-700"><?= htmlspecialchars($row['time_in']) ?></td>
                                <td class="px-3 py-3 text-sm text-indigo-700">
                                    <?= empty($row['time_out']) ? 
                                        '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Still in library</span>' : 
                                        htmlspecialchars($row['time_out']) ?>
                                </td>                             
                                <td class="px-3 py-3 text-sm text-right">                                 
                                    <button onclick="showDetailsModal('<?= htmlspecialchars($row['LRN']) ?>', '<?= htmlspecialchars($row['Name']) ?>', '<?= htmlspecialchars($row['time_in']) ?>', '<?= empty($row['time_out']) ? 'Still in library' : htmlspecialchars($row['time_out']) ?>')" class="bg-white border border-indigo-200 text-indigo-700 p-1.5 rounded-lg hover:bg-indigo-100 transition duration-150 shadow-sm" title="View Details">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>                             
                                </td>                         
                            </tr>                     
                            <?php endwhile; ?>                 
                        </tbody>             
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="bg-indigo-50/50 px-4 py-3 border-t border-indigo-100 flex items-center justify-between">
                    <div class="text-xs text-indigo-700">
                        Showing <span class="font-medium"><?= ($offset + 1) ?></span> to 
                        <span class="font-medium"><?= min($offset + $recordsPerPage, $totalRecords) ?></span> of 
                        <span class="font-medium"><?= $totalRecords ?></span> entries
                    </div>
                    <div class="flex items-center space-x-1">
                        <?php if ($currentPage > 1): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage - 1])) ?>" 
                               class="px-3 py-1.5 border border-indigo-200 rounded-md text-indigo-800 text-sm font-medium bg-white hover:bg-indigo-50">
                                Previous
                            </a>
                        <?php else: ?>
                            <span class="px-3 py-1.5 border border-indigo-200 rounded-md text-indigo-800 text-sm font-medium bg-white opacity-50 cursor-not-allowed">
                                Previous
                            </span>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                               class="px-3 py-1.5 border rounded-md text-sm font-medium <?= $i == $currentPage ? 'border-indigo-500 text-white bg-indigo-600 hover:bg-indigo-700' : 'border-indigo-200 text-indigo-800 bg-white hover:bg-indigo-50' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($currentPage < $totalPages): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage + 1])) ?>" 
                               class="px-3 py-1.5 border border-indigo-200 rounded-md text-indigo-800 text-sm font-medium bg-white hover:bg-indigo-50">
                                Next
                            </a>
                        <?php else: ?>
                            <span class="px-3 py-1.5 border border-indigo-200 rounded-md text-indigo-800 text-sm font-medium bg-white opacity-50 cursor-not-allowed">
                                Next
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>     
    </div>
    
    <!-- Details Modal -->
    <div id="detailsModal" class="fixed inset-0 z-50 flex items-center justify-center backdrop-blur hidden">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md modal">
            <div class="p-4 bg-gradient-to-r from-indigo-900 via-blue-950 to-indigo-950 text-white rounded-t-lg">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-bold">Attendance Details</h3>
                    <button onclick="closeDetailsModal()" class="text-white hover:text-indigo-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div>
                        <p class="text-sm font-medium text-indigo-600">LRN</p>
                        <p id="detailLRN" class="mt-1 text-sm text-gray-700"></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-indigo-600">Student Name</p>
                        <p id="detailName" class="mt-1 text-sm text-gray-700"></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-indigo-600">Time In</p>
                        <p id="detailTimeIn" class="mt-1 text-sm text-gray-700"></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-indigo-600">Time Out</p>
                        <p id="detailTimeOut" class="mt-1 text-sm text-gray-700"></p>
                    </div>
                </div>
            </div>
            <div class="px-4 py-3 bg-gray-50 rounded-b-lg flex justify-end">
                <button onclick="closeDetailsModal()" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition duration-200">
                    Close
                </button>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize date pickers with default values if empty
            const today = new Date().toISOString().split('T')[0];
            const startDate = document.getElementById('startDate');
            const endDate = document.getElementById('endDate');
            
            if (startDate && !startDate.value) {
                startDate.value = today;
            }
            if (endDate && !endDate.value) {
                endDate.value = today;
            }
            
            // Toggle custom date range visibility based on initial selection
            toggleCustomDateRange();
            
            // Handle date filter change
            document.getElementById('dateFilter').addEventListener('change', function() {
                toggleCustomDateRange();
                
                // If not custom range, submit the form immediately
                if (this.value !== 'custom') {
                    document.getElementById('filterForm').submit();
                }
            });
            
            // Handle apply button click for custom range
            document.getElementById('applyDateRange').addEventListener('click', function() {
                document.getElementById('filterForm').submit();
            });
            
            // Auto-submit search form when user stops typing
            let searchTimer;
            document.getElementById('searchInput').addEventListener('keyup', function() {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => {
                    document.getElementById('filterForm').submit();
                }, 800);
            });
            
            function toggleCustomDateRange() {
                const dateFilter = document.getElementById('dateFilter');
                const customDateRange = document.getElementById('customDateRange');
                
                if (dateFilter.value === 'custom') {
                    customDateRange.classList.remove('hidden');
                } else {
                    customDateRange.classList.add('hidden');
                }
            }
        });

        // Show details modal with record information
        function showDetailsModal(lrn, name, timeIn, timeOut) {
            document.getElementById('detailLRN').textContent = lrn;
            document.getElementById('detailName').textContent = name;
            document.getElementById('detailTimeIn').textContent = timeIn;
            document.getElementById('detailTimeOut').textContent = timeOut === 'Still in library' ? 
                '<span class="text-yellow-600">Still in library</span>' : timeOut;
            
            const modal = document.getElementById('detailsModal');
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.querySelector('.modal').classList.add('active');
            }, 10);
        }

        // Close details modal
        function closeDetailsModal() {
            const modal = document.getElementById('detailsModal');
            modal.querySelector('.modal').classList.remove('active');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        // Close modal when clicking outside
        document.getElementById('detailsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDetailsModal();
            }
        });
    </script>
</body> 
</html>