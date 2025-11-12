<style>
    /* --- INLINED HEADER STYLES --- */
    .header-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #ddd;
    }
    .user-profile-menu {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    #profileBtn {
        background-color: #fff;
        color: #333;
        font-size: 1em;
        border: 1px solid #ddd;
        cursor: pointer;
        border-radius: 50px;
        font-family: 'Mada', sans-serif;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        height: 42px;
        padding: 0 15px 0 5px;
    }
    .notification-button {
        background-color: transparent;
        border: 1px solid #ddd;
        color: #333;
        width: 42px;
        height: 42px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        cursor: pointer;
        transition: background-color 0.3s ease, border-color 0.3s ease;
    }
    .notification-button .fa-bell { font-size: 1.1em; }
    .notification-badge { position: absolute; top: 0px; right: 0px; background-color: #e74c3c; color: white; font-size: 0.7rem; border-radius: 50%; padding: 3px 6px; display: flex; justify-content: center; align-items: center; min-width: 18px; height: 18px; font-weight: bold; }
    #profileBtn:hover { background-color: #f5f5f5; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    .profile-dropdown { position: relative; display: inline-block; }

    /* Dropdown Animation */
    #profileDropdownContent, .notification-dropdown-content { 
        display: block; position: absolute; background-color: #ffffff; min-width: 180px; 
        box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.15); z-index: 2000; right: 0; 
        border: 1px solid #eee; border-radius: 8px; margin-top: 8px; overflow: hidden;
        opacity: 0; visibility: hidden; transform: translateY(10px);
        transition: opacity 0.3s ease, transform 0.3s ease, visibility 0.3s;
    }
    #profileDropdownContent.show-dropdown, .notification-dropdown-content.show {
        opacity: 1; visibility: visible; transform: translateY(0);
    }
    .notification-dropdown-content { min-width: 320px; max-width: 350px; padding: 0; }
    #profileDropdownContent a { color: black; padding: 12px 16px; text-decoration: none; display: block; font-size: 1em; }
    #profileDropdownContent a:hover { background-color: #f1f1f1; }
    .notification-header { padding: 12px 16px; font-weight: bold; font-size: 1.1em; color: #333; border-bottom: 1px solid #eee; }
    .notification-body { max-height: 300px; overflow-y: auto; }
    .notification-item { display: flex; align-items: center; padding: 12px 16px; border-bottom: 1px solid #f0f0f0; transition: background-color 0.2s ease; text-decoration: none; color: inherit; font-size: 0.9em; }
    .no-notifications { text-align: center; color: #777; padding: 20px; font-size: 0.9em; }
    .notification-item:hover { background-color: #f8f9fa; }
    .notification-item i { margin-right: 10px; color: #555; width: 20px; text-align: center; }
    .notification-item .fa-check-circle { color: #28a745; }
    .notification-item .fa-times-circle, .notification-item .fa-ban { color: #dc3545; }
    .notification-item .fa-reply { color: #007bff; }

    /* === NEW/MODIFIED STYLES FOR DISMISS BUTTON === */
    .notification-item {
        justify-content: space-between; /* Push button to the end */
    }
    .notification-item .notification-message-text {
        flex-grow: 1; /* Allow text to take available space */
        margin-right: 10px; /* Space before the button */
        /* Truncate text */
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .notification-dismiss-btn {
        background: transparent;
        border: none;
        color: #aaa;
        font-size: 20px;
        font-weight: bold;
        line-height: 1;
        padding: 5px;
        margin-left: 5px;
        cursor: pointer;
        border-radius: 50%;
        width: 25px;
        height: 25px;
        flex-shrink: 0;
        transition: background-color 0.2s ease, color 0.2s ease;
    }
    .notification-dismiss-btn:hover {
        background-color: #f0f0f0;
        color: #333;
    }
    body.dark-theme .notification-dismiss-btn {
        color: #888;
    }
    body.dark-theme .notification-dismiss-btn:hover {
        background-color: #555;
        color: #fff;
    }
    /* === END NEW/MODIFIED STYLES === */

    /* Mobile Header Controls */
    .mobile-header-controls { display: flex; align-items: center; gap: 10px; }
    .no-scroll { overflow: hidden; }
    
    .mobile-nav-toggle { 
        display: none; 
        background: none; 
        border: none; 
        cursor: pointer; 
        z-index: 2001; 
        padding: 10px; 
    }
    .mobile-nav-toggle span { 
        display: block; 
        width: 25px; 
        height: 3px; 
        background-color: #333; 
        margin: 5px 0; 
        transition: transform 0.3s ease, opacity 0.3s ease; 
    }
    
    body.dark-theme .mobile-nav-toggle span {
        background-color: #e0e0e0;
    }
    
    .mobile-nav-close {
        display: none; /* Hidden on desktop */
        position: absolute;
        top: 20px;
        right: 20px; 
        background: none;
        border: none;
        color: #fff;
        font-size: 2.2rem; 
        font-weight: 300;
        line-height: 1;
        cursor: pointer;
        padding: 0;
        z-index: 2002;
    }

    body.dark-theme .mobile-nav-close {
        color: #fff;
    }
    
    body.dark-theme .mobile-nav-close:hover {
        color: #FFD700;
    }

    @media (max-width: 992px) {
        .header-content { 
            padding: 0 15px; 
            justify-content: space-between;
        }
        .logo { 
            margin-right: 0; 
        }
        
        /* === NEW/MODIFIED SLIDE-IN MENU STYLES === */
        .main-nav { 
            position: fixed; 
            top: 0; 
            right: 0; 
            transform: translateX(100%);
            width: 70%;
            max-width: 250px; 
            height: 100vh; 
            background-color: #ffffff; 
            z-index: 2000; 
            padding: 0 0 30px 0;
            display: flex; 
            flex-direction: column; 
            justify-content: flex-start; 
            transition: transform 0.5s cubic-bezier(0.23, 1, 0.32, 1);
            border-radius: 40vw 0 0 40vw;
            box-shadow: -5px 0 15px rgba(0,0,0,0.1); 
        }
        .main-nav.nav-open { 
            transform: translateX(0); 
        }
        .main-nav ul { 
            flex-direction: column; 
            align-items: flex-start;
            gap: 0; 
            width: 100%; 
            padding: 15px 0; 
            margin-top: 80px;
            padding-left: 30px; 
        }
        .main-nav ul li { 
            width: 100%; 
            text-align: left;
            opacity: 0; 
            transform: translateX(30px);
            animation: fadeInRight 0.5s ease forwards; 
        }
        .main-nav.nav-open ul li:nth-child(1) { animation-delay: 0.2s; }
        .main-nav.nav-open ul li:nth-child(2) { animation-delay: 0.3s; }
        .main-nav.nav-open ul li:nth-child(3) { animation-delay: 0.4s; }
        .main-nav.nav-open ul li:nth-child(4) { animation-delay: 0.5s; }
        .main-nav.nav-open ul li:nth-child(5) { animation-delay: 0.6s; }
        .main-nav.nav-open ul li:nth-child(6) { animation-delay: 0.7s; }

        @keyframes fadeInRight { 
            to { opacity: 1; transform: translateX(0); } 
        }
        
        .main-nav ul li a { 
            padding: 16px 30px; 
            width: 100%; 
            display: block; 
            font-size: 1.3em; 
            font-weight: 600; 
            color: #333;
            transition: background-color 0.2s ease, color 0.2s ease;
            border-radius: 30px 0 0 30px; 
        }
        
        .main-nav ul li a::after {
            display: none !important;
        }
        
        .main-nav ul li a:hover {
            background-color: #f5f5f5;
        }
        .main-nav ul li a.active-nav-link {
            background-color: #FFD700;
            color: #1a1a1a;
        }

        .mobile-nav-close {
            display: block;
            color: #333; /* Color for light mode */
        }
        .mobile-nav-close:hover {
            color: #FFD700;
        }
        /* --- END NEW/MODIFIED SLIDE-IN MENU --- */

        .mobile-nav-toggle { 
            display: block; 
        }

        /* BUG FIX: This rule hides the hamburger when the nav is open */
        .main-nav.nav-open ~ .mobile-nav-toggle {
            opacity: 0;
            visibility: hidden;
            transform: scale(0.8);
        }
        /* This ensures the hamburger fades back in smoothly */
        .mobile-nav-toggle {
            transition: opacity 0.3s ease, visibility 0.3s ease, transform 0.3s ease;
        }
        
        .signin-button .desktop-text { display: none; }
        .signin-button { width: 45px; height: 45px; padding: 0; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .signin-button .mobile-icon { display: block !important; font-size: 1.5em; }
        
        #profileBtn .username-text, #profileBtn .fa-caret-down { display: none; }
        #profileBtn { padding: 0; width: 42px; justify-content: center; }

        /* === NEW DARK MODE STYLES FOR MOBILE NAV === */
        body.dark-theme .main-nav {
            background-color: #1e1e1e;
            box-shadow: -5px 0 15px rgba(0,0,0,0.3);
        }
        body.dark-theme .main-nav ul li a {
            color: #e0e0e0;
        }
        body.dark-theme .main-nav ul li a:hover {
            background-color: #333;
        }
        body.dark-theme .main-nav ul li a.active-nav-link {
            background-color: #FFD700;
            color: #1a1a1a;
        }
        body.dark-theme .mobile-nav-close {
            color: #fff; /* White 'X' in dark mode */
        }
        body.dark-theme .mobile-nav-close:hover {
            color: #FFD700;
        }
    }
    
    /* --- MODIFICATION: Added dark theme styles for new modal --- */
    body.dark-theme #notificationViewModal .modal-content {
        background-color: #2c2c2c;
        border: 1px solid #444;
    }
    body.dark-theme #notificationViewModal .modal-title,
    body.dark-theme #notificationViewModal #modalNotificationBody {
        color: #e0e0e0;
    }
    body.dark-theme #notificationViewModal .close-button {
        color: #aaa;
    }
    body.dark-theme #notificationViewModal .close-button:hover {
        color: #fff;
    }
    body.dark-theme #notificationViewModal #notificationViewModalOk {
        background-color: #FFD700;
        color: #1a1a1a;
        border: 1px solid #FFD700;
    }
    body.dark-theme #notificationViewModal #notificationViewModalOk:hover {
        background-color: #e6c200;
        border-color: #e6c200;
    }
    /* --- END MODIFICATION --- */
</style>

<header class="main-header">
    <div class="header-content">
        <div class="logo">
            <div class="logo-main-line">
                <span>TAVERN PUBLICO</span>
            </div>
            <span class="est-year">EST ★ 2024</span>
        </div>

        <nav class="main-nav">
            <button class="mobile-nav-close" aria-label="Close navigation menu">&times;</button>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="menu.php">Menu</a></li>
                <li><a href="gallery.php">Gallery</a></li>
                <li><a href="events.php">Events</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li><a href="about.php">About</a></li>
            </ul>
        </nav>

        <div class="header-right">
            <?php
            if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
                $avatar_path = isset($_SESSION['avatar']) && file_exists($_SESSION['avatar']) ? $_SESSION['avatar'] : 'images/default_avatar.png';

                echo '<div class="user-profile-menu">';
                echo '  <div class="profile-dropdown">';
                echo '    <button id="profileBtn" class="profile-button">';
                echo '      <img src="' . htmlspecialchars($avatar_path) . '" alt="My Avatar" class="header-avatar">';
                echo '      <span class="username-text">' . htmlspecialchars($_SESSION['username']) . '</span>';
                echo '      <i class="fas fa-caret-down" style="font-size: 0.8em; margin-left: 5px;"></i>';
                echo '    </button>';
                echo '    <div id="profileDropdownContent">';
                echo '      <a href="profile.php">My Profile</a>';
                
                // --- MODIFICATION: Check for Admin/Manager role ---
                if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['owner', 'manager'])) {
                    $admin_dashboard_url = ($_SESSION['role'] === 'manager') ? 'manager.php' : 'admin.php';
                    echo '      <a href="' . $admin_dashboard_url . '">Admin</a>';
                }
                // --- END MODIFICATION ---
                
                echo '      <a href="logout.php">Logout</a>';
                echo '    </div>';
                echo '  </div>';
                echo '  <div class="notification-dropdown">';
                echo '      <button class="notification-button" id="notificationBtn">';
                echo '          <i class="fas fa-bell"></i>';
                echo '          <span class="notification-badge" id="notificationCount" style="display: none;">0</span>';
                echo '      </button>';
                echo '      <div class="notification-dropdown-content" id="notificationDropdownContent"></div>';
                echo '  </div>';
                echo '</div>';
            } else {
                echo '<a href="#" class="btn header-button signin-button" id="openModalBtn"><span 
                class="desktop-text">Sign In/Sign Up</span><i class="fas fa-user-circle mobile-icon" style="display: none;"></i></a>';
            }
            ?>
        </div>
        
        <button class="mobile-nav-toggle" aria-label="Open navigation menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>
</header>
<button id="theme-switcher"><i class="fas fa-moon"></i></button>

<div id="notificationViewModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <span class="close-button">&times;</span>
        <div class="modal-form-container" style="padding: 10px 30px 30px 30px;">
             <h2 id="modalNotificationTitle" class="modal-title" style="text-align: left; margin-bottom: 20px;">Notification</h2>
             <p id="modalNotificationBody" style="text-align: left; font-size: 1em; line-height: 1.6; max-height: 60vh; overflow-y: auto; white-space: pre-wrap; margin-bottom: 25px;"></p>
             <button id="notificationViewModalOk" class="btn btn-primary modal-btn" style="width: 100px; margin-left: auto; display: block; margin-top: 0;">OK</button>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // --- Elements ---
    const profileButton = document.getElementById('profileBtn');
    const profileDropdown = document.getElementById('profileDropdownContent');
    const notificationButton = document.getElementById('notificationBtn');
    const notificationDropdown = document.getElementById('notificationDropdownContent');
    const mobileNavToggle = document.querySelector('.mobile-nav-toggle');
    const mainNav = document.querySelector('.main-nav');
    const mobileNavClose = document.querySelector('.mobile-nav-close');
    
    // --- MODIFICATION: Added new modal elements ---
    const notificationViewModal = document.getElementById('notificationViewModal');
    const modalNotificationTitle = document.getElementById('modalNotificationTitle');
    const modalNotificationBody = document.getElementById('modalNotificationBody');
    const notificationViewModalOk = document.getElementById('notificationViewModalOk');
    const notificationViewModalClose = notificationViewModal ? notificationViewModal.querySelector('.close-button') : null;
    // --- END MODIFICATION ---

    // --- Active Nav Link Logic ---
    const navLinks = document.querySelectorAll('.main-nav a');
    const currentPageFile = window.location.pathname.split('/').pop();
    navLinks.forEach(link => {
        const linkFile = link.getAttribute('href').split('/').pop();
        if (linkFile === currentPageFile || (currentPageFile === '' && linkFile === 'index.php')) {
            link.classList.add('active-nav-link');
        }
    });

    // --- Mobile Nav Logic ---
    function closeMobileMenu() {
        if (mainNav) mainNav.classList.remove('nav-open');
        document.body.classList.remove('no-scroll');
    }

    if (mobileNavToggle) {
        mobileNavToggle.addEventListener('click', function() {
            mainNav.classList.toggle('nav-open');
            document.body.classList.toggle('no-scroll');
        });
    }

    if (mobileNavClose) {
        mobileNavClose.addEventListener('click', closeMobileMenu);
    }

    // --- Dropdown Logic ---
    if (profileButton) {
        profileButton.addEventListener('click', function(event) {
            event.stopPropagation();
            if(notificationDropdown) notificationDropdown.classList.remove('show');
            profileDropdown.classList.toggle('show-dropdown');
        });
    }

    if (notificationButton) {
        notificationButton.addEventListener('click', function(event) {
            event.stopPropagation();
            if(profileDropdown) profileDropdown.classList.remove('show-dropdown');
            notificationDropdown.classList.toggle('show');
            fetchNotifications(); // Fetch fresh notifications when opening
        });
    }

    window.addEventListener('click', function(event) { // Added 'event'
        if (profileDropdown && profileDropdown.classList.contains('show-dropdown')) {
            profileDropdown.classList.remove('show-dropdown');
        }
        if (notificationDropdown && notificationDropdown.classList.contains('show')) {
            notificationDropdown.classList.remove('show');
        }
        
        // --- MODIFICATION: Close new modal on window click ---
        if (notificationViewModal && event.target == notificationViewModal) {
            closeNotificationViewModal();
        }
        // --- END MODIFICATION ---
    });

    if(profileDropdown) profileDropdown.addEventListener('click', e => e.stopPropagation());
    if(notificationDropdown) notificationDropdown.addEventListener('click', e => e.stopPropagation());
    
    // --- MODIFICATION: Add close logic for new modal ---
    function closeNotificationViewModal() {
        if (notificationViewModal) notificationViewModal.style.display = 'none';
    }
    if (notificationViewModalOk) notificationViewModalOk.addEventListener('click', closeNotificationViewModal);
    if (notificationViewModalClose) notificationViewModalClose.addEventListener('click', closeNotificationViewModal);
    // --- END MODIFICATION ---

    
    // --- Notification Fetching and Display Logic ---
    async function fetchNotifications() {
        if (!notificationButton) return;
        try {
            const response = await fetch('get_notifications.php');
            const data = await response.json();
            const notificationCountBadge = document.getElementById('notificationCount');
            
            notificationDropdown.innerHTML = '<div class="notification-header">Notifications</div><div class="notification-body"></div>';
            const notificationBody = notificationDropdown.querySelector('.notification-body');

            if (data.success && data.notifications.length > 0) {
                notificationCountBadge.textContent = data.notifications.length;
                notificationCountBadge.style.display = 'flex';
                
                data.notifications.forEach(notif => {
                    const notifLink = document.createElement('a');
                    notifLink.href = notif.link;
                    notifLink.className = 'notification-item';
                    notifLink.dataset.id = notif.id;
                    notifLink.dataset.type = notif.type;
                    
                    notifLink.dataset.fullMessage = notif.message; // Store full message
                    
                    let displayMessage = notif.message;
                    if (notif.type === 'custom') {
                        // Remove "Admin Reply: " for the short dropdown view
                        displayMessage = displayMessage.replace('Admin Reply: ', '');
                    }
                    // --- MODIFICATION: Truncate reservation messages too ---
                    else if (notif.type === 'reservation') {
                        displayMessage = notif.message; // Already formatted as "Your reservation for..."
                    }
                    // --- END MODIFICATION ---

                    let iconClass = 'fa-info-circle';
                    if (notif.type === 'reservation') {
                        if (notif.message.toLowerCase().includes('confirmed')) {
                            iconClass = 'fa-check-circle';
                        } else if (notif.message.toLowerCase().includes('declined')) {
                            iconClass = 'fa-ban';
                        }
                    } else if (notif.type === 'custom') {
                        iconClass = 'fa-reply';
                    }
                    
                    notifLink.innerHTML = `<i class="fas ${iconClass}"></i><span class="notification-message-text">${displayMessage}</span><button class="notification-dismiss-btn" title="Dismiss">&times;</button>`;
                    notificationBody.appendChild(notifLink);
                });
            } else {
                notificationCountBadge.style.display = 'none';
                notificationBody.innerHTML = '<div class="no-notifications">You have no new notifications.</div>';
            }
        } catch (error) { 
            console.error('Error fetching notifications:', error); 
            const notificationBody = notificationDropdown.querySelector('.notification-body');
            if(notificationBody) notificationBody.innerHTML = '<div class="no-notifications">Could not load notifications.</div>';
        }
    }
    
    async function clearNotification(id, type, element, redirectLink = null) {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('type', type);

        try {
            const response = await fetch('clear_notifications.php', { method: 'POST', body: formData });
            const result = await response.json();

            if (result.success) {
                // Animate removal
                element.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                element.style.opacity = '0';
                element.style.transform = 'translateX(20px)';
                
                setTimeout(() => {
                    if (redirectLink && !redirectLink.endsWith('#')) {
                        window.location.href = redirectLink;
                    } else {
                        element.remove(); // Remove from DOM
                        fetchNotifications(); 
                    }
                }, 300);
            } else {
                if (redirectLink && !redirectLink.endsWith('#')) {
                    window.location.href = redirectLink;
                }
            }
        } catch (error) {
            console.error('Error clearing notification:', error);
            if (redirectLink && !redirectLink.endsWith('#')) {
                window.location.href = redirectLink;
            }
        }
    }
    
    // --- MODIFICATION: Updated click handler to open modal for ALL types ---
    if (notificationDropdown) {
        notificationDropdown.addEventListener('click', async (e) => {
            const dismissBtn = e.target.closest('.notification-dismiss-btn');
            const notificationItem = e.target.closest('.notification-item');

            if (!notificationItem) return; // Click was outside any item

            e.preventDefault(); 

            const id = notificationItem.dataset.id;
            const type = notificationItem.dataset.type;

            if (dismissBtn) {
                // --- Clicked the 'X' button ---
                await clearNotification(id, type, notificationItem, null);
            } else {
                // --- Clicked the notification item itself ---
                
                const fullMessage = notificationItem.dataset.fullMessage;
                let modalTitle = 'Notification'; // Default
                
                if (type === 'custom') {
                    modalTitle = 'Admin Reply';
                } else if (type === 'reservation') {
                    modalTitle = 'Reservation Status';
                }

                // Show the modal
                if (modalNotificationTitle && modalNotificationBody && notificationViewModal) {
                    modalNotificationTitle.textContent = modalTitle;
                    modalNotificationBody.textContent = fullMessage.replace('Admin Reply: ', ''); // Clean prefix just in case
                    notificationViewModal.style.display = 'flex';
                }
                
                // Clear it from the dropdown immediately
                await clearNotification(id, type, notificationItem, null);
            }
        });
    }
    // --- END MODIFICATION ---

    if (notificationButton) {
        fetchNotifications(); // Initial fetch
        setInterval(fetchNotifications, 60000); // Poll for new notifications every 60 seconds
    }
});
</script>