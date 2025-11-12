<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user = null;
$reservations = [];

// MODIFIED: Added 'birthday_last_updated' to the query
$sql_user = "SELECT user_id, username, email, created_at, avatar, mobile, birthday, birthday_last_updated FROM users WHERE user_id = ?";
if ($stmt_user = mysqli_prepare($link, $sql_user)) {
    mysqli_stmt_bind_param($stmt_user, "i", $user_id);
    if (mysqli_stmt_execute($stmt_user)) {
        $result_user = mysqli_stmt_get_result($stmt_user);
        $user = mysqli_fetch_assoc($result_user);
    }
    mysqli_stmt_close($stmt_user);
}

$sql_reservations = "SELECT reservation_id, res_date, res_time, num_guests, status, created_at FROM reservations WHERE user_id = ? AND deleted_at IS NULL ORDER BY created_at DESC";
if ($stmt_reservations = mysqli_prepare($link, $sql_reservations)) {
    mysqli_stmt_bind_param($stmt_reservations, "i", $user_id);
    if (mysqli_stmt_execute($stmt_reservations)) {
        $result_reservations = mysqli_stmt_get_result($stmt_reservations);
        while ($row = mysqli_fetch_assoc($result_reservations)) {
            $reservations[] = $row;
        }
    }
    mysqli_stmt_close($stmt_reservations);
}

$avatar_path = isset($user['avatar']) && file_exists($user['avatar']) ? $user['avatar'] : 'images/default_avatar.png';

