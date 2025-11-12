<?php
session_start();
require_once 'db_connect.php';

// If the user is not logged in, redirect them to the homepage.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

// Fetch logged-in user's details to pre-fill the form
$user_details = null;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql_user = "SELECT username, email FROM users WHERE user_id = ?";
    if ($stmt_user = mysqli_prepare($link, $sql_user)) {
        mysqli_stmt_bind_param($stmt_user, "i", $user_id);
        if (mysqli_stmt_execute($stmt_user)) {
            $result_user = mysqli_stmt_get_result($stmt_user);
            $user_details = mysqli_fetch_assoc($result_user);
        }
        mysqli_stmt_close($stmt_user);
    }
}


// Fetch blocked dates
$blocked_dates = [];
$sql_blocked = "SELECT block_date FROM blocked_dates";
if ($result = mysqli_query($link, $sql_blocked)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $blocked_dates[] = $row['block_date'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tavern Publico - Reservation</title>
    <link rel="stylesheet" href="CSS/main.css">
    <link rel="stylesheet" href="CSS/dark-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* --- ALL STYLES FOR RESERVE.PHP ARE NOW INLINED HERE --- */
        
        /* 1. Main Layout & Centering */
        .reservation-hero-section {
            position: relative;
            width: 100%;
            min-height: 100vh;
            padding: 40px 20px;
            display: flex;
            align-items: center;
            justify-content: center; /* Horizontally center the content */
            box-sizing: border-box;
            background-color: #000;
        }

        .reservation-bg-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.65;
            z-index: 0;
        }

        .reservation-overlay {
            position: relative;
            z-index: 1;
            width: 100%;
        }

        .reservation-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            align-items: stretch;
        }
        
        /* 2. Form Card Styling */
        .reservation-form-card {
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(0,0,0,0.1);
            padding: 35px 40px;
            position: relative;
            overflow: hidden;
            border-radius: 15px;
        }
        
        .reservation-form-card::before {
            content: ''; position: absolute; bottom: -20px; left: -20px;
            width: 180px; height: 180px; background-image: url('Tavern.png');
            background-size: contain; background-repeat: no-repeat;
            opacity: 0.04; z-index: 0;
        }
        .reservation-form { position: relative; z-index: 1; }
        .reservation-form-card h2 { font-size: 2.2em; color: #2c3e50; margin-bottom: 25px; text-align: left; }

        .reservation-form .form-group,
        .reservation-form .form-group-inline { margin-bottom: 15px; }
        .reservation-form .form-group-inline { display: flex; gap: 20px; }
        .reservation-form .form-group-inline .form-group { flex: 1; margin-bottom: 0; }
        
        .reservation-form .form-group label { font-weight: 600; color: #555; font-size: 0.9rem; display: block; margin-bottom: 8px; }
        .reservation-form .form-group input,
        .reservation-form .form-group select {
            width: 100%;
            background-color: #f0f2f5; border: 1px solid #ddd; border-radius: 8px;
            padding: 12px; font-size: 0.95rem; transition: all 0.3s ease; box-sizing: border-box;
        }
        .reservation-form .form-group input:read-only { background-color: #e9ecef; cursor: not-allowed; }
        .reservation-form .form-group input:focus,
        .reservation-form .form-group select:focus {
            background-color: #fff; border-color: #FFD700;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.3); outline: none;
        }

        .confirm-reservation-btn {
            width: 100%; padding: 12px; font-size: 1.1em; margin-top: 10px; background-color: #1a1a1a;
            color: #fff; border-radius: 8px; border: 1px solid #1a1a1a;
        }
        .confirm-reservation-btn:hover { background-color: #FFD700; color: #1a1a1a; border-color: #FFD700; }
        
        /* 3. "Visit Us" Card Styling */
        .hours-card {
            background-color: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
            text-align: left; padding: 40px; display: flex;
            flex-direction: column; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 15px;
            color: #fff;
        }
        .hours-card h3 { text-align: center; color: #FFD700; font-size: 1.8em; margin-bottom: 25px; }
        .hours-card .map-container { height: 180px; margin-bottom: 20px; border-radius: 8px; overflow: hidden; flex-shrink: 0; }
        .hours-card .map-container iframe { width: 100%; height: 100%; border: 0; }
        .hours-card .contact-links { display: flex; justify-content: center; gap: 20px; margin: 20px 0; padding-bottom: 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.2); flex-shrink: 0; }
        .hours-card .contact-links a { color: #FFD700; text-decoration: none; font-weight: 600; transition: color 0.3s; }
        .hours-card .contact-links a:hover { color: #fff; }
        .hours-card p, .hours-card h4 { color: #eee; line-height: 1.6; }
        .hours-card p { font-size: 1.05em; margin-bottom: 15px; }
        .hours-card p strong { color: #FFD700; }

        /* 4. Notification Modal Styles */
        #notificationModal .modal-content { background-color: #ffffff; padding: 40px; border-radius: 15px; max-width: 500px; box-shadow: 0 8px 30px rgba(0,0,0,0.3); animation: fadeIn 0.4s ease-out; text-align: center; }
        #notificationModal .close-button { position: absolute; top: 15px; right: 20px; color: #aaa; font-size: 2.2em; font-weight: lighter; cursor: pointer; transition: color 0.3s ease; }
        #notificationModal .close-button:hover { color: #555; }
        #notificationModal .modal-header-icon { font-size: 4.5em; margin-bottom: 20px; animation: bounceIn 0.8s ease-out; }
        #notificationModal .modal-header-icon.success { color: #28a745; }
        #notificationModal .modal-header-icon.error { color: #dc3545; }
        #notificationModal h2 { font-size: 2.2em; margin-bottom: 15px; color: #333; font-weight: 700; }
        #notificationModal p { font-size: 1.1em; color: #666; margin-bottom: 35px; line-height: 1.6; }
        #notificationModal .modal-close-btn { background-color: #FFD700; color: #333; border: none; padding: 12px 30px; border-radius: 8px; cursor: pointer; font-size: 1.1em; font-weight: 600; transition: all 0.2s ease; min-width: 120px; }
        #notificationModal .modal-close-btn:hover { background-color: #e6c200; transform: translateY(-2px); }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes bounceIn { 0% { transform: scale(0.3); opacity: 0; } 50% { transform: scale(1.1); opacity: 1; } 70% { transform: scale(0.9); } 100% { transform: scale(1); } }

        /* 5. Dark Theme Overrides */
        body.dark-theme .reservation-form-card { background-color: rgba(30, 30, 30, 0.85); border-color: rgba(255, 255, 255, 0.15); }
        body.dark-theme .reservation-form-card::before { filter: invert(1); opacity: 0.03; }
        body.dark-theme .reservation-form-card h2 { color: #e0e0e0; }
        body.dark-theme .reservation-form .form-group label, body.dark-theme .form-group small { color: #a0a0a0; }
        body.dark-theme .reservation-form .form-group input, body.dark-theme .reservation-form .form-group select { background-color: #121212; color: #e0e0e0; border-color: #555; }
        body.dark-theme .reservation-form .form-group input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(1); }
        body.dark-theme .confirm-reservation-btn { background-color: #FFD700; color: #1a1a1a; border-color: #FFD700; }
        body.dark-theme .confirm-reservation-btn:hover { background-color: #e6c200; }
        body.dark-theme #notificationModal .modal-content { background-color: #2c2c2c; }
        body.dark-theme #notificationModal h2, body.dark-theme #notificationModal p { color: #e0e0e0; }
        body.dark-theme #notificationModal .close-button { color: #a0a0a0; }
        body.dark-theme #notificationModal .close-button:hover { color: #fff; }

        /* 6. Responsive Media Queries */
        @media (max-width: 480px) {
            .reservation-container { grid-template-columns: 1fr; }
            .reservation-form-card, .hours-card { padding: 25px; }
            .form-group-inline { flex-direction: column; gap: 15px; }
        }
    </style>
</head>
<body>

    <?php include 'partials/header.php'; ?>

    <section class="reservation-hero-section">
        <img src="images/1st.jpg" alt="Tavern Publico exterior at night" class="reservation-bg-image">
        <div class="reservation-overlay">
            <div class="reservation-container">
                <div class="reservation-form-card">
                    <h2>Book Your Table</h2>
                    <form id="reservationForm" class="reservation-form" action="process_reservation.php" method="POST" enctype="multipart/form-data">
                        <div class="form-group-inline">
                            <div class="form-group">
                                <label for="resDate">Date</label>
                                <input type="date" id="resDate" name="resDate" required>
                            </div>
                            <div class="form-group">
                                <label for="resTime">Time</label>
                                <select id="resTime" name="resTime" required></select>
                            </div>
                        </div>
                        <div class="form-group-inline">
                            <div class="form-group">
                                <label for="numGuests">Number of Guests</label>
                                <input type="number" id="numGuests" name="numGuests" min="1" max="50" placeholder="e.g., 4 (Max 50)" required>
                            </div>
                            <div class="form-group">
                                <label for="reservationType">Reservation Type</label>
                                <select id="reservationType" name="reservation_type" required>
                                    <option value="Dine-in" selected>Dine-in</option>
                                    <option value="Private Event">Private Event</option>
                                    <option value="Special Occasion">Special Occasion</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="resName">Full Name</label>
                            <input type="text" id="resName" name="resName" required value="<?php echo htmlspecialchars($user_details['username'] ?? ''); ?>" readonly>
                        </div>
                        <div class="form-group-inline">
                            <div class="form-group">
                                <label for="resPhone">Phone Number</label>
                                <input type="tel" id="resPhone" name="resPhone" placeholder="e.g., 09123456789" required maxlength="11" oninput="this.value = this.value.replace(/[^0-9]/g, '').substring(0, 11);">
                            </div>
                            <div class="form-group">
                                <label for="resEmail">Email Address</label>
                                <input type="email" id="resEmail" name="resEmail" required value="<?php echo htmlspecialchars($user_details['email'] ?? ''); ?>" readonly>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="validId">Upload Photo of Valid ID</label>
                            <input type="file" id="validId" name="valid_id" accept="image/png, image/jpeg, image/jpg" required>
                            <small style="display: block; margin-top: 5px;">For verification purposes. Max size: 10MB.</small>
                            <img id="idPreview" src="#" alt="ID Preview" style="display: none; max-width: 100px; margin-top: 10px; border-radius: 5px;"/>
                        </div>
                        
                        <div class="form-group">
                            <label for="couponCodeInput">Discount Coupon (Optional)</label>
                            <div style="display: flex; gap: 10px;">
                                <input type="text" id="couponCodeInput" name="coupon_code_display" placeholder="e.g., TAVERN10" style="flex-grow: 1;">
                                <button type="button" id="applyCouponBtn" class="btn" style="padding: 10px 15px; margin: 0; background-color: #555; color: white;">Apply</button>
                            </div>
                            <div id="couponMessage" style="font-size: 0.9em; margin-top: 8px; font-weight: bold;"></div>
                            
                            <input type="hidden" id="appliedCouponCode" name="coupon_code">
                        </div>
                        <button type="submit" class="btn confirm-reservation-btn">Confirm Reservation</button>
                    </form>
                </div>

                <div class="hours-card">
                    <h3>Visit Us</h3>
                    
                    <div class="map-container">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3855.700122170868!2d120.6212128749293!3d14.953506185614304!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x339659b48e3a479b%3A0x1d369a19c5c2d385!2sTavern%20Publico!5e0!3m2!1sen!2sph!4v1730890250785!5m2!1sen!2sph" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                    <p><strong><i class="fas fa-map-marker-alt"></i> Address:</strong><br>269 Floridablanca Road, Jose Abad Santos, Guagua, Pampanga</p>
                     
                     <div class="contact-links">
                        <a href="tel:+6585712615"><i class="fas fa-phone"></i> Call Us</a>
                        <a href="mailto:Publicotavern@gmail.com"><i class="fas fa-envelope"></i> Email Us</a>
                    </div>

                    <h4><i class="fas fa-clock"></i> Hours of Operation</h4>
                    <p><strong>Monday - Saturday</strong><br>11:00 AM - 9:00 PM</p>
                    <p><strong>Sunday</strong><br>12:00 PM - 9:00 PM</p>
                </div>
            </div>
        </div>
    </section>
    
    <div id="notificationModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close-button">×</span>
            <div id="modalHeaderIcon" class="modal-header-icon"></div>
            <h2 id="modalTitle"></h2>
            <p id="modalMessage"></p>
            <button class="btn modal-close-btn">OK</button>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>
    <?php include 'partials/Signin-Signup.php'; ?>

    <script src="JS/main.js"></script>
    <script src="JS/theme-switcher.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const resDateInput = document.getElementById('resDate');
            const resTimeSelect = document.getElementById('resTime');
            const blockedDates = <?php echo json_encode($blocked_dates); ?>;
            const validIdInput = document.getElementById('validId');
            const idPreview = document.getElementById('idPreview');
            
            const operatingHours = {
                0: { start: 12, end: 21 }, // Sun
                1: { start: 11, end: 21 }, // Mon-Sat
                2: { start: 11, end: 21 }, 3: { start: 11, end: 21 },
                4: { start: 11, end: 21 }, 5: { start: 11, end: 21 },
                6: { start: 11, end: 21 }
            };

            // MODIFIED: This function now handles empty dates
            function populateTimeSlots(selectedDate) {
                resTimeSelect.innerHTML = ''; // Clear existing slots

                // If no date is selected, show "Select Date First"
                if (!selectedDate) {
                    const option = document.createElement('option');
                    option.value = "";
                    option.textContent = "Please select a date";
                    option.disabled = true;
                    resTimeSelect.appendChild(option);
                    return; // Exit
                }
                
                const date = new Date(selectedDate + 'T00:00:00');
                const dayOfWeek = date.getDay();
                const now = new Date();
                const isToday = date.toDateString() === now.toDateString();
                const hours = operatingHours[dayOfWeek];
                if (!hours) return;
                let startHour = isToday ? Math.max(hours.start, now.getHours() + 1) : hours.start;

                for (let i = startHour; i < hours.end; i++) {
                    const option = document.createElement('option');
                    const time24 = `${i.toString().padStart(2, '0')}:00`;
                    const time12 = new Date(`1970-01-01T${time24}:00`).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
                    option.value = time24;
                    option.textContent = time12;
                    resTimeSelect.appendChild(option);
                }
                 if(resTimeSelect.innerHTML === '') {
                    const option = document.createElement('option');
                    option.value = "";
                    option.textContent = "No slots available";
                    option.disabled = true;
                    resTimeSelect.appendChild(option);
                }
            }
            
            if (validIdInput) {
                validIdInput.addEventListener('change', function() {
                    const file = this.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            idPreview.src = e.target.result;
                            idPreview.style.display = 'block';
                        }
                        reader.readAsDataURL(file);
                    } else {
                        idPreview.style.display = 'none';
                    }
                });
            }

            const notificationModal = document.getElementById('notificationModal');
            const modalHeaderIcon = document.getElementById('modalHeaderIcon');
            const modalTitle = document.getElementById('modalTitle');
            const modalMessage = document.getElementById('modalMessage');
            
            // MODIFIED: Moved modal's close buttons here to avoid script error
            const closeModal = () => {
                if(notificationModal) notificationModal.style.display = 'none';
            };
            
            if (notificationModal) {
                const closeButton = notificationModal.querySelector('.close-button');
                const okButton = notificationModal.querySelector('.modal-close-btn');

                if(closeButton) closeButton.addEventListener('click', closeModal);
                if(okButton) okButton.addEventListener('click', closeModal);
                window.addEventListener('click', (event) => {
                    if (event.target == notificationModal) closeModal();
                });
            }

            function showModal(type, title, message) {
                // Guard clause
                if (!notificationModal || !modalHeaderIcon || !modalTitle || !modalMessage) {
                    console.warn('Notification modal elements not found. Using simple alert.');
                    alert(title + "\n" + message);
                    return;
                }
                modalHeaderIcon.innerHTML = type === 'success' ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>';
                modalHeaderIcon.className = 'modal-header-icon ' + type;
                modalTitle.textContent = title;
                modalMessage.textContent = message;
                notificationModal.style.display = 'flex';
            }

            if (resDateInput) {
                const today = new Date();
                const todayString = today.toISOString().split('T')[0];
                resDateInput.min = todayString;
                resDateInput.value = todayString;

                // MODIFIED: This logic is now fixed
                resDateInput.addEventListener('change', function() {
                    if (blockedDates.includes(this.value)) {
                        showModal('error', 'Date Not Available', 'The selected date is fully booked or unavailable. Please choose a different date.');
                        this.value = '';
                        populateTimeSlots(''); // Call with empty to clear the list
                    } else {
                        populateTimeSlots(this.value); // Only populate if not blocked
                    }
                });
                populateTimeSlots(todayString); // Initial call
            }
            
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('status')) {
                const status = urlParams.get('status');
                const message = urlParams.get('message') || 'An unknown error occurred.';
                if (status === 'success') {
                    showModal('success', 'Reservation Submitted!', 'Your request has been received. Please wait for the administrator to confirm your booking.');
                } else if (status === 'error') {
                    showModal('error', 'Reservation Failed', message);
                }
                history.replaceState(null, '', window.location.pathname);
            }

            // --- COUPON JAVASCRIPT START (MODIFIED) ---
            // This code no longer errors because the modal functions are correctly guarded
            const applyCouponBtn = document.getElementById('applyCouponBtn');
            const couponCodeInput = document.getElementById('couponCodeInput');
            const couponMessage = document.getElementById('couponMessage'); // We'll clear this
            const appliedCouponCode = document.getElementById('appliedCouponCode');
            const reservationForm = document.getElementById('reservationForm');

            if (applyCouponBtn) { // Added guard for safety
                applyCouponBtn.addEventListener('click', async () => {
                    const code = couponCodeInput.value;
                    if (!code) {
                        showModal('error', 'Invalid Coupon', 'Please enter a code.');
                        return;
                    }

                    applyCouponBtn.disabled = true;
                    applyCouponBtn.textContent = '...';
                    
                    const formData = new FormData();
                    formData.append('coupon_code', code);

                    try {
                        const response = await fetch('validate_coupon.php', { method: 'POST', body: formData });
                        const result = await response.json();

                        if (result.success) {
                            showModal('success', 'Coupon Applied!', result.message);
                            if(couponMessage) couponMessage.innerHTML = ''; // Clear text message
                            appliedCouponCode.value = code; // Set the hidden input value
                            couponCodeInput.disabled = true; // Lock the input
                            applyCouponBtn.textContent = 'Applied!';
                        } else {
                            showModal('error', 'Invalid Coupon', result.message);
                            if(couponMessage) couponMessage.innerHTML = ''; // Clear text message
                            appliedCouponCode.value = ''; // Clear hidden input
                            applyCouponBtn.disabled = false;
                            applyCouponBtn.textContent = 'Apply';
                        }
                    } catch (error) {
                        showModal('error', 'Network Error', 'An error occurred. Please try again.');
                        if(couponMessage) couponMessage.innerHTML = ''; // Clear text message
                        applyCouponBtn.disabled = false;
                        applyCouponBtn.textContent = 'Apply';
                    }
                });
            }

            // Clear coupon if user changes the date
            if (resDateInput) {
                resDateInput.addEventListener('change', () => {
                    if (couponCodeInput) {
                        couponCodeInput.disabled = false;
                        couponCodeInput.value = '';
                    }
                    if (appliedCouponCode) appliedCouponCode.value = '';
                    if (couponMessage) couponMessage.textContent = '';
                    if (applyCouponBtn) {
                        applyCouponBtn.disabled = false;
                        applyCouponBtn.textContent = 'Apply';
                    }
                });
            }
            // --- COUPON JAVASCRIPT END ---

        });
    </script>
</body>
</html>