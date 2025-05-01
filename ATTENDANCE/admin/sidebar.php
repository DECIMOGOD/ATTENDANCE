<aside class="bg-gradient-to-br from-indigo-900 via-blue-950 to-indigo-950 text-white w-72 min-h-screen flex flex-col shadow-2xl">
    <!-- Header with logo -->
    <div class="px-7 py-8 border-b border-white/10">
        <div class="flex items-center space-x-4">
            <div class="bg-gradient-to-br from-blue-400 to-indigo-500 p-2.5 rounded-md shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                </svg>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-white">Admin Panel</h2>
                <p class="text-sm text-blue-200 font-medium">Library Attendance System</p>
            </div>
        </div>
    </div>
    
    <!-- Main Navigation -->
    <nav class="flex-grow px-4 py-6">
        <p class="text-xs uppercase tracking-wider text-blue-300 font-semibold mb-5 pl-4">Main Navigation</p>
        <ul class="space-y-3">
            <li>
                <a href="dashboard.php" class="flex items-center px-4 py-3.5 rounded-xl transition-all duration-200 hover:bg-gradient-to-r hover:from-blue-600/40 hover:to-indigo-600/40 group" id="nav-dashboard">
                    <span class="bg-white/10 p-2 rounded-lg mr-4 group-hover:bg-white/20 transition-all duration-200 shadow">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                    </span>
                    <div>
                        <span class="font-medium text-blue-50 group-hover:text-white transition-colors">Dashboard</span>
                        <p class="text-xs text-blue-300/80 group-hover:text-blue-200 transition-colors">Overview & Statistics</p>
                    </div>
                </a>
            </li>
            
            <li>
                <a href="librarypatrons.php" class="flex items-center px-4 py-3.5 rounded-xl transition-all duration-200 hover:bg-gradient-to-r hover:from-blue-600/40 hover:to-indigo-600/40 group" id="nav-patrons">
                    <span class="bg-white/10 p-2 rounded-lg mr-4 group-hover:bg-white/20 transition-all duration-200 shadow">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </span>
                    <div>
                        <span class="font-medium text-blue-50 group-hover:text-white transition-colors">Library Patrons</span>
                        <p class="text-xs text-blue-300/80 group-hover:text-blue-200 transition-colors">User Management</p>
                    </div>
                </a>
            </li>
            
            <li>
                <a href="attendance.php" class="flex items-center px-4 py-3.5 rounded-xl transition-all duration-200 hover:bg-gradient-to-r hover:from-blue-600/40 hover:to-indigo-600/40 group" id="nav-attendance">
                    <span class="bg-white/10 p-2 rounded-lg mr-4 group-hover:bg-white/20 transition-all duration-200 shadow">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                    </span>
                    <div>
                        <span class="font-medium text-blue-50 group-hover:text-white transition-colors">Attendance Records</span>
                        <p class="text-xs text-blue-300/80 group-hover:text-blue-200 transition-colors">Visit Tracking</p>
                    </div>
                </a>
            </li>
        </ul>
    </nav>
    
    <!-- User profile and logout section -->
    <div class="p-5 mt-auto border-t border-indigo-700/30">
        <div class="flex items-center justify-between p-3 bg-indigo-800/50 rounded-xl mb-5 backdrop-blur-sm shadow-md">
            <div class="flex items-center">
                <div class="w-11 h-11 rounded-full bg-gradient-to-r from-blue-300 to-indigo-300 flex items-center justify-center text-indigo-900 font-bold mr-3 shadow-md">
                    AD
                </div>
                <div>
                    <h3 class="font-medium text-sm text-white">Admin User</h3>
                    <p class="text-xs text-blue-200">Administrator</p>
                </div>
            </div>
            <div class="relative">
                <button id="profileMenuButton" class="text-blue-200 hover:text-white p-1.5 rounded-full hover:bg-indigo-700/50 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                    </svg>
                </button>
                <!-- Dropdown menu -->
                <div id="profileDropdown" class="hidden absolute bottom-full right-0 mb-2 w-48 bg-indigo-800 rounded-lg shadow-lg z-10 py-2 border border-indigo-700/50">

                    <a href="change-password.php" class="flex items-center px-4 py-2.5 text-sm text-blue-100 hover:bg-indigo-700/70 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                        Change Password
                    </a>
                </div>
            </div>
        </div>
        
        <a href="logout.php" class="flex items-center justify-center w-full px-4 py-3.5 rounded-xl transition-all duration-200 bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-500 hover:to-pink-500 focus:outline-none focus:ring-2 focus:ring-red-400/50 shadow-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            <span class="font-medium text-white">Logout</span>
        </a>
    </div>
