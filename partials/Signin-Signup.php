<div id="signInUpModal" class="modal auth-modal">
    <div class="modal-content auth-modal-content">
        <div class="auth-branding-panel">
            <div class="auth-branding-overlay"></div>
            <div class="auth-branding-content">
                <div class="logo">
                    <div class="logo-main-line">
                        <span>TAVERN PUBLICO</span>
                    </div>
                    <span class="est-year">EST ★ 2024</span>
                </div>
                <p>Taste the tradition, savor the innovation.</p>
            </div>
        </div>

        <div class="auth-form-panel">
            <span class="close-button">&times;</span>

            <div id="signInPanel" class="modal-panel active">
                <div class="modal-form-container">
                    <h2 class="modal-title">Welcome Back</h2>
                    <p class="modal-subtitle">Sign in to continue</p>
                    <form id="signInForm" class="modal-form">
                        <input type="hidden" name="redirect_url" id="redirectUrl">
                        <div class="form-group">
                            <label for="loginUsernameEmail">Username or Email</label>
                            <input type="text" id="loginUsernameEmail" name="username_email" placeholder="e.g., yourname@gmail.com" required>
                        </div>
                        <div class="form-group">
                            <label for="loginPassword">Password</label>
                            <input type="password" id="loginPassword" name="password" placeholder="Enter your password" required>
                        </div>
                        <div class="form-options">
                            <a href="#" id="forgotPasswordLink">Forgot Password?</a>
                        </div>
                        <button type="submit" class="btn btn-primary modal-btn">Sign In</button>
                    </form>
                    <p class="modal-bottom-text">Don't have an account? <a href="#" class="switch-to-register">Register here</a></p>
                </div>
            </div>

            <div id="registerPanel" class="modal-panel">
                <div class="modal-form-container">
                    <h2 class="modal-title">Create Account</h2>
                     <p class="modal-subtitle">Get started with a free account</p>
                    <form id="registerForm" class="modal-form">
                        <div class="form-group">
                            <label for="registerName">Username</label>
                            <input type="text" id="registerName" name="username" placeholder="Choose a unique username" required>
                        </div>
                        <div class="form-group">
                            <label for="registerEmail">Email Address</label>
                            <input type="email" id="registerEmail" name="email" placeholder="yourname@gmail.com" required>
                            <div id="gmail-error-message" class="email-error-message">Only @gmail.com addresses are allowed.</div>
                        </div>
                        <div class="form-group" style="position: relative;">
                            <label for="registerPassword">Password</label>
                            <input type="password" id="registerPassword" name="password" placeholder="Create a strong password" required>
                            <div id="password-rules-modal" class="mini-modal">
                                <p class="validation-rule-container">
                                    <span id="length" class="validation-rule invalid">6+ characters</span>,
                                    <span id="capital" class="validation-rule invalid">1 uppercase</span>,
                                    <span id="special" class="validation-rule invalid">1 special</span>
                                </p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="registerConfirmPassword">Confirm Password</label>
                            <input type="password" id="registerConfirmPassword" name="confirm_password" placeholder="Confirm your password" required>
                        </div>
                        <button type="submit" class="btn btn-primary modal-btn">Register</button>
                    </form>
                    <p class="modal-bottom-text">Already have an account? <a href="#" class="switch-to-signin">Sign In here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="otpModal" class="modal">
    <div class="modal-content single-form-modal">
        <span class="close-button">&times;</span>
        <div class="modal-form-container">
            <h2 class="modal-title">Enter Verification Code</h2>
            <p class="modal-subtitle">A 6-digit code has been sent to your email.</p>
            <form id="otpForm" class="modal-form">
                <input type="hidden" id="otpEmail" name="email">
                <div class="form-group">
                    <label for="otp">Verification Code</label>
                    <input type="text" id="otp" name="otp" placeholder="Enter 6-digit code" required>
                </div>
                <button type="submit" class="btn btn-primary modal-btn">Verify</button>
                <div class="form-options" style="text-align: center; margin-top: 20px;">
                    <a href="#" id="resendRegisterOtpLink" class="disabled-link">Resend Code</a>
                    <span id="resendRegisterTimer"></span>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="forgotPasswordModal" class="modal">
    <div class="modal-content single-form-modal">
        <span class="close-button">&times;</span>
        <div class="modal-form-container">
            <h2 class="modal-title">Reset Password</h2>
            <p class="modal-subtitle">Enter your email to receive a reset code.</p>
            <form id="forgotPasswordForm" class="modal-form">
                <div class="form-group">
                    <label for="forgotEmail">Email Address</label>
                    <input type="email" id="forgotEmail" name="email" placeholder="Your registered email address" required>
                </div>
                <button type="submit" class="btn btn-primary modal-btn">Send Reset Code</button>
            </form>
        </div>
    </div>
