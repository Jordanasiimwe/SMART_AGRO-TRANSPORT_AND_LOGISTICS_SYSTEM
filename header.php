<?php
// Start the session if it's not already started. This makes the file self-contained and safe.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Protect the page: if user is not logged in, redirect to login.
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit();
}

// Update user's last activity time for real-time tracking
require_once __DIR__ . '/User.php';
$trackerUser = new User();
$trackerUser->updateLastActivity($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="SmartAgro-Link System is a digital platform designed to connect farmers directly with vendors in Nakawa Market. The system enables farmers to showcase available agricultural products, while vendors can easily search, order, and communicate with suppliers in real time. By reducing intermediaries, SmartAgro-Link System improves market access, enhances transparency, promotes fair pricing, and streamlines the agricultural supply chain.">
    <meta name="keywords" content="SmartAgro-Link, agriculture, farmers, vendors, Nakawa Market, agricultural products, supply chain, fair pricing">
    <meta property="og:title" content="Smart AgroLink System">
    <meta property="og:description" content="SmartAgro-Link System is a digital platform designed to connect farmers directly with vendors in Nakawa Market. The system enables farmers to showcase available agricultural products, while vendors can easily search, order, and communicate with suppliers in real time. By reducing intermediaries, SmartAgro-Link System improves market access, enhances transparency, promotes fair pricing, and streamlines the agricultural supply chain.">
    <meta property="og:image" content="/images/smart.jpg">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Smart AgroLink System">
    <meta name="twitter:description" content="SmartAgro-Link System is a digital platform designed to connect farmers directly with vendors in Nakawa Market. The system enables farmers to showcase available agricultural products, while vendors can easily search, order, and communicate with suppliers in real time. By reducing intermediaries, SmartAgro-Link System improves market access, enhances transparency, promotes fair pricing, and streamlines the agricultural supply chain.">
    <meta name="twitter:image" content="/images/smart.jpg">
    <title>Smart AgroLink System</title>
    <link rel="icon" href="/images/smart.jpg" type="image/jpeg">
    <script>
        function showEdit(sectionId) {
            // Hide all sections
            document.querySelectorAll('.farmer-info').forEach(section => section.style.display = 'none');

            // Show the selected section
            document.getElementById(sectionId + 'Section').style.display = 'block';
        }
    </script>

    <link rel="stylesheet" href="/style.css?v=<?php echo time(); ?>">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            .container {
                max-width: 100% !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .records-table, .order-card, .search-results-section {
                box-shadow: none !important;
                border: 1px solid #ccc !important;
            }
        }
        /* Google Translate Customization to look 'Native' */
        .goog-te-banner-frame { display: none !important; }
        body { top: 0 !important; }
        .goog-te-gadget-simple {
            background-color: transparent !important;
            border: 1px solid rgba(255,255,255,0.3) !important;
            padding: 4px !important;
            border-radius: 4px !important;
        }
        .goog-te-gadget-simple .goog-te-menu-value span { color: white !important; }
        .goog-te-gadget-simple .goog-te-menu-value span:last-child { display: none !important; } /* Hide 'Select Language' text arrow junk */
    </style>
    <!-- Google Translate Script -->
    <script type="text/javascript">
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: 'en',
                includedLanguages: 'en,lg,sw', // English, Luganda, Swahili
                layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
                autoDisplay: false
            }, 'google_translate_element');
        }
    </script>
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
</head>
<body>
    <nav class="main-nav no-print">
        <div class="nav-container">
            <a href="/dashboard" class="nav-brand">Smart AgroLink System</a>
            <div class="nav-links">
                <!-- Language Switcher -->
                <div id="google_translate_element" style="display:inline-block; margin-right: 15px; vertical-align: middle;"></div>

                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'vendor'): ?>
                    <?php
                        $cart_item_count = 0;
                        if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
                            // We count the number of unique products in the cart
                            $cart_item_count = count($_SESSION['cart']);
                        }
                    ?>
                    <a href="/cart" class="nav-cart">Cart (<?php echo $cart_item_count; ?>)</a>
                    <a href="/browse" class="nav-cart">Continue Shopping</a>
                <?php endif; ?>
                <a href="/logout" class="nav-logout" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
            </div>
        </div>
    </nav>