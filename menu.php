<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tavern Publico - Menu</title>
    <link rel="stylesheet" href="CSS/main.css">
    <link rel="stylesheet" href="CSS/dark-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* --- Styles for Sticky Menu Header --- */
        .menu-header {
            position: -webkit-sticky; /* Safari */
            position: sticky;
            /* !!! VERIFY THIS VALUE: Should match your main header height + body padding-top !!! */
            /* Usually 90px (header) + 4px (body padding?) = 94px, but verify */
            top: 94px;
            z-index: 990; /* Below main header (1000) but above content */
            background-color: #fff; /* Essential */
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08); /* Keep shadow when sticky */
            width: 100%; /* Ensure full width within container */
            /* Keep original layout properties */
            margin-bottom: 30px;
            border-radius: 10px;
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        /* Dark theme override for sticky header background */
        body.dark-theme .menu-header {
             background-color: #1e1e1e;
             box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        /* --- End Sticky Menu Header Styles --- */

        /* --- ! IMPORTANT: Attempt to fix conflicting overflow --- */
        /* If the header STILL doesn't stick, try uncommenting the next line */
        /* main, .menu-section, .container { overflow: visible !important; } */


        /* --- INLINED & ADJUSTED STYLES FOR MENU PAGE (Existing styles below) --- */
        .menu-section { padding: 40px 0; background-color: #f8f8f8; }

        .section-heading-v2 { margin-bottom: 40px; }

        .category-buttons-container { position: relative; width: 100%; }
        .category-buttons { display: flex; flex-wrap: wrap; gap: 8px; }

        .category-btn { background-color: #f0f0f0; color: #555; border: none; padding: 8px 18px; border-radius: 25px; cursor: pointer; font-size: 0.9em; font-weight: 500; transition: all 0.3s ease; display: flex; align-items: center; gap: 8px; }

        .category-btn .btn-text { margin-left: 6px; }
        .category-btn:hover { background-color: #e0e0e0; }
        .category-btn.active { background-color: #FFD700; color: #333; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .category-btn.active i { color: #333; }
        .search-sort { display: flex; align-items: center; gap: 20px; flex-wrap: wrap; }
        .search-bar { position: relative; }
        .search-bar input { padding: 10px 15px 10px 40px; border: 1px solid #ddd; border-radius: 25px; font-size: 0.95em; width: 200px; transition: border-color 0.3s ease; }
        .search-bar input:focus { border-color: #FFD700; outline: none; }
        .search-bar i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #999; }
        .sort-by { display: flex; align-items: center; gap: 10px; }
        .sort-by label { font-weight: 500; color: #555; }
        .sort-by select { padding: 8px 15px; border: 1px solid #ddd; border-radius: 25px; background-color: #fff; font-size: 0.95em; cursor: pointer; }
        .menu-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; justify-content: center; }
        .menu-item-card { background-color: #fff; border-radius: 15px; box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08); overflow: hidden; text-align: left; transition: all 0.3s ease; display: flex; flex-direction: column; height: 380px; }
        .menu-item-card:hover { transform: translateY(-8px); box-shadow: 0 12px 25px rgba(0, 0, 0, 0.15); }
        
        .menu-item-card img {
            width: 100%;
            height: 200px;
            object-fit: cover; /* This fills the box and crops, ensuring no empty space */
        }

        .menu-item-card h3 { font-family: 'Mada', sans-serif; font-size: 1.6em; margin: 20px 20px 10px; color: #222; font-weight: 600; line-height: 1.3; }
        .item-price-add { display: flex; justify-content: space-between; align-items: center; padding: 0 20px 20px; margin-top: auto; }
        .item-price-add .price { font-family: 'Mada', sans-serif; font-size: 1.5em; font-weight: 700; color: #333; }
        .view-details-btn { background-color: #FFD700; color: #333; border: none; border-radius: 50%; width: 45px; height: 45px; display: flex; justify-content: center; align-items: center; font-size: 1.4rem; cursor: pointer; transition: all 0.2s ease-in-out; box-shadow: 0 2px 5px rgba(0,0,0,0.15); }
        .view-details-btn i { font-weight: 600; transition: transform 0.2s ease-in-out; }
        .view-details-btn:hover { background-color: #e6c200; transform: scale(1.1); box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        .view-details-btn:active { transform: scale(0.95); }
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); justify-content: center; align-items: center; }
        .item-modal-content { background-color: #fff; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,.2); width: 90%; max-width: 500px !important; padding: 0 !important; text-align: left; position: relative; animation: fadeIn .4s; }
        @keyframes fadeIn { from { opacity: 0; transform: scale(.95) } to { opacity: 1; transform: scale(1) } }
        .item-modal-content .close-button { position: absolute; top: 10px; right: 20px; color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; }
        
        .item-modal-content img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            cursor: default; /* --- MODIFICATION: Changed from 'zoom-in' to 'default' --- */
        }

        .modal-item-details { padding: 25px; }
        .modal-item-details h2 { font-size: 2em; margin-top: 0; margin-bottom: 0; text-align: left; color: #222; line-height: 1.2; }
        .modal-item-details p { font-size: 1.1em; color: #555; line-height: 1.7; margin-bottom: 20px; }
        .modal-price-tag { font-size: 1.8em; font-weight: 700; color: #333; text-align: right; }

        .swipe-indicator { display: none; }

        .image-viewer-modal {
            background-color: rgba(0,0,0,0.85); /* Darker overlay */
            z-index: 2001; /* Above the first modal */
        }
        .image-viewer-content {
            background-color: transparent;
            box-shadow: none;
            max-width: 90%;
            max-height: 90vh;
            width: auto;
            height: auto;
            padding: 0 !important;
            border-radius: 5px;
        }
        .image-viewer-close {
            color: #f1f1f1;
            font-size: 40px;
            top: 15px;
            right: 35px;
            text-shadow: 0 0 8px rgba(0,0,0,0.7);
        }

        @media (max-width: 1024px) {
            .menu-header { flex-direction: column; align-items: flex-start; }
            .search-sort { width: 100%; justify-content: space-between; }
            .search-bar { flex-grow: 1; }
            .search-bar input { width: 100%; }
        }
        
        @media (max-width: 768px) {
            .menu-section {
                padding-top: 25px; /* Was 40px */
            }
            
            .section-heading-v2 {
                margin-bottom: 15px; /* Was 20px, reduced further */
            }

            .menu-header { padding: 15px; margin-bottom: 30px; flex-direction: column; align-items: stretch; top: 94px; } /* Ensure top matches sticky value */
            .category-buttons-container { position: relative; width: 100%; }
            .category-buttons {
                display: flex;
                overflow-x: auto;
                flex-wrap: nowrap;
                padding-bottom: 15px;
                -ms-overflow-style: none;
                scrollbar-width: none;
            }
            .category-buttons::-webkit-scrollbar { display: none; }
            .category-btn { flex-shrink: 0; }
            
            /* --- MODIFIED SEARCH/SORT --- */
            .search-sort { 
                flex-direction: row; /* Was column */
                align-items: center; /* Was stretch */
                gap: 15px; 
                width: 100%; /* Ensure it takes full width */
                justify-content: space-between; /* Puts sort on the right */
            }
            .search-bar {
                flex-grow: 1; /* Make search bar take available space */
            }
            .search-bar input {
                width: 100%; /* Make input fill the search-bar container */
            }
            .sort-by {
                flex-shrink: 0; /* Don't let the sort box shrink */
            }
            /* --- END MODIFIED --- */
            
            .menu-grid { 
                grid-template-columns: repeat(2, 1fr); /* Force 2 columns */
                gap: 20px; 
            }

            .menu-item-card {
                height: auto; /* Remove fixed height */
            }
            .menu-item-card img {
                height: 120px; /* Make image shorter */
            }
            .menu-item-card h3 {
                font-size: 1.1em; /* Smaller font */
                margin: 10px 10px 5px; /* Tighter margins */
            }
            .item-price-add {
                padding: 0 10px 10px; /* Tighter padding */
            }
            .item-price-add .price {
                font-size: 1.1em; /* Smaller price */
            }
            .view-details-btn {
                width: 36px; /* Smaller button */
                height: 36px;
                font-size: 1.1rem;
            }

            .swipe-indicator { display: flex; align-items: center; position: absolute; top: 50%; right: 0; transform: translateY(-50%); background-color: rgba(0,0,0,0.7); color: #fff; padding: 8px 15px; border-radius: 20px; font-size: 0.85em; z-index: 10; pointer-events: none; opacity: 1; transition: opacity 0.5s ease; }
            .swipe-indicator.hide { opacity: 0; }
        }

        .menu-item-card {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.5s ease-out, transform 0.5s ease-out;
            visibility: hidden;
        }
        .menu-item-card.is-visible {
            opacity: 1;
            transform: translateY(0);
            visibility: visible;
        }

        /* --- NEW: Scroll to Top Button Styles --- */
        #scrollTopBtn {
            display: none; /* Hidden by default */
            position: fixed;
            bottom: 25px;
            left: 25px; /* Positioned on the left */
            z-index: 1001;
            border: none;
            outline: none;
            background-color: #FFD700; /* Gold color to match theme */
            color: #1a1a1a;
            cursor: pointer;
            padding: 0;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 22px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            transition: background-color 0.3s, opacity 0.3s, transform 0.2s;
            display: none; /* Re-set to none */
            justify-content: center; /* Added for icon centering */
            align-items: center; /* Added for icon centering */
        }

        #scrollTopBtn:hover {
            background-color: #e6c200;
            transform: scale(1.05);
        }

        /* Dark theme style for the button */
        body.dark-theme #scrollTopBtn {
            background-color: #FFD700;
            color: #1a1a1a;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }
        body.dark-theme #scrollTopBtn:hover {
            background-color: #e6c200;
        }
        /* --- END: Scroll to Top Button Styles --- */

    </style>
</head>
<body>

    <?php
    include 'partials/header.php';
    include 'config.php';
    ?>

    <main>
        <section class="menu-section common-padding">
            <div class="container">
                <div class="section-heading-v2">
                    <div class="sub-title">Explore Our</div>
                    <div class="title-with-lines">
                        <div class="line"></div>
                        <h2 class="main-title">Delicious Menu</h2>
                        <div class="line"></div>
                    </div>
                </div>
                <div class="menu-header"> 
                    <div class="category-buttons-container">
                        <div class="category-buttons">
                            <button class="category-btn active" data-category="All"><i class="fas fa-list"></i><span class="btn-text">All Items</span></button>
                            <button class="category-btn" data-category="Specialty"><i class="fas fa-utensils"></i><span class="btn-text">Specialty</span></button>
                            <button class="category-btn" data-category="Appetizer"><i class="fas fa-concierge-bell"></i><span class="btn-text">Appetizer</span></button>
                            <button class="category-btn" data-category="Breakfast"><i class="fas fa-egg"></i><span class="btn-text">All Day Breakfast</span></button>
                            <button class="category-btn" data-category="Lunch"><i class="fas fa-drumstick-bite"></i><span class="btn-text">Ala Carte/For Sharing</span></button>
                            <button class="category-btn" data-category="Sizzlers"><i class="fas fa-fire-alt"></i><span class="btn-text">Sizzling Plates</span></button>
                            <button class="category-btn" data-category="Coffee"><i class="fas fa-coffee"></i><span class="btn-text">Cafe Drinks</span></button>
                            <button class="category-btn" data-category="Non-Coffee"><i class="fas fa-mug-hot"></i><span class="btn-text">Non-Coffee</span></button>
                            <button class="category-btn" data-category="Cool Creations"><i class="fas fa-blender"></i><span class="btn-text">Frappe</span></button>
                            <button class="category-btn" data-category="Cakes"><i class="fas fa-birthday-cake"></i><span class="btn-text">Cakes</span></button>
                        </div>
                        <div class="swipe-indicator">Swipe <i class="fas fa-hand-pointer"></i></div>
                    </div>
                    <div class="search-sort">
                        <div class="search-bar">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" placeholder="Search menu...">
                        </div>
                        <div class="sort-by">
                            <label for="sort-select">Sort by:</label>
                            <select id="sort-select">
                                <option value="popular">Popular</option>
                                <option value="price-low-high">Price (Low to High)</option>
                                <option value="price-high-low">Price (High to Low)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="menu-grid">
                    <?php
                     if (!isset($conn) || !$conn || $conn->connect_error) { // Check if connection exists and is valid
                         include 'config.php'; // Re-include config to get $conn
                     }

                    $sql = "SELECT * FROM menu WHERE deleted_at IS NULL ORDER BY category, name";
                    $result = $conn->query($sql);

                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo '<div class="menu-item-card"
                                    data-name="' . htmlspecialchars($row['name'], ENT_QUOTES) . '"
                                    data-image="' . htmlspecialchars($row['image']) . '"
                                    data-price="₱' . number_format($row['price'], 2) . '"
                                    data-description="' . htmlspecialchars($row['description'], ENT_QUOTES) . '"
                                    data-category="' . htmlspecialchars($row['category']) . '">';

                            echo '  <img src="' . htmlspecialchars($row['image']) . '" alt="' . htmlspecialchars($row['name']) . '">';
                            echo '  <h3>' . htmlspecialchars($row['name']) . '</h3>';
                            echo '  <div class="item-price-add">';
                            echo '    <span class="price">₱' . number_format($row['price'], 2) . '</span>';
                            echo '    <button class="view-details-btn"><i class="fas fa-info-circle"></i></button>';
                            echo '  </div>';
                            echo '</div>';
                        }
                    } else {
                        echo "<p>No menu items found.</p>";
                    }
                    // Consider closing connection if it's the last use on the page
                    // if (isset($conn)) { $conn->close(); }
                    ?>
                </div>
            </div>
        </section>
    </main>

    <button id="scrollTopBtn" title="Go to top"><i class="fas fa-arrow-up"></i></button>

    <?php
    include 'partials/footer.php';
    include 'partials/Signin-Signup.php';
    // Close connection definitively here if not needed by includes above
     if (isset($conn) && $conn) { $conn->close(); }
    ?>

    <div id="menuItemModal" class="modal">
        <div class="modal-content item-modal-content">
            <span class="close-button">&times;</span>
            <img id="modalItemImage" src="" alt="Menu Item Image">
            <div class="modal-item-details">
                
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                    <h2 id="modalItemName"></h2>
                    <button id="viewFullImageBtn" class="view-details-btn" title="View full image" style="flex-shrink: 0; margin-left: 15px;">
                        <i class="fas fa-search-plus"></i>
                    </button>
                </div>

                <p id="modalItemDescription"></p>
                <div class="modal-price-tag" id="modalItemPrice"></div>
            </div>
        </div>
    </div>

    <div id="imageViewerModal" class="modal image-viewer-modal">
        <span class="close-button image-viewer-close">&times;</span>
        <img class="modal-content image-viewer-content" id="fullScreenImage" alt="Full screen menu item image">
    </div>

    <script src="JS/theme-switcher.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {

            const menuItemModal = document.getElementById('menuItemModal');
            const modalName = document.getElementById('modalItemName');
            const modalImage = document.getElementById('modalItemImage');
            const modalPrice = document.getElementById('modalItemPrice');
            const modalDescription = document.getElementById('modalItemDescription');
            const modalCloseButton = menuItemModal ? menuItemModal.querySelector('.close-button') : null; // Added null check

            document.querySelectorAll('.view-details-btn').forEach(button => {
                button.addEventListener('click', () => {
                    const card = button.closest('.menu-item-card');
                    if (!card || !menuItemModal) return; 

                    modalName.textContent = card.dataset.name;
                    modalImage.src = card.dataset.image;
                    modalPrice.textContent = card.dataset.price;
                    modalDescription.textContent = card.dataset.description;

                    menuItemModal.style.display = 'flex';
                });
            });

            if (modalCloseButton) { 
                modalCloseButton.addEventListener('click', () => {
                    if (menuItemModal) menuItemModal.style.display = 'none';
                });
            }

            // --- MODIFICATION: Removed window.addEventListener for menuItemModal ---
            /*
            window.addEventListener('click', (event) => {
                if (event.target == menuItemModal) {
                   if (menuItemModal) menuItemModal.style.display = 'none';
                }
            });
            */
            // --- END MODIFICATION ---


            // --- Image Viewer Modal Logic ---
            const imageViewerModal = document.getElementById('imageViewerModal');
            const fullScreenImage = document.getElementById('fullScreenImage');
            const imageViewerCloseBtn = imageViewerModal ? imageViewerModal.querySelector('.image-viewer-close') : null;
            const viewFullImageBtn = document.getElementById('viewFullImageBtn');

            function openImageViewer() {
                if(imageViewerModal && fullScreenImage && modalImage) {
                    fullScreenImage.src = modalImage.src;
                    imageViewerModal.style.display = 'flex';
                }
            }

            // --- MODIFICATION: Removed click listener for modalImage ---
            /*
            if (modalImage) {
                modalImage.addEventListener('click', openImageViewer);
            }
            */
            // --- END MODIFICATION ---

            // Click listener for the new "zoom" button (remains the same)
            if (viewFullImageBtn) {
                viewFullImageBtn.addEventListener('click', openImageViewer);
            }

            if (imageViewerCloseBtn) {
                imageViewerCloseBtn.addEventListener('click', () => {
                    if (imageViewerModal) imageViewerModal.style.display = 'none';
                });
            }

            // --- MODIFICATION: Removed window.addEventListener for imageViewerModal ---
            /*
            if (imageViewerModal) {
                imageViewerModal.addEventListener('click', (event) => {
                    if (event.target == imageViewerModal) {
                        imageViewerModal.style.display = 'none';
                    }
                });
            }
            */
            // --- END MODIFICATION ---


            const categoryButtonsContainer = document.querySelector('.category-buttons');
            const swipeIndicator = document.querySelector('.swipe-indicator');

            if (categoryButtonsContainer && swipeIndicator) {
                if (categoryButtonsContainer.scrollWidth > categoryButtonsContainer.clientWidth) {
                    swipeIndicator.style.display = 'flex';
                } else {
                    swipeIndicator.style.display = 'none';
                }
                categoryButtonsContainer.addEventListener('scroll', () => {
                    swipeIndicator.classList.add('hide');
                }, { once: true });
            }

            const categoryButtons = document.querySelectorAll('.category-btn');
            const searchInput = document.getElementById('searchInput');
            const sortBySelect = document.getElementById('sort-select');
            const menuGrid = document.querySelector('.menu-grid');
            
            const allMenuItems = Array.from(document.querySelectorAll('.menu-item-card'));

            const filterAndSort = () => {
                const activeCategoryBtn = document.querySelector('.category-btn.active');
                if (!activeCategoryBtn || !searchInput || !sortBySelect || !menuGrid) return;
                const activeCategory = activeCategoryBtn.dataset.category;
                const searchTerm = searchInput.value.toLowerCase();
                const sortValue = sortBySelect.value;
                
                let itemsToShow = allMenuItems;

                itemsToShow.forEach(item => {
                    const isVisibleByCategory = activeCategory === 'All' || item.dataset.category === activeCategory;
                    const itemName = item.dataset.name.toLowerCase();
                    const isVisibleBySearch = itemName.includes(searchTerm);
                    item.style.display = (isVisibleByCategory && isVisibleBySearch) ? 'flex' : 'none';
                });

                let visibleItems = itemsToShow.filter(item => item.style.display !== 'none');

                visibleItems.sort((a, b) => {
                    if (sortValue === 'popular') return 0;
                    const priceA = parseFloat(a.dataset.price.replace(/[₱,]/g, ''));
                    const priceB = parseFloat(b.dataset.price.replace(/[₱,]/g, ''));
                    if (sortValue === 'price-low-high') return priceA - priceB;
                    if (sortValue === 'price-high-low') return priceB - priceA;
                    return 0;
                });

                menuGrid.innerHTML = '';
                visibleItems.forEach(item => menuGrid.appendChild(item));
            };

            categoryButtons.forEach(button => {
                button.addEventListener('click', () => {
                    categoryButtons.forEach(btn => btn.classList.remove('active'));
                    button.classList.add('active');
                    filterAndSort();
                });
            });

            if(searchInput) searchInput.addEventListener('input', filterAndSort);
            if(sortBySelect) sortBySelect.addEventListener('change', filterAndSort);

            const menuItems = document.querySelectorAll('.menu-item-card');

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

            menuItems.forEach((item, index) => {
                item.style.transitionDelay = `${index * 50}ms`;
                observer.observe(item);
            });

            filterAndSort(); 

            
            // --- NEW: Scroll to Top Button JavaScript ---
            const scrollTopBtn = document.getElementById('scrollTopBtn');

            if (scrollTopBtn) {
                // Show or hide the button based on scroll position
                window.onscroll = function() {
                    scrollFunction();
                };

                function scrollFunction() {
                    if (window.scrollY > 200 || document.documentElement.scrollTop > 200) {
                        scrollTopBtn.style.display = "flex"; // Use flex to center icon
                    } else {
                        scrollTopBtn.style.display = "none";
                    }
                }

                // Scroll to top when clicked
                scrollTopBtn.addEventListener('click', () => {
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                });
            }
            // --- END: Scroll to Top Button JavaScript ---

        });
    </script>
</body>
</html>