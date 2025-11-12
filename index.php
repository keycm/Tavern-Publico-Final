<?php
session_start();
include 'config.php'; // Include your database configuration

// Fetch hero slides
$hero_slides = [];
$sql_slides = "SELECT * FROM hero_slides WHERE deleted_at IS NULL ORDER BY media_type DESC, created_at DESC";
$result_slides = $conn->query($sql_slides);
if ($result_slides && $result_slides->num_rows > 0) { // Added check for $result_slides
    $video_count = 0;
    $image_count = 0;
    while($row = $result_slides->fetch_assoc()) {
        if ($row['media_type'] === 'video' && $video_count < 1) {
            $hero_slides[] = $row;
            $video_count++;
        } elseif ($row['media_type'] === 'image' && $image_count < 4) {
            $hero_slides[] = $row;
            $image_count++;
        }
    }
}

// Fetch THE LATEST unrated reservation for the logged-in user
$unrated_reservation = null;
$show_modal_on_load = false;
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']) {
    // Check if the session flag is set to show the modal
    if (isset($_SESSION['show_rating_modal']) && $_SESSION['show_rating_modal'] === true) {
        $user_id = $_SESSION['user_id'];
        $sql_unrated = "SELECT r.reservation_id, r.res_date FROM reservations r LEFT JOIN testimonials t ON r.reservation_id = t.reservation_id WHERE r.user_id = ? AND t.id IS NULL AND r.status = 'Confirmed' AND r.deleted_at IS NULL ORDER BY r.res_date DESC LIMIT 1";

        if ($stmt = $conn->prepare($sql_unrated)) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $unrated_reservation = $result->fetch_assoc();
                $show_modal_on_load = true; // We have a reservation to rate, so we should show the modal
            }
             // Unset the session variable so the modal doesn't pop up on every page refresh
            $_SESSION['show_rating_modal'] = false; // Always unset after checking
            $stmt->close(); // Close statement
        }
    }
}


// Fetch featured testimonials
$featured_testimonials = [];
$sql_testimonials = "SELECT t.*, u.username, u.avatar FROM testimonials t JOIN users u ON t.user_id = u.user_id WHERE t.is_featured = 1 AND t.deleted_at IS NULL ORDER BY t.created_at DESC LIMIT 3";
$result_testimonials = $conn->query($sql_testimonials);
if ($result_testimonials && $result_testimonials->num_rows > 0) { // Added check for $result_testimonials
    while ($row = $result_testimonials->fetch_assoc()) {
        $featured_testimonials[] = $row;
    }
}

