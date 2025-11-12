<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tavern Publico - Contact Us</title>
    
    <link rel="stylesheet" href="CSS/main.css">
    <link rel="stylesheet" href="CSS/dark-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Inlined and mobile-responsive styles for the contact page */
        .contact-info-section {
            background-color: #fefefe;
        }

        .contact-info-section .section-heading {
            margin-bottom: 60px;
            font-size: 2.8em;
            text-align: center;
            color: #222;
            font-weight: 700;
        }

        /* Contact Cards Grid */
        .contact-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr); /* 3 columns on desktop */
            gap: 30px;
            margin-bottom: 80px;
            text-align: center;
        }

        .contact-card {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 200px;
        }

        .contact-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .contact-card h3 {
            font-family: 'Mada', sans-serif;
            font-size: 1.8em;
            margin-bottom: 15px;
            color: #333;
            font-weight: 600;
        }

        .contact-card p {
            font-family: 'Mada', sans-serif;
            font-size: 1.1em;
            color: #555;
            line-height: 1.6;
            margin-bottom: 8px;
        }

        .contact-card a {
            color: #007bff;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .contact-card a:hover {
            color: #0056b3;
            text-decoration: underline;
        }

        /* Contact Form and Map Grid */
        .contact-form-map-grid {
            display: grid;
            grid-template-columns: 1fr 1fr; /* Two columns on desktop */
            gap: 50px;
            align-items: flex-start;
        }

        .contact-form-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .contact-form-container h3 {
            font-family: 'Mada', sans-serif;
            font-size: 2.2em;
            margin-bottom: 30px;
            color: #222;
            font-weight: 700;
            text-align: center;
        }

        .contact-form .form-group {
            margin-bottom: 20px;
        }

        .contact-form label {
            display: block;
            font-family: 'Mada', sans-serif;
            font-size: 1em;
            color: #444;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .contact-form input[type="text"],
        .contact-form input[type="email"],
        .contact-form textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: 'Mada', sans-serif;
            font-size: 1em;
            color: #333;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .contact-form input[readonly] {
            background-color: #f0f2f5;
            cursor: not-allowed;
        }
        
        .contact-form input:focus,
        .contact-form textarea:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.2);
        }

        .map-container iframe {
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            width: 100%;
            height: 100%;
            min-height: 450px;
        }

        /* --- MOBILE RESPONSIVE STYLES --- */
        @media (max-width: 992px) {
            .contact-form-map-grid {
                grid-template-columns: 1fr; /* Stack form and map */
            }
        }

        @media (max-width: 768px) {
            .contact-info-section .section-heading {
                font-size: 2.2em;
            }

            .contact-grid {
                grid-template-columns: 1fr; /* Stack info cards */
                gap: 20px;
                margin-bottom: 40px;
            }

            .contact-form-container {
                padding: 30px;
            }
        }

    </style>
