<?php
session_start();
// Check if admin is logged in
if (!isset($_SESSION['admin_username'])) {
    header("Location: login.php");
    exit();
}

// Database connection
include 'db_connection.php';

// Fetch top 5 frequent visitors (changed from 3 to 5)
$frequentVisitorsQuery = "SELECT students.LRN, students.Name, COUNT(*) AS visit_count 
    FROM attendance_records 
    JOIN students ON attendance_records.LRN = students.LRN 
    GROUP BY students.LRN, students.Name 
    ORDER BY visit_count DESC 
    LIMIT 5";
$frequentVisitorsResult = $conn->query($frequentVisitorsQuery);

// Fetch recent activity with time_out (removed click action)
$recentActivityQuery = "SELECT students.LRN, students.Name, 
    DATE_FORMAT(attendance_records.time_in, '%Y-%m-%d %h:%i %p') as formatted_time_in,
    IFNULL(DATE_FORMAT(attendance_records.time_out, '%Y-%m-%d %h:%i %p'), 'Still in library') as formatted_time_out
    FROM attendance_records 
    JOIN students ON attendance_records.LRN = students.LRN 
    ORDER BY attendance_records.time_in DESC 
    LIMIT 5";
$recentActivityResult = $conn->query($recentActivityQuery);

// Fetch total visitors count
$totalVisitorsQuery = "SELECT COUNT(DISTINCT LRN) as total FROM attendance_records";
$totalVisitorsResult = $conn->query($totalVisitorsQuery);
$totalVisitors = $totalVisitorsResult->fetch_assoc()['total'];

// Fetch all unique visitors (for the popup)
$uniqueVisitorsQuery = "SELECT DISTINCT students.LRN, students.Name 
    FROM attendance_records 
    JOIN students ON attendance_records.LRN = students.LRN 
    ORDER BY students.Name";
$uniqueVisitorsResult = $conn->query($uniqueVisitorsQuery);
$uniqueVisitors = [];
while ($row = $uniqueVisitorsResult->fetch_assoc()) {
    $uniqueVisitors[] = $row;
}

// Fetch most frequent visitor with their visit history
$mostFrequentVisitorQuery = "SELECT students.Name, COUNT(*) AS visit_count 
    FROM attendance_records 
    JOIN students ON attendance_records.LRN = students.LRN 
    GROUP BY students.LRN, students.Name 
    ORDER BY visit_count DESC 
    LIMIT 1";
$mostFrequentVisitorResult = $conn->query($mostFrequentVisitorQuery);
$mostFrequentVisitor = $mostFrequentVisitorResult->num_rows > 0 ? $mostFrequentVisitorResult->fetch_assoc() : ['Name' => 'No data', 'visit_count' => ''];

// Get visit history for most frequent visitor
$mostFrequentVisits = [];
if (!empty($mostFrequentVisitor['Name'])) {
    $visitHistoryQuery = "SELECT DATE_FORMAT(time_in, '%Y-%m-%d %h:%i %p') as formatted_time_in,
                         DATE_FORMAT(time_out, '%Y-%m-%d %h:%i %p') as formatted_time_out
                         FROM attendance_records 
                         JOIN students ON attendance_records.LRN = students.LRN 
                         WHERE students.Name = ?
                         ORDER BY time_in DESC";
    $stmt = $conn->prepare($visitHistoryQuery);
    $stmt->bind_param("s", $mostFrequentVisitor['Name']);
    $stmt->execute();
    $visitHistoryResult = $stmt->get_result();
    while ($row = $visitHistoryResult->fetch_assoc()) {
        $mostFrequentVisits[] = $row;
    }
}

// Fetch peak library hour with visitors during that hour
$peakHourQuery = "SELECT HOUR(time_in) as hour, COUNT(*) as count 
    FROM attendance_records 
    GROUP BY HOUR(time_in) 
    ORDER BY count DESC 
    LIMIT 1";
$peakHourResult = $conn->query($peakHourQuery);
$peakHour = $peakHourResult->num_rows > 0 ? $peakHourResult->fetch_assoc() : null;