// It's good practice to close the connection if it's not needed anymore on the page.
// However, since partials might use it, ensure they include config.php if needed or handle connection persistence.
// $conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tavern Publico</title>
    <link rel="stylesheet" href="CSS/main.css">
    <link rel="stylesheet" href="CSS/dark-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* --- STYLES REMOVED FROM HERE --- */
        /* The faulty CSS override that was forcing the 32px logo size has been deleted. */
        /* The page will now correctly use the responsive styles from CSS/main.css */

        /* --- INLINED RESPONSIVE HERO SECTION STYLES --- */
        .hero-section .hero-overlay {
            justify-content: flex-start;
            align-items: center;
            background-color: rgba(0, 0, 0, 0.6);
            padding-inline: clamp(1.5rem, 10vw, 8rem);
            padding-block: 2rem;
            box-sizing: border-box;
        }

        .hero-text-container { text-align: left; max-width: 650px; }
        .hero-text-container h1 { font-family: 'Madimi One', sans-serif; margin-bottom: 15px; color: #FFD700; line-height: 1.2; font-weight: 700; text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.7); font-size: clamp(2.2rem, 7vw + 1rem, 4.5rem); word-wrap: break-word; hyphens: auto; }
        .hero-text-container p { margin-bottom: 25px; max-width: 500px; color: #FFFFFF; text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.7); font-size: clamp(0.9rem, 2vw + 0.5rem, 1.2rem); }
        
        /* === MODIFIED BUTTON STYLES === */
        .hero-buttons { display: flex; gap: 15px; }
        .hero-buttons .btn { 
            border-radius: 8px; 
            font-weight: bold; 
            padding: 14px 20px; 
            font-size: 1em; 
            text-transform: none; 
            transition: all 0.3s ease; 
            width: 180px; 
            display: inline-flex; 
            justify-content: center; 
            align-items: center; 
            margin-top: 0; 
            text-decoration: none; 
        }
        
        /* Secondary Button (View Menu) */
        .hero-buttons .btn.btn-outline-white { 
            background-color: transparent; 
            color: #fff; 
            border: 2px solid #fff; /* Made border thicker */
        }
        
        /* Primary Button (Reserve Now) */
        .hero-buttons .btn.btn-secondary { 
            background-color: #FFD700; /* Main brand color */
            color: #1a1a1a; /* Dark text for contrast */
            border: 2px solid #FFD700; 
        }
        
        /* General Hover Effect */
        .hero-buttons .btn:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 4px 8px rgba(0,0,0,0.2); 
        }
        
        /* Secondary Hover (View Menu) */
        .hero-buttons .btn.btn-outline-white:hover { 
            background-color: #fff; /* Fill white on hover */
            color: #1a1a1a; /* Dark text on hover */
            border-color: #fff; 
        }
        
        /* Primary Hover (Reserve Now) */
        .hero-buttons .btn.btn-secondary:hover { 
            background-color: #e6c200; /* Darker yellow */
            border-color: #e6c200; 
            color: #1a1a1a;
        }
        /* === END MODIFIED BUTTON STYLES === */

        .section-heading-v2 .main-title { white-space: nowrap; }

        /* --- INLINED RESPONSIVE SECTION HEADING & SLIDER STYLES --- */
        .guest-testimonials-section h2 { font-size: clamp(2rem, 5vw + 1rem, 2.8rem); }
        .section-heading-v2 .sub-title { font-size: clamp(1.5rem, 4vw + 0.5rem, 2.2rem); }
        .section-heading-v2 .main-title { font-size: clamp(1.8rem, 5vw + 1rem, 2.8rem); }

        .hero-bg-video { position: absolute; top: 50%; left: 50%; width: 100%; height: 100%; object-fit: cover; transform: translate(-50%, -50%); }

        /* --- MODAL RATING FORM STYLES --- */
        #ratingModal .modal-content { max-width: 550px; }
        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: center;
            gap: 5px;
            margin-bottom: 15px;
        }
        .star-rating input { display: none; }
        .star-rating label {
            font-size: 2.5rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #FFD700;
        }
        #ratingModal .modal-form-container { padding: 35px 30px; }
        #ratingModal .modal-title { text-align: center; margin-bottom: 25px; }
        #ratingModal .modal-form { width: 100%; }

        .slider-wrapper { display: grid; grid-template-columns: repeat(3, 1fr); gap: 29px; }
        .slider-btn { display: none !important; } /* Force hide slider buttons */


        @media (max-width: 768px) {
            .hero-section { height: 80vh; }
            .hero-overlay { justify-content: center; }
            .hero-text-container { text-align: center; display: flex; flex-direction: column; align-items: center; }
            .hero-buttons { flex-direction: column; align-items: center; width: 80%; max-width: 300px; }
            .hero-buttons .btn { width: 100%; text-align: center; }
            .slider-wrapper { display: flex; overflow-x: visible; }
            .slider-item { flex: 0 0 90%; margin-right: 15px; }
            .slider-item:last-child { margin-right: 0; }
            /* .slider-btn rule above already hides them */
             .slider-container { overflow: hidden; }
        }

        @media (max-width: 480px) {
             .section-heading-v2 .main-title { white-space: normal; padding: 0; }
             .section-heading-v2 .line { display: none; }
             .slider-item { flex: 0 0 100%; }
        }

        @import url("https://fonts.googleapis.com/css?family=Signika+Negative:300,400&display=swap");
        *, *:before, *:after { box-sizing: border-box; position: relative; }
        h1 { font-size: 40px; line-height: 1.2; margin: 0; }
        .revealUp { opacity: 0; visibility: hidden; transform: translateY(20px); transition: opacity 1s, transform 1s, visibility 1s; }
        .revealUp.active { opacity: 1; visibility: visible; transform: translateY(0); }

        .section-heading-v2, .guest-testimonials-section h2 { background-color: transparent !important; }
        .specialty-card p {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            min-height: 3em;
        }
        
        .specialty-card .image-container {
            position: relative; 
            width: 100%;
            height: 210px; 
            margin-bottom: 20px; 
        }

        .specialty-card .image-container img {
            width: 100%;
            height: 100%; 
            object-fit: cover;
            margin-bottom: 0; 
        }
        
        .hot-sticker-animated {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 50px; 
            height: 50px;
            background-color: #e74c3c; 
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Mada', sans-serif;
            font-weight: 700;
            font-size: 16px;
            text-transform: uppercase;
            z-index: 10;
            animation: pulse-fire 1.5s infinite;
        }

        @keyframes pulse-fire {
            0% {
                box-shadow: 0 0 8px rgba(231, 76, 60, 0.7), 
                            0 0 10px rgba(231, 76, 60, 0.5);
            }
            50% {
                box-shadow: 0 0 16px rgba(243, 156, 18, 1), 
                            0 0 20px rgba(243, 156, 18, 0.7),
                            0 0 25px rgba(231, 76, 60, 0.5); 
            }
            100% {
                box-shadow: 0 0 8px rgba(231, 76, 60, 0.7), 
                            0 0 10px rgba(231, 76, 60, 0.5);
            }
        }

    </style>
