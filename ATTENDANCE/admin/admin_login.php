<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$database = "library_attendance_system";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $admin_username = $_POST['username'];
    $admin_password = $_POST['password'];

    $sql = "SELECT * FROM admin_users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $admin_username);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    if ($admin && password_verify($admin_password, $admin['password'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $admin['username'];
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid username or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Library Attendance System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .login-card {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(8px);
        }
        .gradient-bg {
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 50%, #3730a3 100%);
        }
        .input-focus:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.5);
            background: rgba(30, 41, 59, 0.8);
        }
        .btn-loading {
            position: relative;
        }
        .btn-loading::after {
            content: "";
            position: absolute;
            width: 16px;
            height: 16px;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            margin: auto;
            border: 3px solid transparent;
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: button-loading-spinner 1s ease infinite;
        }
        @keyframes button-loading-spinner {
            from { transform: rotate(0turn); }
            to { transform: rotate(1turn); }
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4 bg-black">
    <div class="login-card rounded-xl overflow-hidden w-full max-w-md">
        <!-- Header with gradient -->
        <div class="gradient-bg p-6 text-white">
            <div class="flex items-center justify-center space-x-3">
                <div class="bg-white/20 p-2 rounded-lg shadow-inner">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold">Library Attendance System</h1>
                    <p class="text-xs text-indigo-200">Administrator Access</p>
                </div>
            </div>
        </div>
        
        <!-- Login Form -->
        <form id="loginForm" action="" method="POST" class="p-6 space-y-5">
            <div class="space-y-1">
                <label for="username" class="block text-sm font-medium text-indigo-100">Username</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-indigo-400">
                        <i class="fas fa-user"></i>
                    </div>
                    <input type="text" id="username" name="username" placeholder="Enter admin username" 
                           class="pl-10 w-full p-2.5 bg-slate-900/70 border border-slate-700 rounded-lg input-focus text-indigo-100 placeholder-indigo-500 text-sm" required>
                </div>
            </div>
            
            <div class="space-y-1">
                <label for="password" class="block text-sm font-medium text-indigo-100">Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-indigo-400">
                        <i class="fas fa-lock"></i>
                    </div>
                    <input type="password" id="password" name="password" placeholder="Enter your password" 
                           class="pl-10 w-full p-2.5 bg-slate-900/70 border border-slate-700 rounded-lg input-focus text-indigo-100 placeholder-indigo-500 text-sm" required>
                </div>
            </div>
            
            <button type="submit" id="loginBtn" class="w-full gradient-bg text-white py-3 rounded-lg font-medium hover:opacity-90 transition duration-200 flex items-center justify-center shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
                Sign In
            </button>
        </form>
        
        <!-- Footer -->
        <div class="px-6 py-4 bg-slate-900/50 border-t border-slate-800 text-center">
            <p class="text-xs text-indigo-400">© <?php echo date('Y'); ?> Library Attendance System. All rights reserved.</p>
        </div>
    </div>

    <script>
        // Form submission loader
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            btn.innerHTML = 'Authenticating...';
            btn.classList.add('btn-loading');
            btn.disabled = true;
        });

        <?php if(isset($error)): ?>
            Swal.fire({
                icon: 'error',
                title: 'Login Failed',
                text: '<?php echo $error; ?>',
                background: '#1e293b',
                color: '#e2e8f0',
                confirmButtonColor: '#4f46e5'
            });
        <?php endif; ?>
    </script>
</body>
</html>