// --- NEW: Birthday Lock Logic ---
$birthday_locked = false;
$birthday_message = '';
if (!empty($user['birthday_last_updated'])) {
    $last_updated = new DateTime($user['birthday_last_updated']);
    $today = new DateTime();
    $interval = $last_updated->diff($today);
    $days_passed = $interval->days;
    
    if ($days_passed < 60) {
        $birthday_locked = true;
        $days_remaining = 60 - $days_passed;
        $birthday_message = "You can change your birthday in $days_remaining days.";
    }
}
// --- END NEW ---

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Tavern Publico</title>
    <link rel="stylesheet" href="CSS/main.css">
    <link rel="stylesheet" href="CSS/profile.css">
    <link rel="stylesheet" href="CSS/dark-theme.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .profile-avatar-container { text-align: center; margin-bottom: 25px; }
        .profile-avatar { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 5px solid #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.1); margin-bottom: 15px; }
        .upload-avatar-form input[type="file"] { display: none; }
        .upload-avatar-form .upload-label { display: inline-block; padding: 10px 20px; background-color: #3498db; color: white; border-radius: 5px; cursor: pointer; transition: background-color 0.3s; }
        .upload-avatar-form .upload-label:hover { background-color: #2980b9; }
        .upload-avatar-form button { display: none; }
        
        .btn-cancel {
            background-color: #e74c3c; color: white; border: none; padding: 5px 12px;
            font-size: 0.85em; border-radius: 4px; cursor: pointer; transition: background-color 0.3s; font-weight: 500;
        }
        .btn-cancel:hover { background-color: #c0392b; }

        .settings-form .info-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f0f0f0; }
        .settings-form .info-label { flex-basis: 35%; font-weight: 600; color: #555; padding-right: 15px; }
        .settings-form .info-value { flex-basis: 65%; display: flex; align-items: center; }
        .input-with-icon { position: relative; width: 100%; }
        .input-with-icon i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #999; }
        .input-with-icon input { width: 100%; padding: 10px 15px 10px 40px; border: 1px solid #ddd; border-radius: 5px; font-size: 1em; background-color: #f9f9f9; }
        
        /* --- NEW: Readonly Input Style --- */
        .input-with-icon input[readonly],
        .input-with-icon input[readonly]:focus {
            background-color: #e9ecef;
            cursor: not-allowed;
            color: #555;
            border-color: #ddd;
            box-shadow: none;
        }
        .birthday-lock-message {
            font-size: 0.85em; color: #777;
        }
        /* --- END NEW --- */
        
        .user-id-value { font-family: monospace; font-size: 1.1em; color: #333; font-weight: 600; background-color: #f0f2f5; padding: 8px 12px; border-radius: 5px; }
        .save-changes-container { padding-top: 25px; text-align: right; }
        .btn-save { background-color: #28a745; color: white; padding: 10px 25px; border-radius: 5px; cursor: pointer; font-weight: 600; border: none; transition: background-color 0.3s, transform 0.2s; }
        .btn-save:hover { background-color: #218838; transform: translateY(-2px); }

        .policies-section { margin-top: 25px; padding-top: 20px; border-top: 1px solid #eee; }
        .policies-section h4 { margin-bottom: 10px; }
        .policies-section p { font-size: 0.9em; color: #666; margin-bottom: 5px; }
        .policies-section a { cursor: pointer; text-decoration: underline; color: #3498db; }
        .toggle-history-btn { display: block; margin: 20px auto 0; background: #f8f9fa; border: 1px solid #ddd; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-weight: 500; transition: background-color 0.2s; }
        .toggle-history-btn:hover { background-color: #e9ecef; }

        .info-modal-content {
            background-color: #fff; border-radius: 10px; padding: 30px;
            width: 90%; max-width: 600px; text-align: left; position: relative;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2); animation: fadeIn 0.4s;
        }
        @keyframes fadeIn{from{opacity:0;transform:scale(.95)}to{opacity:1;transform:scale(1)}}
        .info-modal-content .close-button {
            position: absolute; top: 10px; right: 20px; color: #aaa;
            font-size: 28px; font-weight: bold; cursor: pointer;
        }
        #infoModalTitle { font-size: 1.8em; margin-top: 0; margin-bottom: 15px; color: #333; }
        #infoModalBody { font-size: 1em; line-height: 1.6; max-height: 60vh; overflow-y: auto; color: #555; }
        #infoModalBody h2 { font-size: 1.5em; margin-top: 20px; margin-bottom: 10px; color: #333; text-align: left; }
        #infoModalBody p { margin-bottom: 10px; }
        
        .info-modal-content.alert-modal {
            max-width: 380px; text-align: center; padding: 25px;
            height: auto; min-height: 0 !important;
        }
        .info-modal-content.alert-modal #infoModalTitle { font-size: 1.6em; margin: 0 0 10px 0; }
        .info-modal-content.alert-modal #infoModalBody p { margin: 0; }
        
        /* --- Dark Mode Styles --- */
        body.dark-theme .profile-page-main {
            background-color: #121212;
        }
        body.dark-theme .profile-header h1,
        body.dark-theme .profile-header p {
            color: #e0e0e0;
        }
        body.dark-theme .profile-avatar {
            border-color: #333;
        }
        body.dark-theme .profile-details-card,
        body.dark-theme .reservation-history-card {
            background-color: #1e1e1e;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }
        body.dark-theme .card-header {
            background-color: #2a2a2a;
            border-bottom-color: #333;
        }
        body.dark-theme .card-header h3,
        body.dark-theme .settings-form h4 {
            color: #e0e0e0;
        }
        body.dark-theme .settings-form .info-row {
            border-bottom-color: #333;
        }
        body.dark-theme .settings-form .info-label {
            color: #bbb;
        }
        body.dark-theme .user-id-value {
            color: #e0e0e0;
            background-color: #121212;
        }
        body.dark-theme .input-with-icon input {
            background-color: #121212;
            border-color: #555;
            color: #e0e0e0;
        }
        body.dark-theme .input-with-icon input:focus {
            border-color: #FFD700;
        }
        
        /* --- NEW: Dark Mode Readonly Style --- */
        body.dark-theme .input-with-icon input[readonly],
        body.dark-theme .input-with-icon input[readonly]:focus {
            background-color: #2c2c2c !important;
            color: #888 !important;
            cursor: not-allowed !important;
            border-color: #444 !important;
            box-shadow: none !important;
        }
        body.dark-theme .birthday-lock-message {
            color: #888;
        }
        /* --- END NEW --- */

        body.dark-theme .input-with-icon i {
            color: #888;
        }
        body.dark-theme .policies-section {
            border-top-color: #333;
        }
        body.dark-theme .policies-section p {
            color: #999;
        }
        body.dark-theme .policies-section a {
            color: #FFD700;
        }
        body.dark-theme .reservation-history-card thead th {
            background-color: #2a2a2a;
            color: #bbb;
        }
        body.dark-theme .reservation-history-card th,
        body.dark-theme .reservation-history-card td {
            border-bottom-color: #333;
        }
        body.dark-theme .reservation-history-card tbody tr:hover {
            background-color: #2c2c2c;
        }
        body.dark-theme .no-reservations {
            color: #888;
        }
        body.dark-theme .toggle-history-btn {
            background-color: #333;
            border-color: #555;
            color: #e0e0e0;
        }
        body.dark-theme .toggle-history-btn:hover {
            background-color: #444;
        }
        /* Modal Dark Mode */
        body.dark-theme .info-modal-content {
            background-color: #2c2c2c;
        }
        body.dark-theme .info-modal-content .close-button {
            color: #aaa;
        }
        body.dark-theme #infoModalTitle,
        body.dark-theme #infoModalBody h2 {
            color: #e0e0e0;
        }
        body.dark-theme #infoModalBody,
        body.dark-theme #infoModalBody p {
            color: #bbb;
        }
    </style>
</head>
<body>

    <?php include 'partials/header.php'; ?>

    <main class="profile-page-main">
        <div class="container">
            <div class="profile-header">
                <div class="profile-avatar-container">
                    <img src="<?= htmlspecialchars($avatar_path) ?>" alt="My Avatar" class="profile-avatar">
                    <form action="upload_avatar.php" method="post" enctype="multipart/form-data" class="upload-avatar-form">
                        <label for="avatarFile" class="upload-label"><i class="fas fa-upload"></i> Change Avatar</label>
                        <input type="file" name="avatarFile" id="avatarFile" onchange="this.form.submit()">
                    </form>
                </div>
                <h1 id="welcomeMessage">Welcome, <?= htmlspecialchars($user['username'] ?? 'Guest'); ?>!</h1>
                <p>Manage your account settings and view your reservation history.</p>
            </div>

            <div class="profile-content-grid">
                <div class="profile-details-card">
                    <div class="card-header">
                        <h3><i class="fas fa-cog"></i> Settings</h3>
                    </div>
                    <div class="card-body">
                        <form id="accountSettingsForm" class="settings-form">
                            <h4>Account Information</h4>
                            <div class="info-row"><span class="info-label">User ID</span><span class="info-value user-id-value"><?= sprintf('%04d', $user['user_id']); ?></span></div>
                            
                            <div class="info-row"><span class="info-label">Username</span><div class="info-value input-with-icon"><i class="fas fa-user"></i><input type="text" name="username" id="usernameInput" value="<?= htmlspecialchars($user['username']); ?>" readonly></div></div>
                            
                            <div class="info-row">
                                <span class="info-label">Birthday</span>
                                <div class="info-value input-with-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                    <input type="date" name="birthday" value="<?= htmlspecialchars($user['birthday'] ?? ''); ?>" <?= $birthday_locked ? 'readonly' : '' ?>>
                                </div>
                            </div>
                            <?php if ($birthday_locked): ?>
                            <div class="info-row" style="border-bottom: none; padding-top: 0;">
                                <span class="info-label"></span> <div class="info-value birthday-lock-message"><?= $birthday_message; ?></div>
                            </div>
                            <?php endif; ?>

                            <div class="info-row"><span class="info-label">Mobile</span><div class="info-value input-with-icon"><i class="fas fa-mobile-alt"></i><input type="tel" name="mobile" placeholder="e.g., 09123456789" value="<?= htmlspecialchars($user['mobile'] ?? ''); ?>" maxlength="11" pattern="\d{11}" oninput="this.value = this.value.replace(/[^0-9]/g, '').substring(0, 11);"></div></div>
                            
                            <div class="info-row"><span class="info-label">Change Password</span><div class="info-value input-with-icon"><i class="fas fa-lock"></i><input type="password" name="new_password" id="newPasswordInput" placeholder="New Password"></div></div>
                            <div class="info-row"><span class="info-label">Retype Password</span><div class="info-value input-with-icon"><i class="fas fa-lock"></i><input type="password" name="retype_password" id="retypePasswordInput" placeholder="Retype New Password"></div></div>
                            <div class="save-changes-container"><button type="submit" class="btn-save">Save Changes</button></div>
                        </form>

                        <div class="policies-section">
                            <h4>Policies</h4>
                            <p><a id="termsLink">Terms of Service</a></p>
                            <p><a id="privacyLink">Privacy Policy</a></p>
                        </div>
                    </div>
                </div>

                <div class="reservation-history-card">
                    <div class="card-header"><h3><i class="fas fa-calendar-alt"></i> Reservation History</h3></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="reservationsTable">
                                <thead><tr><th>Date</th><th>Time</th><th>Guests</th><th>Status</th><th>Actions</th></tr></thead>
                                <tbody>
                                    <?php if (!empty($reservations)): ?>
                                        <?php foreach ($reservations as $index => $res): ?>
                                            <tr class="reservation-row" style="<?= $index >= 5 ? 'display: none;' : '' ?>">
                                                <td><?= htmlspecialchars($res['res_date']); ?></td>
                                                <td><?= htmlspecialchars(date('g:i A', strtotime($res['res_time']))); ?></td>
                                                <td><?= htmlspecialchars($res['num_guests']); ?></td>
                                                <td><span class="status-badge status-<?= strtolower(htmlspecialchars($res['status'])); ?>"><?= htmlspecialchars($res['status']); ?></span></td>
                                                <td>
                                                    <?php
                                                    $created_timestamp = strtotime($res['created_at']);
                                                    $current_timestamp = time();
                                                    $can_cancel = ($current_timestamp - $created_timestamp) < 1800;
                                                    $is_cancellable_status = in_array($res['status'], ['Pending', 'Confirmed']);
                                                    if ($is_cancellable_status && $can_cancel) {
                                                        echo '<button class="btn-cancel" data-id="' . $res['reservation_id'] . '">Cancel</button>';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="no-reservations">You have no past or upcoming reservations.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <?php if (count($reservations) > 5): ?>
                                <button id="toggleHistoryBtn" class="toggle-history-btn">Show More</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <div id="infoModal" class="modal">
        <div class="modal-content info-modal-content">
            <span class="close-button">&times;</span>
            <h2 id="infoModalTitle"></h2>
            <div id="infoModalBody"></div>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>
    <?php include 'partials/Signin-Signup.php'; ?>
    <script src="JS/main.js"></script>
    <script src="JS/theme-switcher.js"></script> <script>
        document.addEventListener('DOMContentLoaded', () => {
            const infoModal = document.getElementById('infoModal');
            const infoModalContent = infoModal.querySelector('.info-modal-content');
            const infoModalTitle = document.getElementById('infoModalTitle');
            const infoModalBody = document.getElementById('infoModalBody');
            const infoModalCloseBtn = infoModal.querySelector('.close-button');

            function showAlertModal(title, message) {
                infoModalContent.classList.add('alert-modal');
                infoModalTitle.textContent = title;
                infoModalBody.innerHTML = `<p>${message}</p>`;
                infoModal.style.display = 'flex';
            }

            async function showPolicyModal(policyUrl, policyTitle) {
                infoModalContent.classList.remove('alert-modal');
                infoModalTitle.textContent = policyTitle;
                infoModalBody.innerHTML = '<p>Loading...</p>';
                infoModal.style.display = 'flex';
                try {
                    const response = await fetch(policyUrl);
                    if (!response.ok) throw new Error('Content could not be loaded.');
                    const content = await response.text();
                    infoModalBody.innerHTML = content;
                } catch (error) {
                    infoModalBody.innerHTML = `<p>Sorry, the content could not be loaded at this time.</p>`;
                }
            }

            infoModalCloseBtn.onclick = () => infoModal.style.display = 'none';
            window.addEventListener('click', (event) => {
                if (event.target == infoModal) infoModal.style.display = 'none';
            });

            document.querySelectorAll('.btn-cancel').forEach(button => {
                button.addEventListener('click', function() {
                    if (confirm('Are you sure you want to cancel this reservation? This action cannot be undone.')) {
                        handleCancelReservation(this.dataset.id, this);
                    }
                });
            });

            const toggleBtn = document.getElementById('toggleHistoryBtn');
            if(toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    const rows = document.querySelectorAll('#reservationsTable .reservation-row');
                    const isShowingAll = this.textContent === 'Show Less';
                    rows.forEach((row, index) => {
                        if (index >= 5) row.style.display = isShowingAll ? 'none' : 'table-row';
                    });
                    this.textContent = isShowingAll ? 'Show More' : 'Show Less';
                });
            }

            document.getElementById('accountSettingsForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const newPassword = document.getElementById('newPasswordInput').value;
                const retypePassword = document.getElementById('retypePasswordInput').value;

                if (newPassword !== retypePassword) {
                    showAlertModal('Error', 'The new passwords do not match. Please try again.');
                    return; 
                }

                const formData = new FormData(this);
                
                // --- MODIFIED: Remove username from form data before sending ---
                formData.delete('username');
                // --- END MODIFIED ---
                
                try {
                    const response = await fetch('update_profile.php', { method: 'POST', body: formData });
                    const result = await response.json();
                    
                    if(result.success) {
                        // Username is no longer part of the response, so no need to update welcome message
                        document.getElementById('newPasswordInput').value = '';
                        document.getElementById('retypePasswordInput').value = '';
                        // Reload the page if the birthday was changed to update the lock status
                        if (result.birthday_updated) {
                            showAlertModal(result.success ? 'Success!' : 'Error', result.message + " Page will now reload.");
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            showAlertModal(result.success ? 'Success!' : 'Error', result.message);
                        }
                    } else {
                         showAlertModal(result.success ? 'Success!' : 'Error', result.message);
                    }
                } catch (error) {
                    showAlertModal('Error', 'An unexpected network error occurred.');
                }
            });

            document.getElementById('termsLink').addEventListener('click', (e) => {
                e.preventDefault();
                showPolicyModal('terms_of_service.php', 'Terms of Service');
            });
            document.getElementById('privacyLink').addEventListener('click', (e) => {
                e.preventDefault();
                showPolicyModal('privacy_policy.php', 'Privacy Policy');
            });
        });

        async function handleCancelReservation(id, buttonElement) {
            const formData = new FormData();
            formData.append('reservation_id', id);
            try {
                const response = await fetch('cancel_reservation.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    const row = buttonElement.closest('tr');
                    if(row) {
                        row.querySelector('.status-badge').textContent = 'Cancelled';
                        row.querySelector('.status-badge').className = 'status-badge status-cancelled';
                        buttonElement.remove();
                    }
                    showAlertModal('Success', 'Your reservation has been cancelled.');
                } else {
                    showAlertModal('Cancellation Failed', result.message);
                }
            } catch (error) {
                showAlertModal('Error', 'An unexpected error occurred. Please try again.');
            }
        }
    </script>
</body>
</html>