</head>
<body>

    <?php include 'partials/header.php'; ?>

    <main>
        <section class="contact-info-section common-padding">
            <div class="container">
                <h2 class="section-heading">Get in Touch</h2>
                <div class="contact-grid">
                    <div class="contact-card">
                        <i class="fas fa-map-marker-alt" style="font-size: 2em; color: #FFD700; margin-bottom: 15px;"></i>
                        <h3>Location</h3>
                        <p>269 Floridablanca Road, Jose Abad Santos (Siran), Guagua, Pampanga</p>
                        <p><a href="https://www.google.com/maps/place/269+Floridablanca+Rd,+Jose+Abad+Santos(Siran),+Guagua,+Pampanga,+Philippines" target="_blank">View on Google Maps</a></p>
                    </div>
                    <div class="contact-card">
                        <i class="fas fa-phone-alt" style="font-size: 2em; color: #FFD700; margin-bottom: 15px;"></i>
                        <h3>Reservations & Inquiries</h3>
                        <p>Phone: (045) 123-4567</p>
                        <p>Email: info@tavernpublico.com</p>
                    </div>
                    <div class="contact-card">
                        <i class="fas fa-clock" style="font-size: 2em; color: #FFD700; margin-bottom: 15px;"></i>
                        <h3>Hours of Operation</h3>
                        <p>Monday - Thursday: 11am - 10pm</p>
                        <p>Friday - Saturday: 11am - 12am</p>
                        <p>Sunday: 10am - 9pm</p>
                    </div>
                </div>

                <div class="contact-form-map-grid">
                    <div class="contact-form-container">
                        <h3>Send Us a Message</h3>
                        
                        <form class="contact-form" id="contactForm" action="process_contact_form.php" method="POST">
                            <div class="form-group">
                                <label for="contactName">Name</label>
                                <input type="text" id="contactName" name="contactName" required
                                    <?php if(isset($_SESSION['loggedin'])): ?>
                                        value="<?php echo htmlspecialchars($_SESSION['username']); ?>" readonly
                                    <?php endif; ?>
                                >
                            </div>
                            <div class="form-group">
                                <label for="contactEmail">Email</label>
                                <input type="email" id="contactEmail" name="contactEmail" required
                                    <?php 
                                    if(isset($_SESSION['loggedin'])) {
                                        // Since the header now includes db_connect.php and creates a connection,
                                        // we should ensure we don't create a new one.
                                        // However, the header script creates and closes its own connection.
                                        // To be safe, we will connect here once.
                                        require_once 'db_connect.php'; // Use require_once to prevent re-definition errors
                                        
                                        $user_email = '';
                                        $sql_email = "SELECT email FROM users WHERE user_id = ?";
                                        
                                        if($stmt = mysqli_prepare($link, $sql_email)){
                                            mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
                                            mysqli_stmt_execute($stmt);
                                            mysqli_stmt_bind_result($stmt, $user_email);
                                            mysqli_stmt_fetch($stmt);
                                            mysqli_stmt_close($stmt);
                                        }
                                        // Do NOT close the main $link connection here, as other parts of the page might need it.
                                        
                                        echo 'value="' . htmlspecialchars($user_email) . '" readonly';
                                    }
                                    ?>
                                >
                            </div>
                            <div class="form-group">
                                <label for="contactSubject">Subject</label>
                                <input type="text" id="contactSubject" name="contactSubject" value="Reservation Inquiry">
                            </div>
                            <div class="form-group">
                                <label for="contactMessage">Message</label>
                                <textarea id="contactMessage" name="contactMessage" rows="6" required maxlength="500"></textarea>
                            </div>
                            
                            <button type="submit" id="contactSubmitBtn" class="btn btn-primary" style="width: 100%;">Send Message</button>
                        </form>
                    </div>
                   <div class="map-container">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3855.589858212431!2d120.6180582148446!3d14.96062708960249!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33965f60b4c7c4c7%3A0x4c27f3b2e5cb4e6d!2s269%20Floridablanca%20Rd%2C%20Jose%20Abad%20Santos(Siran)%2C%20Guagua%2C%20Pampanga%2C%20Philippines!5e0!3m2!1sen!2sus!4v1695822365738!5m2!1sen!2sus" allowfullscreen="" loading="lazy"></iframe>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'partials/footer.php'; ?>
    <?php include 'partials/Signin-Signup.php'; ?>

    <script src="JS/main.js"></script>
    <script src="JS/theme-switcher.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {

            // --- 1. Alert Modal Logic (using the modal from Signin-Signup.php) ---
            const alertModal = document.getElementById('alertModal');
            const alertModalTitle = document.getElementById('alertModalTitle');
            const alertModalMessage = document.getElementById('alertModalMessage');
            const alertModalOk = document.getElementById('alertModalOk');
            const alertModalCloseBtn = alertModal ? alertModal.querySelector('.close-button') : null;

            function showAlert(title, message) {
                if (alertModalTitle) alertModalTitle.textContent = title;
                if (alertModalMessage) alertModalMessage.textContent = message;
                if (alertModal) alertModal.style.display = 'flex';
            }
            
            function closeAlertModal() {
                if (alertModal) alertModal.style.display = 'none';
            }

            if (alertModalOk) alertModalOk.onclick = closeAlertModal;
            if (alertModalCloseBtn) alertModalCloseBtn.onclick = closeAlertModal;

            // --- 2. Check for URL Status Messages ---
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');

            if (status) {
                let title = 'Notice';
                let message = '';

                if (status === 'success' || status === 'success_no_email') {
                    title = 'Message Sent!';
                    message = 'Thank you for contacting us. We have received your inquiry and will get back to you shortly.';
                } else if (status === 'invalid_email_numeric') {
                    title = 'Invalid Email';
                    message = 'Emails with only numbers in the name (e.g., 12345@gmail.com) are not allowed.';
                } else if (status === 'invalid_email') {
                    title = 'Invalid Email';
                    message = 'Please provide a valid email address.';
                } else if (status === 'error' || status === 'db_error') {
                    title = 'Submission Failed';
                    message = 'An error occurred while sending your message. Please try again later.';
                }
                
                if (message) {
                    showAlert(title, message);
                }
                
                // Clean the URL
                history.replaceState(null, '', window.location.pathname);
            }

            // --- 3. Add Loading Animation to Form Submit ---
            const contactForm = document.getElementById('contactForm');
            const contactSubmitBtn = document.getElementById('contactSubmitBtn');

            if (contactForm && contactSubmitBtn) {
                contactForm.addEventListener('submit', () => {
                    // Check if form is valid (basic HTML5 validation)
                    if (contactForm.checkValidity()) {
                        contactSubmitBtn.classList.add('btn-loading'); // This class is in Signin-Signup.php
                        contactSubmitBtn.disabled = true;
                        
                        // We don't call preventDefault(), so the form will submit normally
                        // and the page will reload, resetting the button.
                        // This effectively prevents double-clicks.
                    }
                });
            }
        });
    </script>
    </body>
</html>