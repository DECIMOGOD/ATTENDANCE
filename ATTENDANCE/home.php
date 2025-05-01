<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Attendance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(to bottom, #272643, #2c698d);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        .card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        .warning {
            color: #ef4444;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: none;
        }
        .success {
            color: #10b981;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: none;
        }
        .loading-spinner {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .response-message {
            padding: 0.75rem;
            border-radius: 0.375rem;
            margin-top: 1rem;
            font-weight: 500;
        }
        .success-message {
            background-color: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }
        .error-message {
            background-color: #fee2e2;
            color: #dc2626;
            border: 1px solid #fca5a5;
        }
    </style>
</head>
<body class="flex items-center justify-center p-4">
    <div class="container mx-auto max-w-6xl">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Instruction Card -->
            <div class="bg-white p-6 md:p-8 rounded-lg shadow-xl border border-gray-300 card">
                <div class="flex flex-col items-center text-center">
                    <img src="img/ATI logo.png" alt="School Logo" class="w-32 h-32 md:w-40 md:h-40 mb-6">
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-4">Library Attendance System</h2>
                    
                    <div class="bg-blue-50 p-4 rounded-lg mb-6 w-full">
                        <h3 class="text-lg font-semibold text-blue-800 mb-2">Instructions:</h3>
                        <ol class="text-left text-gray-700 space-y-2 pl-4">
                            <li class="flex items-start">
                                <span class="inline-block bg-blue-100 text-blue-800 rounded-full w-6 h-6 flex items-center justify-center mr-2">1</span>
                                Enter your 12-digit LRN in the input field
                            </li>
                            <li class="flex items-start">
                                <span class="inline-block bg-blue-100 text-blue-800 rounded-full w-6 h-6 flex items-center justify-center mr-2">2</span>
                                Click "Time In" when entering the library
                            </li>
                            <li class="flex items-start">
                                <span class="inline-block bg-blue-100 text-blue-800 rounded-full w-6 h-6 flex items-center justify-center mr-2">3</span>
                                Click "Time Out" when leaving the library
                            </li>
                        </ol>
                    </div>
                    
                    <div class="w-full border-t pt-4">
                        <p class="text-gray-600 text-sm">
                            <span class="font-semibold">Note:</span> This system records your library attendance for monitoring purposes.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Attendance Form Card -->
            <div class="bg-white p-6 md:p-8 rounded-lg shadow-xl border border-gray-300 card">
                <div class="flex flex-col items-center text-center">
                    <img src="img/logow.png" alt="Library Logo" class="w-24 h-24 md:w-28 md:h-28 mb-4">
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-900 tracking-wide">Library Attendance</h2>
                    <p class="text-gray-700 mb-4 text-base md:text-lg">Enter your 12-digit LRN</p>
                    
                    <div class="text-gray-800 font-semibold text-base md:text-lg mb-4" id="datetime"></div>
                    
                    <div id="attendanceForm" class="w-full space-y-4">
                        <div>
                            <input type="text" 
                                   id="lrn" 
                                   name="lrn" 
                                   class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none text-center text-gray-900 text-lg tracking-wider" 
                                   maxlength="12" 
                                   required 
                                   pattern="\d{12}"
                                   inputmode="numeric"
                                   aria-label="12-digit LRN"
                                   placeholder="000000000000"
                                   oninput="validateLRN()"
                                   onkeypress="return isNumberKey(event)">
                            <p id="lrnWarning" class="warning">LRN must be exactly 12 digits (numbers only).</p>
                            <p id="lrnSuccess" class="success">Valid LRN format</p>
                        </div>
                        <div class="flex space-x-2">
                            <button type="button" 
                                    onclick="submitAttendance('time_in')"
                                    class="w-1/2 bg-gradient-to-r from-[#e3f6f5] to-[#bae8e8] text-black font-semibold py-3 px-4 rounded-lg hover:scale-105 transition-transform shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center justify-center"
                                    id="timeInBtn">
                                <span id="timeInText">Time In</span>
                                <span id="timeInSpinner" class="loading-spinner ml-2 hidden"></span>
                            </button>
                            <button type="button" 
                                    onclick="submitAttendance('time_out')"
                                    class="w-1/2 bg-gradient-to-r from-[#bae8e8] to-[#e3f6f5] text-black font-semibold py-3 px-4 rounded-lg hover:scale-105 transition-transform shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center justify-center"
                                    id="timeOutBtn">
                                <span id="timeOutText">Time Out</span>
                                <span id="timeOutSpinner" class="loading-spinner ml-2 hidden"></span>
                            </button>
                        </div>
                    </div>
                    
                    <div id="responseMessage" class="response-message hidden w-full mt-4"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize datetime display
        function updateDateTime() {
            const now = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            const formattedDateTime = now.toLocaleDateString(undefined, options);
            document.getElementById("datetime").textContent = formattedDateTime;
        }
        setInterval(updateDateTime, 1000);
        updateDateTime();

        // Input validation
        function isNumberKey(evt) {
            const charCode = evt.which ? evt.which : evt.keyCode;
            return !(charCode > 31 && (charCode < 48 || charCode > 57));
        }

        function validateLRN() {
            const lrnInput = document.getElementById("lrn");
            const warning = document.getElementById("lrnWarning");
            const success = document.getElementById("lrnSuccess");
            
            // Remove any non-numeric characters
            lrnInput.value = lrnInput.value.replace(/\D/g, '');
            
            if (lrnInput.value.length === 12) {
                warning.style.display = "none";
                success.style.display = "block";
                return true;
            } else {
                warning.style.display = "block";
                success.style.display = "none";
                return false;
            }
        }

        // Attendance submission function
        function submitAttendance(action) {
            if (!validateLRN()) {
                document.getElementById('lrn').focus();
                return;
            }
            
            const lrn = document.getElementById('lrn').value;
            const timeInBtn = document.getElementById('timeInBtn');
            const timeOutBtn = document.getElementById('timeOutBtn');
            const timeInSpinner = document.getElementById('timeInSpinner');
            const timeOutSpinner = document.getElementById('timeOutSpinner');
            const timeInText = document.getElementById('timeInText');
            const timeOutText = document.getElementById('timeOutText');
            const responseMessage = document.getElementById('responseMessage');
            
            // Show loading state
            if (action === 'time_in') {
                timeInBtn.disabled = true;
                timeInText.textContent = 'Processing...';
                timeInSpinner.classList.remove('hidden');
            } else {
                timeOutBtn.disabled = true;
                timeOutText.textContent = 'Processing...';
                timeOutSpinner.classList.remove('hidden');
            }
            
            // Clear previous messages
            responseMessage.classList.add('hidden');
            responseMessage.textContent = '';
            responseMessage.className = 'response-message hidden';
            
            // Submit data to server
            fetch('record_attendance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `lrn=${encodeURIComponent(lrn)}&action=${encodeURIComponent(action)}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                // Show success/error message
                responseMessage.textContent = data.message || (action === 'time_in' ? 'Time In recorded successfully' : 'Time Out recorded successfully');
                responseMessage.classList.remove('hidden');
                
                if (data.success) {
                    responseMessage.classList.add('success-message');
                    // Clear input on success
                    document.getElementById('lrn').value = '';
                } else {
                    responseMessage.classList.add('error-message');
                }
                
                // Hide message after 5 seconds
                setTimeout(() => {
                    responseMessage.classList.add('hidden');
                }, 5000);
            })
            .catch(error => {
                console.error('Error:', error);
                responseMessage.textContent = 'An error occurred. Please try again.';
                responseMessage.classList.remove('hidden');
                responseMessage.classList.add('error-message');
            })
            .finally(() => {
                // Reset buttons
                timeInBtn.disabled = false;
                timeOutBtn.disabled = false;
                timeInText.textContent = 'Time In';
                timeOutText.textContent = 'Time Out';
                timeInSpinner.classList.add('hidden');
                timeOutSpinner.classList.add('hidden');
            });
        }

        // Focus LRN input on page load
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('lrn').focus();
        });
    </script>
</body>
</html>