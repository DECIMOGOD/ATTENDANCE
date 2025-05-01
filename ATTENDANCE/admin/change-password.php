<?php
session_start();
include 'db_connection.php';

// Change Password Only
if (isset($_POST['change_password'])) {
    if ($_POST['new_password'] === $_POST['confirm_password']) {
        $new_password = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
        $updatePasswordQuery = "UPDATE admin_users SET password='$new_password' WHERE username='{$_SESSION['admin_username']}'";
        $conn->query($updatePasswordQuery);
        $_SESSION['success'] = "Password changed successfully!";
    } else {
        $_SESSION['error'] = "Passwords do not match!";
    }
    header("Location: change-password.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-indigo-950/5 flex min-h-screen">
    
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="flex-1 p-4">
        <div class="w-full mx-auto">
            <!-- Header section -->
            <div class="mb-4">
                <h1 class="text-2xl font-bold text-indigo-950">System Settings</h1>
                <p class="text-indigo-600/70">Manage your account password</p>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-gradient-to-r from-green-500 to-emerald-600 text-white p-3 rounded-lg mb-4 shadow-md flex items-center">
                    <p><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-gradient-to-r from-red-500 to-rose-600 text-white p-3 rounded-lg mb-4 shadow-md flex items-center">
                    <p><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
                </div>
            <?php endif; ?>

            <!-- Password Change Card Only -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-indigo-100">
                <div class="p-4 bg-gradient-to-r from-indigo-900 via-blue-950 to-indigo-950 text-white">
                    <div class="flex items-center">
                        <div class="bg-white/20 p-2 rounded-lg shadow-inner mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white">Security Settings</h2>
                            <p class="text-xs text-blue-200">Update your account password</p>
                        </div>
                    </div>
                </div>
                
                <div class="p-6">
                    <form method="POST" id="passwordForm">
                        <div class="space-y-4">
                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-indigo-900">New Password</label>
                                <input type="password" name="new_password" class="w-full px-3 py-2 border border-indigo-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-indigo-50/50 text-sm" required>
                            </div>
                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-indigo-900">Confirm Password</label>
                                <input type="password" name="confirm_password" class="w-full px-3 py-2 border border-indigo-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-indigo-50/50 text-sm" required>
                            </div>

                            <div class="pt-2">
                                <button type="submit" name="change_password" class="px-4 py-2 bg-gradient-to-r from-amber-500 to-yellow-600 rounded-lg text-white font-medium hover:from-amber-400 hover:to-yellow-500 transition shadow-lg text-sm flex items-center">
                                    Update Password
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Password match validation
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const newPass = this.querySelector('input[name="new_password"]');
            const confirmPass = this.querySelector('input[name="confirm_password"]');
            
            if (newPass.value !== confirmPass.value) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Password Mismatch',
                    text: 'Passwords do not match!',
                    confirmButtonColor: '#4f46e5'
                });
            }
        });
    </script>
</body>
</html>