</aside>

<!-- JavaScript to set active state and dropdown functionality -->
<script>
    // Function to set active menu based on current page
    function setActiveMenu() {
        // Get current page filename
        const path = window.location.pathname;
        const page = path.split('/').pop();
        
        // Remove active classes from all nav items
        document.querySelectorAll('nav a').forEach(item => {
            item.classList.remove('bg-gradient-to-r', 'from-blue-600/80', 'to-indigo-600/80', 'backdrop-blur-sm', 'shadow-lg');
            
            // Reset icon container
            const iconContainer = item.querySelector('span');
            if (iconContainer) {
                iconContainer.classList.remove('bg-white/20', 'shadow-inner');
                iconContainer.classList.add('bg-white/10', 'group-hover:bg-white/20');
            }
            
            // Reset icon color
            const icon = item.querySelector('svg');
            if (icon) {
                icon.classList.remove('text-white');
                icon.classList.add('text-blue-200');
            }
            
            // Reset text styles
            const titleSpan = item.querySelector('div > span');
            const descSpan = item.querySelector('div > p');
            if (titleSpan) {
                titleSpan.classList.remove('text-white');
                titleSpan.classList.add('text-blue-50', 'group-hover:text-white');
            }
            if (descSpan) {
                descSpan.classList.remove('text-blue-200');
                descSpan.classList.add('text-blue-300/80', 'group-hover:text-blue-200');
            }
        });
        
        // Set active class based on current page
        let activeItem = null;
        
        if (page === 'dashboard.php' || page === '') {
            activeItem = document.getElementById('nav-dashboard');
        } else if (page === 'librarypatrons.php') {
            activeItem = document.getElementById('nav-patrons');
        } else if (page === 'attendance.php') {
            activeItem = document.getElementById('nav-attendance');
        }
        
        // Apply active styles if we found a match
        if (activeItem) {
            // Add active background
            activeItem.classList.add('bg-gradient-to-r', 'from-blue-600/80', 'to-indigo-600/80', 'backdrop-blur-sm', 'shadow-lg');
            activeItem.classList.remove('hover:bg-gradient-to-r', 'hover:from-blue-600/40', 'hover:to-indigo-600/40');
            
            // Update icon container
            const iconContainer = activeItem.querySelector('span');
            if (iconContainer) {
                iconContainer.classList.remove('bg-white/10', 'group-hover:bg-white/20');
                iconContainer.classList.add('bg-white/20', 'shadow-inner');
            }
            
            // Update icon
            const icon = activeItem.querySelector('svg');
            if (icon) {
                icon.classList.remove('text-blue-200');
                icon.classList.add('text-white');
            }
            
            // Update text styles
            const titleSpan = activeItem.querySelector('div > span');
            const descSpan = activeItem.querySelector('div > p');
            if (titleSpan) {
                titleSpan.classList.remove('text-blue-50', 'group-hover:text-white');
                titleSpan.classList.add('text-white');
            }
            if (descSpan) {
                descSpan.classList.remove('text-blue-300/80', 'group-hover:text-blue-200');
                descSpan.classList.add('text-blue-200');
            }
        }
    }
    
    // Toggle dropdown menu for profile button
    document.addEventListener('DOMContentLoaded', function() {
        setActiveMenu();
        
        const profileButton = document.getElementById('profileMenuButton');
        const profileDropdown = document.getElementById('profileDropdown');
        
        if (profileButton && profileDropdown) {
            // Toggle dropdown on button click
            profileButton.addEventListener('click', function(e) {
                e.stopPropagation();
                profileDropdown.classList.toggle('hidden');
            });
            
            // Close dropdown when clicking elsewhere
            document.addEventListener('click', function(e) {
                if (!profileButton.contains(e.target) && !profileDropdown.contains(e.target)) {
                    profileDropdown.classList.add('hidden');
                }
            });
        }
    });
    
    // Also call if you're using SPA navigation or AJAX
    if (typeof window.customNavigate === 'function') {
        window.addEventListener('customnavigate', setActiveMenu);
    }
</script>