// Get visitors during peak hour
$peakHourVisitors = [];
if ($peakHour) {
    $peakVisitorsQuery = "SELECT students.LRN, students.Name, 
                         DATE_FORMAT(time_in, '%Y-%m-%d %h:%i %p') as formatted_time_in
                         FROM attendance_records 
                         JOIN students ON attendance_records.LRN = students.LRN 
                         WHERE HOUR(time_in) = ?
                         ORDER BY time_in DESC";
    $stmt = $conn->prepare($peakVisitorsQuery);
    $stmt->bind_param("i", $peakHour['hour']);
    $stmt->execute();
    $peakVisitorsResult = $stmt->get_result();
    while ($row = $peakVisitorsResult->fetch_assoc()) {
        $peakHourVisitors[] = $row;
    }
}

// Daily attendance data for last 7 days
$dailyAttendanceQuery = "SELECT 
    DATE(time_in) as date, 
    COUNT(*) as count 
    FROM attendance_records 
    WHERE time_in >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(time_in)
    ORDER BY DATE(time_in)";
$dailyAttendanceResult = $conn->query($dailyAttendanceQuery);

$dailyLabels = [];
$dailyData = [];
$dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

// Initialize with empty data for last 7 days
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dayName = $dayNames[date('w', strtotime($date))];
    $dailyLabels[] = $dayName;
    $dailyData[$date] = 0;
}

// Fill with actual data
while ($row = $dailyAttendanceResult->fetch_assoc()) {
    $dailyData[$row['date']] = $row['count'];
}
$dailyValues = array_values($dailyData);

// Monthly attendance data for last 12 months
$monthlyAttendanceQuery = "SELECT 
    DATE_FORMAT(time_in, '%Y-%m') as month,
    COUNT(*) as count 
    FROM attendance_records 
    WHERE time_in >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(time_in, '%Y-%m')
    ORDER BY DATE_FORMAT(time_in, '%Y-%m')";
$monthlyAttendanceResult = $conn->query($monthlyAttendanceQuery);

$monthlyLabels = [];
$monthlyData = [];
$months = [];

// Generate last 12 months
for ($i = 11; $i >= 0; $i--) {
    $date = date('Y-m', strtotime("-$i months"));
    $months[$date] = 0;
}

// Fill with actual data
while ($row = $monthlyAttendanceResult->fetch_assoc()) {
    $months[$row['month']] = $row['count'];
}

// Prepare labels and data
foreach ($months as $month => $count) {
    $monthlyLabels[] = date('M', strtotime($month));
    $monthlyData[] = $count;
}