</div>

<div id="resetOtpModal" class="modal">
    <div class="modal-content single-form-modal">
        <span class="close-button">&times;</span>
        <div class="modal-form-container">
            <h2 class="modal-title">Enter Reset Code</h2>
            <p class="modal-subtitle">A 6-digit code has been sent to your email.</p>
            <form id="resetOtpForm" class="modal-form">
                <input type="hidden" id="resetOtpEmail" name="email">
                <div class="form-group">
                    <label for="resetOtp">Reset Code</label>
                    <input type="text" id="resetOtp" name="otp" placeholder="Enter 6-digit code" required>
                </div>
                <button type="submit" class="btn btn-primary modal-btn">Verify Code</button>
                <div class="form-options" style="text-align: center; margin-top: 20px;">
                    <a href="#" id="resendOtpLink" class="disabled-link">Resend Code</a>
                    <span id="resendTimer"></span>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="setNewPasswordModal" class="modal">
    <div class="modal-content single-form-modal">
        <span class="close-button">&times;</span>
        <div class="modal-form-container">
            <h2 class="modal-title">Set New Password</h2>
            <p class="modal-subtitle">Create a new, strong password for your account.</p>
            <form id="setNewPasswordForm" class="modal-form">
                <input type="hidden" id="setNewPasswordEmail" name="email">
                <div class="form-group">
                    <label for="newPassword">New Password</label>
                    <input type="password" id="newPassword" name="password" placeholder="Enter new password" required>
                </div>
                <div class="form-group">
                    <label for="newPasswordConfirm">Confirm New Password</label>
                    <input type="password" id="newPasswordConfirm" name="password_confirm" placeholder="Confirm new password" required>
                </div>
                <button type="submit" class="btn btn-primary modal-btn">Save New Password</button>
            </form>
        </div>
    </div>
</div>

<div id="alertModal" class="modal">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <div class="modal-form-container">
             <h2 id="alertModalTitle" class="modal-title"></h2>
             <p id="alertModalMessage" style="margin-bottom: 25px;"></p>
             <button id="alertModalOk" class="btn btn-primary">OK</button>
        </div>
    </div>
</div>

<style>
/* --- NEW & UPDATED UI/UX STYLES FOR ALL AUTH MODALS --- */

/* Main Two-Column Auth Modal */
.auth-modal .modal-content {
    display: flex;
    flex-direction: row;
    max-width: 900px;
    width: 90%;
    padding: 0;
    margin: 0;
    border-radius: 15px;
    overflow: hidden;
    max-height: 90vh; /* --- FIX 1: Added max-height --- */
}

.auth-branding-panel {
    width: 45%;
    background-image: url('images/1st.jpg');
    background-size: cover;
    background-position: center;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 40px;
    position: relative;
}

.auth-branding-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.6);
    z-index: 1;
}

