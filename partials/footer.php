<?php
// Make sure Font Awesome is linked on the page this partial is included in.
// Example link to add in your <head>:
// <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
?>

<style>
    /* Added styles for social icon hover effect */
    .social-icons a:hover {
        color: #fff !important; /* Make sure hover color is white */
        transform: scale(1.1);
    }
</style>

<footer class="main-footer" style="background-color: #1a1a1a; color: #bbb; padding: clamp(2rem, 8vw, 4rem) 0;">
    <div class="container footer-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: clamp(1.5rem, 5vw, 2.5rem); align-items: start;">
        <div class="footer-about">
            <p style="font-family: 'Madimi One', sans-serif; letter-spacing: 0.5px; line-height: 1.2; color: #fff; font-size: clamp(1.5rem, 4vw, 2rem);">Tavern Publico</p>
            <p style="font-family: 'Mada', sans-serif; margin-top: 10px; line-height: 1.6; font-size: 0.95em; font-weight: 400;">EST ★ 2024</p>
            <p style="font-family: 'Mada', sans-serif; margin-top: 10px; line-height: 1.6; font-size: 0.95em; font-weight: 400;">Taste the tradition, savor the innovation.</p>
            <div class="social-icons" style="margin-top: 20px; display: flex; gap: 20px; align-items: center;">
                <a href="https://www.facebook.com/profile.php?id=61561038154883#" aria-label="Facebook" style="color: #bbb; text-decoration: none; font-size: 24px; transition: color 0.3s ease, transform 0.3s ease;">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="#" aria-label="Twitter" style="color: #bbb; text-decoration: none; font-size: 24px; transition: color 0.3s ease, transform 0.3s ease;">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="https://www.instagram.com/reel/DBZC_iuSQO_/" aria-label="Instagram" style="color: #bbb; text-decoration: none; font-size: 24px; transition: color 0.3s ease, transform 0.3s ease;">
                    <i class="fab fa-instagram"></i>
                </a>
            </div>
        </div>
        <div class="footer-links">
            <h3 style="font-family: 'Mada', sans-serif; color: #fff; margin-bottom: 25px; font-weight: 600; line-height: 1.2; font-size: clamp(1.1rem, 2vw, 1.2rem);">Quick Links</h3>
            <ul style="list-style: none; padding: 0;">
                <li><a href="index.php" style="font-family: 'Mada', sans-serif; color: #bbb; text-decoration: none; display: block; margin-bottom: 10px; transition: color 0.3s ease; font-size: 0.95em; font-weight: 400;">Home</a></li>
                <li><a href="menu.php" style="font-family: 'Mada', sans-serif; color: #bbb; text-decoration: none; display: block; margin-bottom: 10px; transition: color 0.3s ease; font-size: 0.95em; font-weight: 400;">Menu</a></li>
                <li><a href="about.php" style="font-family: 'Mada', sans-serif; color: #bbb; text-decoration: none; display: block; margin-bottom: 10px; transition: color 0.3s ease; font-size: 0.95em; font-weight: 400;">About</a></li>
                <li><a href="events.php" style="font-family: 'Mada', sans-serif; color: #bbb; text-decoration: none; display: block; margin-bottom: 10px; transition: color 0.3s ease; font-size: 0.95em; font-weight: 400;">Events</a></li>
                <li><a href="gallery.php" style="font-family: 'Mada', sans-serif; color: #bbb; text-decoration: none; display: block; margin-bottom: 10px; transition: color 0.3s ease; font-size: 0.95em; font-weight: 400;">Gallery</a></li>
                <li><a href="contact.php" style="font-family: 'Mada', sans-serif; color: #bbb; text-decoration: none; display: block; margin-bottom: 10px; transition: color 0.3s ease; font-size: 0.95em; font-weight: 400;">Contact</a></li>
            </ul>
        </div>
        <div class="footer-contact">
            <h3 style="font-family: 'Mada', sans-serif; color: #fff; margin-bottom: 25px; font-weight: 600; line-height: 1.2; font-size: clamp(1.1rem, 2vw, 1.2rem);">Contact Us</h3>
            <p style="font-family: 'Mada', sans-serif; margin-bottom: 10px; line-height: 1.6; font-size: 0.95em; font-weight: 400;">269 Floridablanca Road, Jose Abad Santos</p>
            <p style="font-family: 'Mada', sans-serif; margin-bottom: 10px; line-height: 1.6; font-size: 0.95em; font-weight: 400;">Publicotavern@gmail.com</p>
            <p style="font-family: 'Mada', sans-serif; margin-bottom: 10px; line-height: 1.6; font-size: 0.95em; font-weight: 400;">Phone: +65 85712615</p>
        </div>
        <div class="footer-hours">
            <h3 style="font-family: 'Mada', sans-serif; color: #fff; margin-bottom: 25px; font-weight: 600; line-height: 1.2; font-size: clamp(1.1rem, 2vw, 1.2rem);">Hours</h3>
            <p style="font-family: 'Mada', sans-serif; margin-bottom: 10px; line-height: 1.6; font-size: 0.95em; font-weight: 400;">Monday - Wednesday: 11:00 AM - 9:00 PM</p>
            <p style="font-family: 'Mada', sans-serif; margin-bottom: 10px; line-height: 1.6; font-size: 0.95em; font-weight: 400;">Thursday - Friday: 11:00 AM - 9:00 PM</p>
            <p style="font-family: 'Mada', sans-serif; margin-bottom: 10px; line-height: 1.6; font-size: 0.95em; font-weight: 400;">Saturday - Sunday  11:00 AM - 9:00 PM </p>
            
        </div>
    </div>
    <div class="footer-bottom" style="text-align: center; margin-top: 40px; padding-top: 30px; border-top: 1px solid #333; font-size: 0.9em; color: #999;">
        <p>© 2024 Tavern Publico. All rights reserved.</p>
    </div>
</footer>