</head>

<body>

    <?php include 'partials/header.php'; ?>

    <section class="hero-section">
        <div class="slideshow-container">
            <?php if (!empty($hero_slides)): ?>
                <?php foreach ($hero_slides as $index => $slide): ?>
                    <div class="mySlides fade">
                        <?php if ($slide['media_type'] === 'video' && !empty($slide['video_path'])): ?>
                            <video autoplay muted playsinline class="hero-bg-video">
                                <source src="<?php echo htmlspecialchars($slide['video_path']); ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        <?php elseif (!empty($slide['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($slide['image_path']); ?>" alt="Hero Image <?php echo $index + 1; ?>" class="hero-bg-image">
                        <?php endif; ?>

                        <div class="hero-overlay">
                            <div class="hero-text-container">
                                <?php if (!empty($slide['title'])): ?>
                                    <h1><?php echo htmlspecialchars($slide['title']); ?></h1>
                                <?php else: ?>
                                    <h1>Experience Authentic Flavors at Tavern Publico</h1>
                                <?php endif; ?>

                                <?php if (!empty($slide['subtitle'])): ?>
                                    <p><?php echo htmlspecialchars($slide['subtitle']); ?></p>
                                <?php else: ?>
                                    <p>Craft coffee, comfort food, and a welcoming atmosphere in the heart of the city.</p>
                                <?php endif; ?>

                                <div class="hero-buttons">
                                    <a href="menu.php" class="btn btn-outline-white">View Menu</a>
                                    <?php
                                    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
                                        echo '<a href="reserve.php" class="btn btn-secondary">Reserve Now</a>';
                                    } else {
                                        echo '<button class="btn btn-secondary signin-button">Reserve Now</button>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div style="text-align:center; position: absolute; bottom: 20px; width: 100%; z-index: 2;">
                    <?php foreach ($hero_slides as $index => $slide): ?>
                        <span class="dot" onclick="currentSlide(<?php echo $index + 1; ?>)"></span>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                 <div class="mySlides fade" style="display:block;">
                    <img src="images/story.jpg" alt="Default Hero Image" class="hero-bg-image">
                    <div class="hero-overlay">
                        <div class="hero-text-container">
                            <h1>Experience Authentic Flavors at Tavern Publico</h1>
                            <p>Craft coffee, comfort food, and a welcoming atmosphere in the heart of the city.</p>
                            <div class="hero-buttons">
                                <a href="menu.php" class="btn btn-outline-white">View Menu</a>
                                <?php
                                if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
                                    echo '<a href="reserve.php" class="btn btn-secondary">Reserve Now</a>';
                                } else {
                                    echo '<button class="btn btn-secondary signin-button">Reserve Now</button>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="specialties-section common-padding">
    <div class="container">
        <div class="section-heading-v2 revealUp">
            <div class="sub-title">Freshly Taste</div>
            <div class="title-with-lines">
                <div class="line"></div>
                <h2 class="main-title">Our Specialties</h2>
                <div class="line"></div>
            </div>
        </div>
            <div class="slider-container" id="specialtiesSliderContainer">
                <div class="slider-wrapper">
                    <?php
                    $sql_specialties = "SELECT * FROM menu WHERE category = 'Specialty' AND deleted_at IS NULL ORDER BY RAND() LIMIT 3";
                    $result_specialties = $conn->query($sql_specialties);
                    if ($result_specialties && $result_specialties->num_rows > 0) {
                        while ($row = $result_specialties->fetch_assoc()) {
                            echo '<div class="slider-item"><div class="specialty-card">';
                            
                            echo '<div class="image-container">';
                            echo '  <img src="' . htmlspecialchars($row['image']) . '" alt="' . htmlspecialchars($row['name']) . '">';
                            echo '  <div class="hot-sticker-animated">Hot</div>';
                            echo '</div>';
                            
                            echo '<h3>' . htmlspecialchars($row['name']) . '</h3>';
                            $description = htmlspecialchars($row['description']);
                            if (strlen($description) > 60) {
                                $description = substr($description, 0, 60) . '...';
                            }
                            echo '<p>' . $description . '</p>';
                            echo '<div class="price-arrow"><span class="price">₱' . number_format($row['price'], 2) . '</span></div>';
                            echo '</div></div>';
                        }
                    } else {
                        echo '<p>Check back soon for our specialties!</p>';
                    }
                    ?>
                </div>
            </div>
            <a href="menu.php" class="btn btn-secondary">View Full Menu</a>
        </div>
    </section>

    <section class="our-story-section common-padding">
    <div class="container">
        <div class="section-heading-v2 revealUp">
            <div class="sub-title">A Rich Heritage</div>
            <div class="title-with-lines">
                <div class="line"></div>
                <h2 class="main-title">Our Story</h2>
                <div class="line"></div>
            </div>
        </div>
            <div class="story-content">
                <div class="story-image"><img src="images/story.jpg" alt="Our Story Image"></div>
                <div class="story-text">
                    <p>Founded in 2024, Tavern Publico was born from a passion for bringing together exceptional craft food and drinks in a welcoming environment. Our chefs use locally-sourced ingredients to create memorable dishes that honor tradition while embracing innovation.</p>
                    <p>Every visit to Tavern Publico is an opportunity to experience the warmth of our hospitality and the quality of our cuisine.</p>
                    <a href="about.php" class="btn btn-outline-dark">Learn More About Us</a>
                </div>
            </div>
        </div>
    </section>

    <section class="upcoming-events-section common-padding">
        <div class="container">
            <div class="section-heading-v2 revealUp">
                <div class="sub-title">Don't Miss Out</div>
                <div class="title-with-lines">
                    <div class="line"></div>
                    <h2 class="main-title">Upcoming Events</h2>
                    <div class="line"></div>
                </div>
            </div>
            <div class="slider-container" id="eventsSliderContainer">
                <div class="slider-wrapper">
                    <?php
                    $sql_events = "SELECT * FROM events WHERE deleted_at IS NULL ORDER BY date DESC LIMIT 3";
                    $result_events = $conn->query($sql_events);
                    if ($result_events && $result_events->num_rows > 0) {
                        while ($row = $result_events->fetch_assoc()) {
                            $start_date_formatted = date("l, F j, Y", strtotime($row['date']));
                            $date_display = $start_date_formatted;
                            if (!empty($row['end_date'])) {
                                $end_date_formatted = date("l, F j, Y", strtotime($row['end_date']));
                                if ($start_date_formatted !== $end_date_formatted) {
                                    $date_display .= " - " . $end_date_formatted;
                                }
                            }
                            echo '<div class="slider-item">
                                    <div class="event-card">
                                        <img src="' . htmlspecialchars($row['image']) . '" alt="' . htmlspecialchars($row['title']) . '">
                                        <span class="event-date">' . htmlspecialchars($date_display) . '</span>
                                        <h3>' . htmlspecialchars($row['title']) . '</h3>
                                        <p>' . substr(htmlspecialchars($row['description']), 0, 100) . '...</p>
                                    </div>
                                  </div>';
                        }
                    } else {
                         echo '<p>No upcoming events scheduled yet. Stay tuned!</p>';
                    }
                    ?>
                </div>
            </div>
            <a href="events.php" class="btn btn-secondary">View All Events</a>
        </div>
    </section>

    <section class="guest-testimonials-section common-padding">
        <div class="container">
            <h2 class="revealUp">What Our Guests Say</h2>
            <div class="slider-container" id="testimonialsSliderContainer">
                <div class="slider-wrapper">
                    <?php if (!empty($featured_testimonials)): ?>
                        <?php foreach ($featured_testimonials as $testimonial): ?>
                            <div class="slider-item"><div class="testimonial-card">
                                <div class="stars"><?php echo str_repeat('★', $testimonial['rating']) . str_repeat('☆', 5 - $testimonial['rating']); ?></div>
                                <p>"<?php echo htmlspecialchars($testimonial['comment']); ?>"</p>
                                <div class="guest-info">
                                    <?php $avatar_path = !empty($testimonial['avatar']) && file_exists($testimonial['avatar']) ? $testimonial['avatar'] : 'images/default_avatar.png'; ?>
                                    <img src="<?php echo htmlspecialchars($avatar_path); ?>" alt="<?php echo htmlspecialchars($testimonial['username']); ?>">
                                    <div class="guest-details"><span class="guest-name"><?php echo htmlspecialchars($testimonial['username']); ?></span></div>
                                </div>
                            </div></div>
                        <?php endforeach; ?>
                     <?php else: ?>
                        <p>Be the first to leave a review!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="call-to-action-section">
        <div class="container">
            <div class="cta-content"><h2>Ready to Experience Tavern Publico?</h2><p>Join us for an unforgettable dining experience. Whether you're planning a romantic dinner, family gathering, or just want to enjoy great food and drinks, we're here to serve you.</p><div class="cta-buttons"><a href="reserve.php" class="btn btn-outline-white">Reserve a Table</a><a href="menu.php" class="btn btn-outline-white">View Our Menu</a><a href="contact.php" class="btn btn-outline-white">Contact Us</a></div></div>
        </div>
    </section>

    <?php if ($show_modal_on_load && !empty($unrated_reservation)): ?>
    <div id="ratingModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <div class="modal-form-container">
                <h2 class="modal-title">Rate Your Recent Visit</h2>
                <form id="ratingForm" class="modal-form">
                    <div class="form-group" style="text-align: left; font-size: 1.1em; margin-bottom: 20px;">
                        <p>Please rate your visit on: <strong><?php echo htmlspecialchars($unrated_reservation['res_date']); ?></strong></p>
                        <input type="hidden" name="reservation_id" value="<?php echo $unrated_reservation['reservation_id']; ?>">
                    </div>
                    <div class="form-group" style="text-align: left;">
                        <label>Your Rating:</label>
                        <div class="star-rating">
                            <input type="radio" id="5-stars" name="rating" value="5" required /><label for="5-stars" class="star">★</label>
                            <input type="radio" id="4-stars" name="rating" value="4" /><label for="4-stars" class="star">★</label>
                            <input type="radio" id="3-stars" name="rating" value="3" /><label for="3-stars" class="star">★</label>
                            <input type="radio" id="2-stars" name="rating" value="2" /><label for="2-stars" class="star">★</label>
                            <input type="radio" id="1-star" name="rating" value="1" /><label for="1-star" class="star">★</label>
                        </div>
                    </div>
                    <div class="form-group" style="text-align: left;">
                        <label for="comment">Leave a comment:</label>
                        <textarea name="comment" id="comment" rows="4" placeholder="Tell us about your experience..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 1em;" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary modal-btn">Submit Rating</button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php include 'partials/footer.php'; ?>
    <?php include 'partials/Signin-Signup.php'; ?>
    <script src="JS/theme-switcher.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {

            // --- MODIFICATION START: Added Alert Modal Logic ---
            // This will find the alertModal loaded by 'Signin-Signup.php'
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
            // --- MODIFICATION END ---


            function initializeCustomSliders() {
                document.querySelectorAll('.slider-container').forEach(container => {
                    const wrapper = container.querySelector('.slider-wrapper');
                    const slides = Array.from(container.querySelectorAll('.slider-item'));

                    let autoSlideInterval = null;
                    const autoSlideDelay = 4000;
                    const inactivityResetDelay = 8000;
                    let inactivityTimeout = null;
                    const autoSlideIDs = ['specialtiesSliderContainer', 'eventsSliderContainer', 'testimonialsSliderContainer'];

                    if (!wrapper || slides.length <= 1) {
                       return;
                    }

                    let currentIndex = 0;
                    const slideCount = slides.length;
                    let touchstartX = 0;
                    let touchendX = 0;

                    function goToSlide(index, smooth = true) {
                        if (window.innerWidth > 768) {
                            wrapper.style.transform = 'translateX(0)';
                            wrapper.style.transition = 'none';
                            stopAutoSlide();
                            return;
                        }

                        currentIndex = Math.max(0, Math.min(index, slideCount - 1));

                        const scrollAmount = slides[currentIndex].offsetLeft;
                        wrapper.style.transition = smooth ? 'transform 0.4s ease-in-out' : 'none';
                        wrapper.style.transform = `translateX(-${scrollAmount}px)`;
                    }

                    function startAutoSlide() {
                        if (!autoSlideIDs.includes(container.id) || window.innerWidth > 768) {
                            return;
                        }
                        stopAutoSlide();
                        autoSlideInterval = setInterval(() => {
                            let nextIndex = (currentIndex + 1) % slideCount;
                            goToSlide(nextIndex);
                        }, autoSlideDelay);
                    }

                    function stopAutoSlide() {
                        clearInterval(autoSlideInterval);
                        clearTimeout(inactivityTimeout);
                    }

                    function resetAutoSlideAfterInactivity() {
                        if (!autoSlideIDs.includes(container.id) || window.innerWidth > 768) {
                            return;
                        }
                        clearTimeout(inactivityTimeout);
                        inactivityTimeout = setTimeout(() => {
                             startAutoSlide();
                        }, inactivityResetDelay);
                    }

                    function handleGesture() {
                        const swipeThreshold = 50;
                        if (touchendX < touchstartX - swipeThreshold) {
                            goToSlide(currentIndex + 1);
                            stopAutoSlide();
                            resetAutoSlideAfterInactivity();
                        }
                        if (touchendX > touchstartX + swipeThreshold) {
                            goToSlide(currentIndex - 1);
                             stopAutoSlide();
                             resetAutoSlideAfterInactivity();
                        }
                    }

                    wrapper.addEventListener('touchstart', e => {
                        touchstartX = e.changedTouches[0].screenX;
                        stopAutoSlide();
                    }, { passive: true });

                    wrapper.addEventListener('touchend', e => {
                        touchendX = e.changedTouches[0].screenX;
                        handleGesture();
                    });

                    window.addEventListener('resize', () => {
                        goToSlide(currentIndex, false);
                        if (autoSlideIDs.includes(container.id)) {
                             if (window.innerWidth <= 768) {
                                startAutoSlide();
                            } else {
                                stopAutoSlide();
                            }
                        }
                    });

                    goToSlide(0, false);
                    startAutoSlide();
                });
            }

            initializeCustomSliders();

            const slides = document.querySelectorAll(".slideshow-container .mySlides");
            const dots = document.querySelectorAll(".slideshow-container .dot");
            let slideIndex = 0;
            let slideInterval;

            if (slides.length > 1) {
                function moveToSlide(n) {
                    clearInterval(slideInterval);
                    const oldVideo = slides[slideIndex]?.querySelector("video.hero-bg-video");
                    if (oldVideo) {
                        oldVideo.pause();
                        oldVideo.onended = null;
                    }

                    slides.forEach(slide => slide.style.display = "none");
                    dots.forEach(dot => dot.classList.remove("active"));

                    slideIndex = n >= slides.length ? 0 : (n < 0 ? slides.length - 1 : n);

                    const currentSlide = slides[slideIndex];
                    if(dots[slideIndex]) dots[slideIndex].classList.add("active");
                    currentSlide.style.display = "block";

                    const newVideo = currentSlide.querySelector("video.hero-bg-video");

                    if (newVideo) {
                        newVideo.currentTime = 0;
                        newVideo.play().catch(error => console.error("Video autoplay failed.", error));
                        newVideo.onended = () => moveToSlide(slideIndex + 1);
                    } else {
                        slideInterval = setInterval(() => moveToSlide(slideIndex + 1), 5000);
                    }
                }

                window.currentSlide = (n) => moveToSlide(n - 1);

                moveToSlide(0);
            } else if (slides.length === 1) {
                slides[0].style.display = 'block';
            }

            const ratingModal = document.getElementById('ratingModal');
            const showModalOnLoad = <?php echo json_encode($show_modal_on_load); ?>;

            if (ratingModal && showModalOnLoad) {
                const closeBtn = ratingModal.querySelector('.close-button');
                const ratingForm = document.getElementById('ratingForm');

                const closeRatingModal = () => {
                    ratingModal.style.display = 'none';
                };

                closeBtn.addEventListener('click', closeRatingModal);

                ratingForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    fetch('submit_rating.php', { method: 'POST', body: formData })
                        .then(response => response.json())
                        .then(data => {
                            
                            // --- MODIFICATION START: Replaced alert() ---
                            // Now that showAlert() is defined in this scope, it will be used.
                            showAlert(data.success ? 'Success!' : 'Error', data.message);
                            // --- MODIFICATION END ---

                            if (data.success) {
                                closeRatingModal();
                            }
                        });
                });

                ratingModal.style.display = 'flex';
            }

            const revealElements = document.querySelectorAll('.revealUp');

            const revealObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('active');
                    }
                });
            }, {
                threshold: 0.1
            });

            revealElements.forEach(el => {
                revealObserver.observe(el);
            });

        });
    </script>
</body>

</html>