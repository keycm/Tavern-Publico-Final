<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tavern Publico - Gallery</title>
    <link rel="stylesheet" href="CSS/main.css">
    <link rel="stylesheet" href="CSS/gallery.css">
    <link rel="stylesheet" href="CSS/dark-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* UI Adjustments for a more compact layout */
        .gallery-section { padding: 40px 0; }
        .section-heading-v2 { margin-bottom: 40px; }

        /* Original Gallery Styles */
        .gallery-item { position: relative; }
        .gallery-item-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6); color: #fff; display: flex; justify-content: center; align-items: center; opacity: 0; transition: opacity 0.3s ease; }
        .gallery-item:hover .gallery-item-overlay { opacity: 1; }
        .view-gallery-btn { padding: 10px 20px; background-color: #FFD700; color: #333; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        
        /* Original Modal Styles */
        .modal{display:none;position:fixed;z-index:2000;left:0;top:0;width:100%;height:100%;overflow:auto;background-color:rgba(0,0,0,.7);justify-content:center;align-items:center}
        .item-modal-content{background-color:#fff;border-radius:10px;box-shadow:0 5px 20px rgba(0,0,0,.2);width:90%;max-width:600px!important;padding:0!important;text-align:left;position:relative;animation:fadeIn .4s}
        @keyframes fadeIn{from{opacity:0;transform:scale(.95)}to{opacity:1;transform:scale(1)}}
        .item-modal-content .close-button{position:absolute;top:10px;right:20px;color:#aaa;font-size:35px;font-weight:700;cursor:pointer;z-index:10;color:#fff;text-shadow:0 0 5px #000;}
        .item-modal-content img{width:100%;height:auto;max-height:70vh;object-fit:cover;border-top-left-radius:10px;border-top-right-radius:10px}
        .modal-item-details{padding:25px}
        .modal-item-details p{font-size:1.1em;color:#555;line-height:1.7;margin:0}

        /* Animation styles for gallery items */
        .gallery-item {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.6s ease-out, transform 0.6s ease-out;
            visibility: hidden;
        }
        .gallery-item.is-visible {
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
    <section class="gallery-section common-padding">
        <div class="container">
            <div class="section-heading-v2">
                <div class="sub-title">Our Moments</div>
                <div class="title-with-lines">
                    <div class="line"></div>
                    <h2 class="main-title">Photo Gallery</h2>
                    <div class="line"></div>
                </div>
            </div>
            <div class="image-grid">
                <?php
                $sql = "SELECT * FROM gallery WHERE deleted_at IS NULL ORDER BY id DESC";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo '<div class="gallery-item" 
                                data-image="' . htmlspecialchars($row['image']) . '" 
                                data-description="' . htmlspecialchars($row['description'], ENT_QUOTES) . '">';
                        echo '  <img src="' . htmlspecialchars($row['image']) . '" alt="Tavern Gallery Image">';
                        echo '  <div class="gallery-item-overlay">';
                        echo '      <button class="view-gallery-btn">View Details</button>';
                        echo '  </div>';
                        echo '</div>';
                    }
                } else {
                    echo "<p>No gallery images found.</p>";
                }
                ?>
            </div>
        </div>
    </section>
</main>

<div id="galleryItemModal" class="modal">
    <div class="modal-content item-modal-content">
        <span class="close-button">&times;</span>
        <img id="modalGalleryImage" src="" alt="Gallery Image">
        <div class="modal-item-details">
            <p id="modalGalleryDescription"></p>
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
        const galleryModal = document.getElementById('galleryItemModal');
        const modalImage = document.getElementById('modalGalleryImage');
        const modalDescription = document.getElementById('modalGalleryDescription');
        const closeModalBtn = galleryModal.querySelector('.close-button');

        const closeGalleryModal = () => galleryModal.style.display = 'none';

        document.querySelectorAll('.view-gallery-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                const item = e.target.closest('.gallery-item');
                modalImage.src = item.dataset.image;
                modalDescription.textContent = item.dataset.description;
                galleryModal.style.display = 'flex';
            });
        });

        closeModalBtn.addEventListener('click', closeGalleryModal);
        window.addEventListener('click', (event) => {
            if (event.target == galleryModal) closeGalleryModal();
        });

        // --- ANIMATION ON SCROLL FOR GALLERY ITEMS ---
        const galleryItems = document.querySelectorAll('.gallery-item');

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

        galleryItems.forEach((item, index) => {
            // Apply a staggered delay for a nicer effect
            item.style.transitionDelay = `${index * 70}ms`;
            observer.observe(item);
        });
    });
</script>

</body>
</html>