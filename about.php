<?php 
session_start(); 
include 'config.php'; 
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tavern Publico - About Us</title>
    <link rel="stylesheet" href="CSS/main.css">
    <link rel="stylesheet" href="CSS/dark-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* UI Adjustments for a more compact layout */
        .our-story-section, .team-section { padding: 40px 0; }
        .section-heading-v2 { margin-bottom: 40px; }

        .team-section { background-color: #fefefe; }
        .team-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; justify-items: center; }
        .team-member { background-color: #fff; border-radius: 10px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08); text-align: center; padding: 30px; transition: transform 0.3s ease, box-shadow 0.3s ease; max-width: 350px; width: 100%; }
        .team-member:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12); }
        .team-member img { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; margin-bottom: 20px; border: 4px solid #f0f0f0; }
        .team-member h3 { font-family: 'Mada', sans-serif; font-size: 1.5em; margin-bottom: 8px; color: #222; font-weight: 600; }
        .team-member p { font-family: 'Mada', sans-serif; font-size: 1em; color: #555; line-height: 1.6; }
        .team-member .team-bio { font-size: 0.95em; color: #777; margin-top: 15px; }

        /* Animation Styles */
        .story-content, .team-member {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.6s ease-out, transform 0.6s ease-out;
            visibility: hidden;
        }
        .story-content.is-visible, .team-member.is-visible {
            opacity: 1;
            transform: translateY(0);
            visibility: visible;
        }
    </style>
</head>

<body>

    <?php include 'partials/header.php'; ?>

    <main>
        <section class="our-story-section common-padding">
            <div class="container">
                <div class="section-heading-v2">
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
                        <p>Founded in 2024, Tavern Publico was born from a passion for bringing together exceptional craft food and drinks in a welcoming environment...</p>
                        <p>Every visit to Tavern Publico is an opportunity to experience the warmth of our hospitality and the quality of our cuisine...</p>
                        <p>From our humble beginnings, we've grown into a beloved local spot, known for our cozy ambiance and dedication to culinary excellence...</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="team-section common-padding">
            <div class="container">
                <div class="section-heading-v2">
                    <div class="sub-title">Get to Know Us</div>
                    <div class="title-with-lines">
                        <div class="line"></div>
                        <h2 class="main-title">Meet Our Team</h2>
                        <div class="line"></div>
                    </div>
                </div>
                <div class="team-grid">
                    <?php
                    $sql = "SELECT * FROM team WHERE deleted_at IS NULL ORDER BY id ASC";
                    $result = $conn->query($sql);

                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo '<div class="team-member">';
                            echo '    <img src="' . htmlspecialchars($row['image']) . '" alt="' . htmlspecialchars($row['name']) . '">';
                            echo '    <h3>' . htmlspecialchars($row['name']) . '</h3>';
                            echo '    <p>' . htmlspecialchars($row['title']) . '</p>';
                            echo '    <p class="team-bio">' . htmlspecialchars($row['bio']) . '</p>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p>Our team information is currently being updated. Please check back soon!</p>';
                    }
                    ?>
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
            // --- ANIMATION ON SCROLL ---
            const animatedElements = document.querySelectorAll('.story-content, .team-member');

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1
            });

            animatedElements.forEach((element, index) => {
                // Apply a staggered delay only to team members
                if (element.classList.contains('team-member')) {
                    element.style.transitionDelay = `${index * 100}ms`;
                }
                observer.observe(element);
            });
        });
    </script>
</body>

</html>