// Check for query errors
if (!$frequentVisitorsResult || !$recentActivityResult || !$totalVisitorsResult || 
    !$mostFrequentVisitorResult || !$peakHourResult || !$dailyAttendanceResult || 
    !$monthlyAttendanceResult) {
    die("Database query error: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .backdrop-blur {
            backdrop-filter: blur(5px);
            background-color: rgba(0, 0, 0, 0.5);
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
        .window {
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            background: white;
        }
        .window-header {
            background: linear-gradient(to right, #1e1b4b, #172554, #1e1b4b);
            color: white;
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .window-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }
        .window-controls {
            display: flex;
            gap: 8px;
        }
        .window-control {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            cursor: pointer;
        }
        .window-control.close {
            background: #ef4444;
        }
        .window-control.minimize {
            background: #f59e0b;
        }
        .window-control.maximize {
            background: #10b981;
        }
        .window-body {
            padding: 20px;
            max-height: 70vh;
            overflow-y: auto;
        }
        .visitor-card {
            transition: all 0.2s ease;
        }
        .visitor-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-indigo-950/5 flex min-h-screen">
    
    <?php include 'sidebar.php'; ?>
    
    <div class="flex-1 p-6" id="main-content">
        <div class="w-full mx-auto">
            <!-- Header section with greeting -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-indigo-950">Dashboard</h1>
                <p class="text-indigo-600/70">Welcome, <span class="font-semibold"><?php echo $_SESSION['admin_username']; ?></span>!</p>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-indigo-100 cursor-pointer hover:shadow-xl transition-shadow duration-200" 
                     onclick="showWindow('Total Visitors', '<?php echo $totalVisitors; ?> unique student visits', 'users')">
                    <div class="p-3 bg-gradient-to-r from-indigo-900 via-blue-950 to-indigo-950 text-white">
                        <div class="flex items-center">
                            <div class="bg-white/20 p-2 rounded-lg shadow-inner mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-medium text-white">Total Visitors</h2>
                            </div>
                        </div>
                    </div>
                    <div class="p-4">
                        <p class="text-3xl font-bold text-indigo-700"><?php echo $totalVisitors; ?></p>
                        <p class="text-xs text-indigo-500 mt-1">Unique student visits</p>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-indigo-100 cursor-pointer hover:shadow-xl transition-shadow duration-200" 
                     onclick="showWindow('Most Frequent Visitor', '<?php echo $mostFrequentVisitor['Name']; ?> with <?php echo $mostFrequentVisitor['visit_count'] ? $mostFrequentVisitor['visit_count'] : '0'; ?> visits', 'user', '<?php echo htmlspecialchars(json_encode($mostFrequentVisits), ENT_QUOTES, 'UTF-8'); ?>')">
                    <div class="p-3 bg-gradient-to-r from-indigo-900 via-blue-950 to-indigo-950 text-white">
                        <div class="flex items-center">
                            <div class="bg-white/20 p-2 rounded-lg shadow-inner mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-medium text-white">Most Frequent Visitor</h2>
                            </div>
                        </div>
                    </div>
                    <div class="p-4">
                        <p class="text-xl font-bold text-indigo-700">
                            <?php echo $mostFrequentVisitor['Name']; ?>
                        </p>
                        <p class="text-xs text-indigo-500 mt-1">
                            <?php echo $mostFrequentVisitor['visit_count'] ? $mostFrequentVisitor['visit_count'] . ' visits' : ''; ?>
                        </p>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-indigo-100 cursor-pointer hover:shadow-xl transition-shadow duration-200" 
                     onclick="showWindow('Peak Library Hour', '<?php echo ($peakHour ? ($peakHour['hour'] % 12 == 0 ? 12 : $peakHour['hour'] % 12) . ':00 ' . ($peakHour['hour'] < 12 ? 'AM' : 'PM') : 'N/A'); ?> with <?php echo $peakHour ? $peakHour['count'] : '0'; ?> entries', 'clock', '<?php echo htmlspecialchars(json_encode($peakHourVisitors), ENT_QUOTES, 'UTF-8'); ?>')">
                    <div class="p-3 bg-gradient-to-r from-indigo-900 via-blue-950 to-indigo-950 text-white">
                        <div class="flex items-center">
                            <div class="bg-white/20 p-2 rounded-lg shadow-inner mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-medium text-white">Peak Library Hour</h2>
                            </div>
                        </div>
                    </div>
                    <div class="p-4">
                        <p class="text-xl font-bold text-indigo-700">
                            <?php 
                            if ($peakHour) {
                                $hour = $peakHour['hour'];
                                echo ($hour % 12 == 0 ? 12 : $hour % 12) . ':00 ' . ($hour < 12 ? 'AM' : 'PM');
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </p>
                        <p class="text-xs text-indigo-500 mt-1">
                            <?php echo $peakHour ? $peakHour['count'] . ' entries' : ''; ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-indigo-100 cursor-pointer hover:shadow-xl transition-shadow duration-200" 
                     onclick="showWindow('Daily Attendance', 'Visitor trend for the last 7 days', 'chart-bar')">
                    <div class="p-4 bg-gradient-to-r from-indigo-900 via-blue-950 to-indigo-950 text-white">
                        <div class="flex items-center">
                            <div class="bg-white/20 p-2 rounded-lg shadow-inner mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-white">Daily Attendance</h2>
                                <p class="text-xs text-blue-200">Last 7 days visitor trend</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-4">
                        <canvas id="dailyAttendanceChart" height="250"></canvas>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-indigo-100 cursor-pointer hover:shadow-xl transition-shadow duration-200" 
                     onclick="showWindow('Monthly Attendance', 'Year to date visitor analysis', 'chart-line')">
                    <div class="p-4 bg-gradient-to-r from-indigo-900 via-blue-950 to-indigo-950 text-white">
                        <div class="flex items-center">
                            <div class="bg-white/20 p-2 rounded-lg shadow-inner mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-white">Monthly Attendance</h2>
                                <p class="text-xs text-blue-200">Year to date analysis</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-4">
                        <canvas id="monthlyAttendanceChart" height="250"></canvas>
                    </div>
                </div>
            </div>

            <!-- Most Frequent Visitors (now shows top 5) -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-indigo-100 mb-6 cursor-pointer hover:shadow-xl transition-shadow duration-200" 
                 onclick="showWindow('Most Frequent Visitors', 'Top 5 library patrons by visit count', 'users', '<?php echo htmlspecialchars(json_encode($frequentVisitorsResult->fetch_all(MYSQLI_ASSOC)), ENT_QUOTES, 'UTF-8'); ?>')">
                <div class="p-4 bg-gradient-to-r from-indigo-900 via-blue-950 to-indigo-950 text-white">
                    <div class="flex items-center">
                        <div class="bg-white/20 p-2 rounded-lg shadow-inner mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white">Most Frequent Visitors</h2>
                            <p class="text-xs text-blue-200">Top 5 library patrons by visit count</p>
                        </div>
                    </div>
                </div>
                <div class="p-4">
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead class="bg-indigo-50">
                                <tr>
                                    <th class="p-2 text-left text-xs font-medium text-indigo-800 border-b border-indigo-100">LRN</th>
                                    <th class="p-2 text-left text-xs font-medium text-indigo-800 border-b border-indigo-100">Name</th>
                                    <th class="p-2 text-right text-xs font-medium text-indigo-800 border-b border-indigo-100">Visits</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-indigo-100">
                                <?php 
                                $frequentVisitorsResult->data_seek(0); // Reset pointer to beginning
                                while ($row = $frequentVisitorsResult->fetch_assoc()): ?>
                                    <tr class="hover:bg-indigo-50/50">
                                        <td class="p-2 text-sm text-indigo-700"><?php echo $row['LRN']; ?></td>
                                        <td class="p-2 text-sm font-medium text-indigo-800"><?php echo $row['Name']; ?></td>
                                        <td class="p-2 text-sm text-indigo-700 text-right"> 
                                            <span class="px-2 py-1 bg-indigo-100 text-indigo-800 rounded-full text-xs font-medium">
                                                <?php echo $row['visit_count']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Activity with Time Out (removed click action) -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-indigo-100">
                <div class="p-4 bg-gradient-to-r from-indigo-900 via-blue-950 to-indigo-950 text-white">
                    <div class="flex items-center">
                        <div class="bg-white/20 p-2 rounded-lg shadow-inner mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white">Recent Activity</h2>
                            <p class="text-xs text-blue-200">Latest 5 library check-ins with check-outs</p>
                        </div>
                    </div>
                </div>
                <div class="p-4">
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead class="bg-indigo-50">
                                <tr>
                                    <th class="p-2 text-left text-xs font-medium text-indigo-800 border-b border-indigo-100">LRN</th>
                                    <th class="p-2 text-left text-xs font-medium text-indigo-800 border-b border-indigo-100">Name</th>
                                    <th class="p-2 text-left text-xs font-medium text-indigo-800 border-b border-indigo-100">Time In</th>
                                    <th class="p-2 text-left text-xs font-medium text-indigo-800 border-b border-indigo-100">Time Out</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-indigo-100">
                                <?php while ($row = $recentActivityResult->fetch_assoc()): ?>
                                    <tr class="hover:bg-indigo-50/50">
                                        <td class="p-2 text-sm text-indigo-700"><?php echo $row['LRN']; ?></td>
                                        <td class="p-2 text-sm font-medium text-indigo-800"><?php echo $row['Name']; ?></td>
                                        <td class="p-2 text-sm text-indigo-700"> 
                                            <span class="flex items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-indigo-500 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <?php echo $row['formatted_time_in']; ?>
                                            </span>
                                        </td>
                                        <td class="p-2 text-sm text-indigo-700">
                                            <?php if ($row['formatted_time_out'] !== 'Still in library'): ?>
                                                <span class="flex items-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-indigo-500 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <?php echo $row['formatted_time_out']; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    Still in library
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Window Overlay -->
    <div id="window-overlay" class="fixed inset-0 z-50 flex items-center justify-center backdrop-blur hidden">
        <div class="window w-full max-w-2xl mx-4 modal">
            <div class="window-header">
                <div class="window-title">
                    <span id="window-icon">
                        <!-- Icon will be inserted here by JavaScript -->
                    </span>
                    <span id="window-title">Window Title</span>
                </div>
                <div class="window-controls">
                    <div class="window-control minimize" onclick="minimizeWindow()"></div>
                    <div class="window-control maximize" onclick="maximizeWindow()"></div>
                    <div class="window-control close" onclick="closeWindow()"></div>
                </div>
            </div>
            <div class="window-body bg-white" id="window-content">
                Window content goes here
            </div>
        </div>
    </div>

    <script>
        // Daily Attendance Chart with real data
        new Chart(document.getElementById('dailyAttendanceChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($dailyLabels); ?>,
                datasets: [{
                    label: 'Visitors',
                    data: <?php echo json_encode($dailyValues); ?>,
                    backgroundColor: 'rgba(79, 70, 229, 0.5)',
                    borderColor: 'rgba(79, 70, 229, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                    barThickness: 15
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: true,
                            color: 'rgba(79, 70, 229, 0.1)'
                        },
                        ticks: {
                            color: 'rgba(79, 70, 229, 0.8)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: 'rgba(79, 70, 229, 0.8)'
                        }
                    }
                }
            }
        });

        // Monthly Attendance Chart with real data
        new Chart(document.getElementById('monthlyAttendanceChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($monthlyLabels); ?>,
                datasets: [{
                    label: 'Visitors',
                    data: <?php echo json_encode($monthlyData); ?>,
                    backgroundColor: 'rgba(244, 63, 94, 0.2)',
                    borderColor: 'rgba(244, 63, 94, 1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(244, 63, 94, 1)',
                    pointBorderColor: '#fff',
                    pointRadius: 4,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: true,
                            color: 'rgba(244, 63, 94, 0.1)'
                        },
                        ticks: {
                            color: 'rgba(244, 63, 94, 0.8)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: 'rgba(244, 63, 94, 0.8)'
                        }
                    }
                }
            }
        });

        // Enhanced Window function with additional data parameter
        function showWindow(title, content, iconType, additionalData = null) {
            const overlay = document.getElementById('window-overlay');
            const windowTitle = document.getElementById('window-title');
            const windowContent = document.getElementById('window-content');
            const windowIcon = document.getElementById('window-icon');
            
            // Set window title and icon
            windowTitle.textContent = title;
            
            // Set icon based on type
            let iconHtml = '';
            let contentHtml = '';
            
            switch(iconType) {
                case 'users':
                    iconHtml = `<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>`;
                    
                    if (title === 'Total Visitors') {
                        // Special content for Total Visitors
                        contentHtml = `
                            <div class="mb-4">
                                <p class="text-sm text-gray-600">${content}</p>
                            </div>
                            <div class="max-h-96 overflow-y-auto">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <?php foreach ($uniqueVisitors as $visitor): ?>
                                        <div class="visitor-card flex items-center p-3 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors">
                                            <div class="bg-indigo-100 p-2 rounded-full mr-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="font-medium text-indigo-900"><?php echo htmlspecialchars($visitor['Name']); ?></p>
                                                <p class="text-xs text-indigo-600">LRN: <?php echo htmlspecialchars($visitor['LRN']); ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        `;
                    } else if (title === 'Most Frequent Visitors') {
                        // Content for Most Frequent Visitors (top 5)
                        const visitors = additionalData ? JSON.parse(additionalData) : [];
                        contentHtml = `
                            <div class="mb-4">
                                <p class="text-sm text-gray-600">${content}</p>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full border-collapse">
                                    <thead class="bg-indigo-50">
                                        <tr>
                                            <th class="p-2 text-left text-xs font-medium text-indigo-800 border-b border-indigo-100">Name</th>
                                            <th class="p-2 text-left text-xs font-medium text-indigo-800 border-b border-indigo-100">LRN</th>
                                            <th class="p-2 text-right text-xs font-medium text-indigo-800 border-b border-indigo-100">Visits</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-indigo-100">
                                        ${visitors.map(visitor => `
                                            <tr class="hover:bg-indigo-50/50">
                                                <td class="p-2 text-sm font-medium text-indigo-800">${visitor.Name}</td>
                                                <td class="p-2 text-sm text-indigo-700">${visitor.LRN}</td>
                                                <td class="p-2 text-sm text-indigo-700 text-right"> 
                                                    <span class="px-2 py-1 bg-indigo-100 text-indigo-800 rounded-full text-xs font-medium">
                                                        ${visitor.visit_count}
                                                    </span>
                                                </td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        `;
                    }
                    break;
                    
                case 'user':
                    iconHtml = `<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>`;
                    
                    const visits = additionalData ? JSON.parse(additionalData) : [];
                    contentHtml = `
                        <div class="mb-4">
                            <p class="text-lg font-semibold text-indigo-900">${content}</p>
                        </div>
                        <div class="max-h-96 overflow-y-auto">
                            <h3 class="text-sm font-medium text-indigo-800 mb-2">Visit History:</h3>
                            <table class="w-full border-collapse">
                                <thead class="bg-indigo-50">
                                    <tr>
                                        <th class="p-2 text-left text-xs font-medium text-indigo-800 border-b border-indigo-100">Time In</th>
                                        <th class="p-2 text-left text-xs font-medium text-indigo-800 border-b border-indigo-100">Time Out</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-indigo-100">
                                    ${visits.map(visit => `
                                        <tr class="hover:bg-indigo-50/50">
                                            <td class="p-2 text-sm text-indigo-700">
                                                <span class="flex items-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-indigo-500 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    ${visit.formatted_time_in}
                                                </span>
                                            </td>
                                            <td class="p-2 text-sm text-indigo-700">
                                                ${visit.formatted_time_out === 'Still in library' ? 
                                                    '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Still in library</span>' : 
                                                    `<span class="flex items-center">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-indigo-500 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        ${visit.formatted_time_out}
                                                    </span>`
                                                }
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    `;
                    break;
                    
                case 'clock':
                    iconHtml = `<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>`;
                    
                    const peakVisitors = additionalData ? JSON.parse(additionalData) : [];
                    contentHtml = `
                        <div class="mb-4">
                            <p class="text-lg font-semibold text-indigo-900">${content}</p>
                        </div>
                        <div class="max-h-96 overflow-y-auto">
                            <h3 class="text-sm font-medium text-indigo-800 mb-2">Visitors during this hour:</h3>
                            <table class="w-full border-collapse">
                                <thead class="bg-indigo-50">
                                    <tr>
                                        <th class="p-2 text-left text-xs font-medium text-indigo-800 border-b border-indigo-100">Name</th>
                                        <th class="p-2 text-left text-xs font-medium text-indigo-800 border-b border-indigo-100">LRN</th>
                                        <th class="p-2 text-left text-xs font-medium text-indigo-800 border-b border-indigo-100">Time In</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-indigo-100">
                                    ${peakVisitors.map(visitor => `
                                        <tr class="hover:bg-indigo-50/50">
                                            <td class="p-2 text-sm font-medium text-indigo-800">${visitor.Name}</td>
                                            <td class="p-2 text-sm text-indigo-700">${visitor.LRN}</td>
                                            <td class="p-2 text-sm text-indigo-700">
                                                <span class="flex items-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-indigo-500 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    ${visitor.formatted_time_in}
                                                </span>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    `;
                    break;
                    
                case 'chart-bar':
                    iconHtml = `<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>`;
                    contentHtml = `<p>${content}</p>`;
                    break;
                    
                case 'chart-line':
                    iconHtml = `<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                    </svg>`;
                    contentHtml = `<p>${content}</p>`;
                    break;
                    
                default:
                    iconHtml = `<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>`;
                    contentHtml = `<p>${content}</p>`;
            }
            
            windowIcon.innerHTML = iconHtml;
            windowContent.innerHTML = contentHtml;
            
            // Show window
            overlay.classList.remove('hidden');
            setTimeout(() => {
                document.querySelector('#window-overlay .modal').classList.add('active');
            }, 10);
            
            // Blur main content
            document.getElementById('main-content').classList.add('backdrop-blur');
        }

        function closeWindow() {
            const overlay = document.getElementById('window-overlay');
            document.querySelector('#window-overlay .modal').classList.remove('active');
            
            setTimeout(() => {
                overlay.classList.add('hidden');
                // Remove blur from main content
                document.getElementById('main-content').classList.remove('backdrop-blur');
            }, 300);
        }

        function minimizeWindow() {
            // For demo purposes, just close the window
            // In a real implementation, you would minimize it to a taskbar
            closeWindow();
        }

        function maximizeWindow() {
            const window = document.querySelector('#window-overlay .window');
            if (window.classList.contains('maximized')) {
                window.classList.remove('maximized');
                window.style.width = '';
                window.style.height = '';
                window.style.maxWidth = '42rem';
            } else {
                window.classList.add('maximized');
                window.style.width = '95vw';
                window.style.height = '95vh';
                window.style.maxWidth = 'none';
            }
        }

        // Close window when clicking outside
        document.getElementById('window-overlay').addEventListener('click', function(e) {
            if (e.target === this) {
                closeWindow();
            }
        });
    </script>
</body>
</html>