.auth-branding-content { z-index: 2; }
.auth-branding-panel .logo { margin-bottom: 20px; }
.auth-branding-panel .logo .logo-main-line span { color: #FFD700; font-size: 2.5rem; }
.auth-branding-panel .logo .est-year { color: #eee; }
.auth-branding-panel p { font-size: 1.1rem; color: #eee; text-shadow: 1px 1px 3px rgba(0,0,0,0.5); }

.auth-form-panel { 
    width: 55%; 
    padding: 40px 50px; 
    position: relative; 
    background-color: #ffffff; 
    overflow-y: auto; /* --- FIX 2: Added overflow-y --- */
}

/* Single-Form Modals (Forgot Password, OTP, etc.) */
.single-form-modal {
    max-width: 480px;
    width: 95%;
    padding: 20px;
    border-radius: 15px;
}

.single-form-modal .modal-form-container {
    padding: 20px;
}

/* General Form & Panel Styling */
.auth-form-panel .close-button, .single-form-modal .close-button {
    position: absolute;
    top: 15px;
    right: 20px;
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    line-height: 1;
}
.auth-form-panel .close-button:hover, .single-form-modal .close-button:hover { color: #333; }

.modal-form-container { padding: 0; }
.modal-title { 
    text-align: left; 
    font-family: 'Mada', sans-serif;
    font-size: 2rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 8px;
}
.modal-subtitle {
    text-align: left;
    color: #777;
    margin-bottom: 30px;
    font-size: 1rem;
    line-height: 1.5;
}
.single-form-modal .modal-title, .single-form-modal .modal-subtitle {
    text-align: center;
}

.modal-form { width: 100%; }
.modal-form .form-group { text-align: left; margin-bottom: 20px; }
.modal-form .form-group label { font-weight: 600; color: #555; margin-bottom: 8px; font-size: 0.9rem; display: block; }
.modal-form .form-group input {
    width: 100%;
    background-color: #f0f2f5;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 14px;
    font-size: 1rem;
    transition: all 0.3s ease;
    overflow: hidden; 
}
.modal-form .form-group input:focus {
    background-color: #fff;
    border-color: #FFD700;
    box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.3);
    outline: none;
}

.modal-btn {
    width: 100%;
    padding: 14px;
    font-size: 1.1em;
    margin-top: 15px;
    background-color: #1a1a1a;
    color: #fff;
    border-radius: 8px;
    border: 1px solid #1a1a1a;
}
.modal-btn:hover {
    background-color: #FFD700;
    color: #1a1a1a;
    border-color: #FFD700;
}

.modal-bottom-text { text-align: center; margin-top: 25px; font-size: 0.9em; color: #555;}
.modal-bottom-text a { color: #007bff; text-decoration: none; font-weight: 600; }
.modal-bottom-text a:hover { text-decoration: underline; }

.form-options { text-align: right; margin-bottom: 15px; }
#forgotPasswordLink { font-size: 0.9em; color: #007bff; text-decoration: none; }
#forgotPasswordLink:hover { text-decoration: underline; }

/* Responsive Adjustments */
@media (max-width: 768px) {
    .auth-modal .modal-content {
        flex-direction: column;
        width: 95%;
        max-width: 450px;
        max-height: 90vh;
        overflow-y: auto;
    }
    .auth-branding-panel { display: none; }
    .auth-form-panel { 
        width: 100%; 
        padding: 30px 25px; 
        overflow-y: visible; /* On mobile, let the whole modal-content scroll */
    }
    .modal-title { font-size: 1.8rem; }
}

/* Alert Modal Specific Styles */
#alertModal .modal-content { max-width: 400px; text-align: center; }
#alertModal .modal-form-container { padding: 10px 30px 30px 30px; }
#alertModal #alertModalOk { width: auto; min-width: 120px; }

/* Email & Password Validation Styles */
.email-error-message { display: none; color: #e74c3c; font-size: 0.85em; margin-top: 5px; font-weight: 500; }
.mini-modal { display: none; position: absolute; bottom: 105%; left: 0; margin-bottom: 10px; background-color: #333; color: #fff; padding: 10px 15px; border-radius: 6px; z-index: 10; width: auto; white-space: nowrap; box-shadow: 0 4px 8px rgba(0,0,0,0.2); opacity: 0; visibility: hidden; transform: translateY(10px); transition: opacity 0.3s ease, transform 0.3s ease, visibility 0.3s ease; }
.mini-modal.show { display: block; opacity: 1; visibility: visible; transform: translateY(0); }
.mini-modal::after { content: ''; position: absolute; top: 100%; left: 20px; border-width: 5px; border-style: solid; border-color: #333 transparent transparent transparent; }
.mini-modal .validation-rule-container { color: #eee; margin: 0; font-size: 0.85em; }
.mini-modal .validation-rule.invalid { color: #ff8a8a; }
.mini-modal .validation-rule.valid { color: #8aff8a; }

/* --- Loading Animation Styles --- */
.btn-loading { position: relative; color: transparent !important; cursor: wait; pointer-events: none; }
.btn-loading::after {
    content: ''; position: absolute; left: 50%; top: 50%; width: 20px; height: 20px;
    margin-left: -10px; margin-top: -10px; border: 3px solid rgba(255, 255, 255, 0.5);
    border-top-color: #ffffff; border-radius: 50%; animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* --- Resend Link Styles --- */
.disabled-link { color: #999; pointer-events: none; text-decoration: none; }
#resendTimer, #resendRegisterTimer { margin-left: 5px; color: #555; font-weight: bold; }

/* FIX: Ensure modal text is readable when dark theme is active */
body.dark-theme .modal-content,
body.dark-theme .modal-content .modal-title,
body.dark-theme .modal-content .modal-subtitle,
body.dark-theme .modal-content .form-group label,
body.dark-theme .modal-content .modal-bottom-text,
body.dark-theme #resendTimer, 
body.dark-theme #resendRegisterTimer {
    color: #333; /* A standard dark color for text */
}
body.dark-theme .modal-content a { color: #007bff; }
body.dark-theme .disabled-link { color: #999 !important; }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // --- MODAL HANDLING ---
    const signInUpModal = document.getElementById("signInUpModal");
    const otpModal = document.getElementById('otpModal');
    const forgotPasswordModal = document.getElementById('forgotPasswordModal');
    const resetOtpModal = document.getElementById('resetOtpModal');
    const setNewPasswordModal = document.getElementById('setNewPasswordModal');
    const alertModal = document.getElementById('alertModal');
    const openModalBtns = document.querySelectorAll(".signin-button");
    const signInPanel = document.getElementById("signInPanel");
    const registerPanel = document.getElementById("registerPanel");
    const switchToRegisterLinks = document.querySelectorAll(".switch-to-register");
    const switchToSignInLink = document.querySelector(".switch-to-signin");
    const forgotPasswordLink = document.getElementById('forgotPasswordLink');
    const redirectUrlInput = document.getElementById('redirectUrl');

    // --- RESEND OTP ELEMENTS ---
    const resendOtpLink = document.getElementById('resendOtpLink');
    const resendTimerSpan = document.getElementById('resendTimer');
    let resendTimer;
    let countdown;

    const resendRegisterOtpLink = document.getElementById('resendRegisterOtpLink');
    const resendRegisterTimerSpan = document.getElementById('resendRegisterTimer');
    let resendRegisterTimer;
    let registerCountdown;

    function closeModal(modal) { if (modal) modal.style.display = 'none'; }

    // --- COUNTDOWN LOGIC (Password Reset) ---
    function startResendCountdown() {
        countdown = 60; 
        resendOtpLink.classList.add('disabled-link');
        resendTimerSpan.textContent = `(${countdown}s)`;
        resendTimer = setInterval(() => {
            countdown--;
            resendTimerSpan.textContent = `(${countdown}s)`;
            if (countdown <= 0) {
                clearInterval(resendTimer);
                resendTimerSpan.textContent = '';
                resendOtpLink.classList.remove('disabled-link');
            }
        }, 1000);
    }

    // --- COUNTDOWN LOGIC (Registration) ---
    function startRegisterResendCountdown() {
        registerCountdown = 60;
        resendRegisterOtpLink.classList.add('disabled-link');
        resendRegisterTimerSpan.textContent = `(${registerCountdown}s)`;
        resendRegisterTimer = setInterval(() => {
            registerCountdown--;
            resendRegisterTimerSpan.textContent = `(${registerCountdown}s)`;
            if (registerCountdown <= 0) {
                clearInterval(resendRegisterTimer);
                resendRegisterTimerSpan.textContent = '';
                resendRegisterOtpLink.classList.remove('disabled-link');
            }
        }, 1000);
    }

    // --- MODIFIED: Dedicated close function for OTP modal to clear session storage ---
    function closeOtpModal() {
        closeModal(otpModal);
        sessionStorage.removeItem('showOtpModal');
        sessionStorage.removeItem('otpEmail');
        clearInterval(resendRegisterTimer);
        resendRegisterTimerSpan.textContent = '';
        resendRegisterOtpLink.classList.remove('disabled-link');
    }
    
    document.querySelectorAll('.modal .close-button').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const modal = e.target.closest('.modal');
            if (modal.id === 'otpModal') {
                closeOtpModal();
            } else {
                closeModal(modal);
            }
            if (modal.id === 'resetOtpModal') {
                clearInterval(resendTimer);
                resendTimerSpan.textContent = '';
                resendOtpLink.classList.remove('disabled-link');
            }
        });
    });

    if (openModalBtns.length > 0 && signInUpModal) {
        openModalBtns.forEach(btn => {
            btn.onclick = function() {
                if (redirectUrlInput) {
                    redirectUrlInput.value = window.location.href;
                }
                signInUpModal.style.display = "flex";
                if (signInPanel) signInPanel.classList.add("active");
                if (registerPanel) registerPanel.classList.remove("active");
            };
        });
    }

    if(switchToRegisterLinks) {
        switchToRegisterLinks.forEach(link => {
            link.onclick = (e) => { e.preventDefault(); signInPanel.classList.remove("active"); registerPanel.classList.add("active"); };
        });
    }

    if(switchToSignInLink){
        switchToSignInLink.onclick = (e) => { e.preventDefault(); registerPanel.classList.remove("active"); signInPanel.classList.add("active"); };
    }

    if (forgotPasswordLink) {
        forgotPasswordLink.addEventListener('click', (e) => {
            e.preventDefault();
            closeModal(signInUpModal);
            forgotPasswordModal.style.display = 'flex';
        });
    }

    const alertModalTitle = document.getElementById('alertModalTitle');
    const alertModalMessage = document.getElementById('alertModalMessage');
    const alertModalOk = document.getElementById('alertModalOk');
    function showAlert(title, message) {
        alertModalTitle.textContent = title;
        alertModalMessage.textContent = message;
        alertModal.style.display = 'flex';
    }
    if (alertModalOk) alertModalOk.onclick = () => closeModal(alertModal);

    // --- NEW: Check sessionStorage on page load to show OTP modal if needed ---
    if (sessionStorage.getItem('showOtpModal') === 'true') {
        const userEmail = sessionStorage.getItem('otpEmail');
        if (userEmail) {
            document.getElementById('otpEmail').value = userEmail;
            otpModal.style.display = 'flex';
            startRegisterResendCountdown();
        }
    }

    // --- FORM SUBMISSIONS ---
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const password = document.getElementById('registerPassword').value;
            const confirmPassword = document.getElementById('registerConfirmPassword').value;

            if (password !== confirmPassword) {
                showAlert('Registration Failed', 'Passwords do not match. Please try again.');
                return; 
            }

            const submitBtn = registerForm.querySelector('button[type="submit"]');
            submitBtn.classList.add('btn-loading');

            const formData = new FormData(registerForm);
            const userEmail = formData.get('email');

            try {
                const response = await fetch('register.php', { method: 'POST', body: formData });
                if (!response.ok) {
                    throw new Error(`Server responded with status: ${response.status}`);
                }
                const data = await response.json();
                
                if (data.success) {
                    closeModal(signInUpModal);
                    document.getElementById('otpEmail').value = userEmail;
                    // MODIFIED: Save state to sessionStorage before showing modal
                    sessionStorage.setItem('showOtpModal', 'true');
                    sessionStorage.setItem('otpEmail', userEmail);
                    otpModal.style.display = 'flex';
                    startRegisterResendCountdown();
                } else {
                    showAlert('Registration Failed', data.message);
                }

            } catch (error) {
                console.error('Registration error:', error);
                showAlert('Error', 'An unexpected network error occurred. Please try again later.');
            } finally {
                submitBtn.classList.remove('btn-loading');
            }
        });
    }
    
    if (resendRegisterOtpLink) {
        resendRegisterOtpLink.addEventListener('click', async (e) => {
            e.preventDefault();
            if (resendRegisterOtpLink.classList.contains('disabled-link')) return;

            const userEmail = document.getElementById('otpEmail').value;
            if (!userEmail) {
                showAlert('Error', 'Could not find the email to resend the code.');
                return;
            }

            resendRegisterOtpLink.textContent = 'Sending...';
            resendRegisterOtpLink.classList.add('disabled-link');
            
            const formData = new FormData();
            formData.append('email', userEmail);

            try {
                const response = await fetch('resend_otp.php', { method: 'POST', body: formData });
                const data = await response.json();
                
                if (data.success) {
                    showAlert('Success', 'A new verification code has been sent.');
                    startRegisterResendCountdown();
                } else {
                    showAlert('Error', data.message || 'Failed to resend code.');
                    resendRegisterOtpLink.classList.remove('disabled-link');
                }
            } catch (error) {
                showAlert('Error', 'An unexpected network error occurred.');
                resendRegisterOtpLink.classList.remove('disabled-link');
            } finally {
                resendRegisterOtpLink.textContent = 'Resend Code';
            }
        });
    }

    const signInForm = document.getElementById('signInForm');
    if (signInForm) {
        signInForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = signInForm.querySelector('button[type="submit"]');
            submitBtn.classList.add('btn-loading');
            const formData = new FormData(signInForm);
            try {
                const response = await fetch('login.php', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        window.location.reload();
                    }
                } else {
                    showAlert('Login Failed', data.message);
                }
            } catch (error) {
                console.error('Login error:', error);
                showAlert('Error', 'An unexpected network error occurred.');
            } finally {
                submitBtn.classList.remove('btn-loading');
            }
        });
    }

    const otpForm = document.getElementById('otpForm');
    if (otpForm) {
        otpForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = otpForm.querySelector('button[type="submit"]');
            submitBtn.classList.add('btn-loading');
            const formData = new FormData(otpForm);

            try {
                const response = await fetch('verify_otp.php', { method: 'POST', body: formData });
                const data = await response.json();
                
                if (data.success) {
                    // MODIFIED: Clear session storage on success
                    closeOtpModal();
                    showAlert('Success!', data.message);
                } else {
                    showAlert('Verification Failed', data.message);
                }
            } catch (error) {
                console.error('OTP Verification error:', error);
                showAlert('Error', 'An unexpected network error occurred during verification.');
            } finally {
                submitBtn.classList.remove('btn-loading');
            }
        });
    }

    // --- PASSWORD RESET FLOW ---
    const forgotPasswordForm = document.getElementById('forgotPasswordForm');
    const resetOtpForm = document.getElementById('resetOtpForm');
    const setNewPasswordForm = document.getElementById('setNewPasswordForm');

    if (forgotPasswordForm) {
        forgotPasswordForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = forgotPasswordForm.querySelector('button[type="submit"]');
            submitBtn.classList.add('btn-loading');
            const formData = new FormData(forgotPasswordForm);
            const userEmail = formData.get('email');
            try {
                const response = await fetch('forgot_password.php', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    closeModal(forgotPasswordModal);
                    document.getElementById('resetOtpEmail').value = userEmail; 
                    resetOtpModal.style.display = 'flex';
                    startResendCountdown();
                } else {
                    showAlert('Error', data.message);
                }
            } catch (error) {
                console.error('Forgot password error:', error);
                showAlert('Error', 'An unexpected network error occurred.');
            } finally {
                submitBtn.classList.remove('btn-loading');
            }
        });
    }

    if (resendOtpLink) {
        resendOtpLink.addEventListener('click', async (e) => {
            e.preventDefault();
            if (resendOtpLink.classList.contains('disabled-link')) return;
            const userEmail = document.getElementById('resetOtpEmail').value;
            if (!userEmail) {
                showAlert('Error', 'Could not find the email to resend the code.');
                return;
            }
            resendOtpLink.textContent = 'Sending...';
            resendOtpLink.classList.add('disabled-link');
            const formData = new FormData();
            formData.append('email', userEmail);
            try {
                const response = await fetch('forgot_password.php', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    showAlert('Success', 'A new reset code has been sent to your email.');
                    startResendCountdown();
                } else {
                    showAlert('Error', data.message || 'Failed to resend code.');
                }
            } catch (error) {
                showAlert('Error', 'An unexpected network error occurred.');
            } finally {
                resendOtpLink.textContent = 'Resend Code';
            }
        });
    }

    if (resetOtpForm) {
        resetOtpForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = resetOtpForm.querySelector('button[type="submit"]');
            submitBtn.classList.add('btn-loading');
            const formData = new FormData(resetOtpForm);
            const userEmail = formData.get('email');
            try {
                const response = await fetch('verify_reset_otp.php', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    clearInterval(resendTimer);
                    
                    closeModal(resetOtpModal); 
                    
                    document.getElementById('setNewPasswordEmail').value = userEmail; 
                    setNewPasswordModal.style.display = 'flex';
                } else {
                    showAlert('Verification Failed', data.message);
                }
            } catch (error) {
                console.error('Reset OTP error:', error);
            } finally {
                submitBtn.classList.remove('btn-loading');
            }
        });
    }

    if (setNewPasswordForm) {
        setNewPasswordForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = setNewPasswordForm.querySelector('button[type="submit"]');
            submitBtn.classList.add('btn-loading');
            const formData = new FormData(setNewPasswordForm);
            try {
                const response = await fetch('update_password.php', { method: 'POST', body: formData });
                const data = await response.json();
                closeModal(setNewPasswordModal);
                showAlert(data.success ? 'Success!' : 'Error', data.message);
            } catch (error) {
                console.error('Update password error:', error);
            } finally {
                submitBtn.classList.remove('btn-loading');
            }
        });
    }

    // --- REAL-TIME PASSWORD VALIDATION ---
    const registerPasswordInput = document.getElementById('registerPassword');
    const passwordRulesModal = document.getElementById('password-rules-modal');
    const lengthRule = document.getElementById('length');
    const capitalRule = document.getElementById('capital');
    const specialRule = document.getElementById('special');

    if (registerPasswordInput && passwordRulesModal && lengthRule && capitalRule && specialRule) {
        registerPasswordInput.addEventListener('focus', () => {
            passwordRulesModal.classList.add('show');
        });
        registerPasswordInput.addEventListener('blur', () => {
            passwordRulesModal.classList.remove('show');
        });
        registerPasswordInput.addEventListener('input', () => {
            const password = registerPasswordInput.value;
            if (password.length >= 6) { lengthRule.classList.replace('invalid', 'valid'); } 
            else { lengthRule.classList.replace('valid', 'invalid'); }
            if (/[A-Z]/.test(password)) { capitalRule.classList.replace('invalid', 'valid'); } 
            else { capitalRule.classList.replace('valid', 'invalid'); }
            if (/[^A-Za-z0-9]/.test(password)) { specialRule.classList.replace('invalid', 'valid'); } 
            else { specialRule.classList.replace('valid', 'invalid'); }
        });
    }
});
</script>