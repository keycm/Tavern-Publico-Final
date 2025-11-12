<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tavern Publico - Events</title>
    <link rel="stylesheet" href="CSS/main.css">
    <link rel="stylesheet" href="CSS/dark-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* UI Adjustments for a more compact layout */
        .upcoming-events-section { padding: 40px 0; }
        .section-heading-v2 { margin-bottom: 40px; }

        .events-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; }
        .modal{display:none;position:fixed;z-index:2000;left:0;top:0;width:100%;height:100%;overflow:auto;background-color:rgba(0,0,0,.7);justify-content:center;align-items:center}
        .item-modal-content{background-color:#fff;border-radius:10px;box-shadow:0 5px 20px rgba(0,0,0,.2);width:90%;max-width:550px!important;padding:0!important;text-align:left;position:relative;animation:fadeIn .4s}
        @keyframes fadeIn{from{opacity:0;transform:scale(.95)}to{opacity:1;transform:scale(1)}}
        .item-modal-content .close-button{position:absolute;top:10px;right:20px;color:#aaa;font-size:35px;font-weight:700;cursor:pointer;z-index:10;}
        .item-modal-content img{width:100%;height:250px;object-fit:cover;border-top-left-radius:10px;border-top-right-radius:10px}
        .modal-item-details{padding:25px}
        .modal-item-details h2{font-size:2em;margin-top:0;margin-bottom:10px;color:#222}
        .modal-item-details .event-date-modal{font-size:1em;color:#777;margin-bottom:15px;font-weight:bold;}
        .modal-item-details p{font-size:1.1em;color:#555;line-height:1.7;margin:0}
        
        .learn-more-link { background: none !important; border: none !important; padding: 0 !important; margin-top: 15px; font-family: 'Mada', sans-serif; font-size: 0.95em; font-weight: bold; color: #000; text-decoration: none; cursor: pointer; display: inline-block; transition: color 0.3s ease; }
        .learn-more-link:hover { color: #555; text-decoration: underline; }
        .learn-more-container { text-align: right; padding: 0 25px; margin-top: auto; }
        .event-card { display: flex; flex-direction: column; }
        
        /* --- MODIFICATION: Default <p> tag is now visible on desktop --- */
        .event-card p {
            font-size: 0.95em;
            color: #555;
            margin: 0 25px 20px;
            flex-grow: 1;
            overflow: hidden;
            /* Using line-clamp for desktop view */
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            min-height: 4.5em; /* approx 3 lines */
        }
        
        @media (max-width: 992px) { 
            .events-grid { grid-template-columns: repeat(2, 1fr); } 
        }
        
        /* --- MODIFIED RESPONSIVE SECTION --- */
        @media (max-width: 768px) {
            .upcoming-events-section {
                padding-top: 25px; /* Adjust spacing */
            }
            .section-heading-v2 {
                margin-bottom: 20px; /* Adjust spacing */
            }
            .events-grid { 
                grid-template-columns: repeat(2, 1fr); /* 2 columns */
                gap: 20px;
            }
            .event-card img {
                height: 120px; /* Shorter image */
            }
            .event-card h3 {
                font-size: 1.1em; /* Smaller font */
                margin: 10px 10px 5px; /* Tighter margins */
            }
            .event-card .event-date {
                font-size: 0.8em;
                margin: 0 10px 8px;
            }
            
            /* --- MODIFICATION: Hides the <p> tag on mobile --- */
            .event-card p {
                display: none;
            }
            /* --- END MODIFICATION --- */

            .learn-more-container {
                padding: 0 10px 10px; /* Tighter padding */
            }
            .learn-more-link {
                font-size: 0.9em;
                margin-top: 15px; /* Added margin-top for spacing */
            }
        }
        /* --- END MODIFIED SECTION --- */
        
        /* Animation styles for event cards */
        .event-card {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.6s ease-out, transform 0.6s ease-out;
            visibility: hidden;
        }
        .event-card.is-visible {
            opacity: 1;
            transform: translateY(0);
            visibility: visible;
        }
    </style>
</head>
<body>

<?php
include 'partials/header.php';
include 'config.php';
?>

<main>
    <section class="upcoming-events-section common-padding">
        <div class="container">
            <div class="section-heading-v2">
                <div class="sub-title">Don't Miss Out</div>
                <div class="title-with-lines">
                    <div class="line"></div>
                    <h2 class="main-title">Upcoming Events</h2>
                    <div class="line"></div>
                </div>
            </div>
            <div class="events-grid">
                <?php
                $sql = "SELECT * FROM events WHERE deleted_at IS NULL ORDER BY date DESC";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $start_date_formatted = date("d/m/Y", strtotime($row['date']));
                        $date_display = $start_date_formatted;
                        if (!empty($row['end_date'])) {
                            $end_date_formatted = date("d/m/Y", strtotime($row['end_date']));
                             if ($start_date_formatted !== $end_date_formatted) {
                                $date_display .= " - " . $end_date_formatted;
                            }
                        }

                        echo '<div class="event-card" 
                                data-title="' . htmlspecialchars($row['title'], ENT_QUOTES) . '"
                                data-image="' . htmlspecialchars($row['image']) . '"
                                data-date="' . htmlspecialchars($date_display) . '"
                                data-description="' . htmlspecialchars($row['description'], ENT_QUOTES) . '">';
                        echo '  <img src="' . htmlspecialchars($row['image']) . '" alt="' . htmlspecialchars($row['title']) . '">';
                        echo '  <span class="event-date">' . htmlspecialchars($date_display) . '</span>';
                        echo '  <h3>' . htmlspecialchars($row['title']) . '</h3>';
                        
                        // --- MODIFICATION: Description <p> tag is re-added ---
                        echo '  <p>' . htmlspecialchars($row['description']) . '</p>'; 
                        // --- END MODIFICATION ---
                        
                        echo '  <div class="learn-more-container">';
                        echo '      <a href="#" class="learn-more-link view-details-btn">Learn More</a>';
                        echo '  </div>';
                        echo '</div>';
                    }
                } else {
                    echo "<p>No upcoming events found. Please check back later!</p>";
                }
                ?>
            </div>
        </div>
    </section>
</main>

<div id="eventItemModal" class="modal">
    <div class="modal-content item-modal-content">
        <span class="close-button">&times;</span>
        <img id="modalEventImage" src="" alt="Event Image">
        <div class="modal-item-details">
            <h2 id="modalEventTitle"></h2>
            <div id="modalEventDate" class="event-date-modal"></div>
            <p id="modalEventDescription"></p>
        </div>
    </div>
</div>

<?php
include 'partials/footer.php';
include 'partials/Signin-Signup.php';
?>
<script src="JS/main.js"></script>
<script src="JS/theme-switcher.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const eventModal = document.getElementById('eventItemModal');
        const modalImage = document.getElementById('modalEventImage');
        const modalTitle = document.getElementById('modalEventTitle');
        const modalDate = document.getElementById('modalEventDate');
        const modalDescription = document.getElementById('modalEventDescription');
        const closeModalBtn = eventModal.querySelector('.close-button');

        const closeEventModal = () => eventModal.style.display = 'none';

        document.querySelectorAll('.view-details-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault(); 
                const card = e.target.closest('.event-card');
                modalImage.src = card.dataset.image;
                modalTitle.textContent = card.dataset.title;
                modalDate.textContent = card.dataset.date;
                modalDescription.textContent = card.dataset.description;
                eventModal.style.display = 'flex';
            });
        });

        closeModalBtn.addEventListener('click', closeEventModal);
        window.addEventListener('click', (event) => {
            if (event.target == eventModal) closeEventModal();
        });

        // --- ANIMATION ON SCROLL FOR EVENT CARDS ---
        const eventCards = document.querySelectorAll('.event-card');

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1 // Trigger when 10% of the item is visible
        });

        eventCards.forEach((card, index) => {
            // Apply a staggered delay for a nicer effect
            card.style.transitionDelay = `${index * 80}ms`;
            observer.observe(card);
        });
    });
</script>

</body>
</html>