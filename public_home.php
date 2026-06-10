<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="SmartAgro-Link System is a digital platform designed to connect farmers directly with vendors in Nakawa Market. The system enables farmers to showcase available agricultural products, while vendors can easily search, order, and communicate with suppliers in real time. By reducing intermediaries, SmartAgro-Link System improves market access, enhances transparency, promotes fair pricing, and streamlines the agricultural supply chain.">
    <meta name="keywords" content="SmartAgro-Link, agriculture, farmers, vendors, Nakawa Market, agricultural products, supply chain, fair pricing">
    <meta property="og:title" content="Welcome to Smart AgroLink System">
    <meta property="og:description" content="SmartAgro-Link System is a digital platform designed to connect farmers directly with vendors in Nakawa Market. The system enables farmers to showcase available agricultural products, while vendors can easily search, order, and communicate with suppliers in real time. By reducing intermediaries, SmartAgro-Link System improves market access, enhances transparency, promotes fair pricing, and streamlines the agricultural supply chain.">
    <meta property="og:image" content="/images/smart.jpg">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Welcome to Smart AgroLink System">
    <meta name="twitter:description" content="SmartAgro-Link System is a digital platform designed to connect farmers directly with vendors in Nakawa Market. The system enables farmers to showcase available agricultural products, while vendors can easily search, order, and communicate with suppliers in real time. By reducing intermediaries, SmartAgro-Link System improves market access, enhances transparency, promotes fair pricing, and streamlines the agricultural supply chain.">
    <meta name="twitter:image" content="/images/smart.jpg">
    <title>Welcome to Smart AgroLink System</title>
    <link rel="stylesheet" href="/style.css">
    <link rel="icon" href="/images/smart.jpg" type="image/jpeg">
    <style>
        body.public-home {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-image: none;
            background-color: var(--primary-color);
            color: white;
            text-align: center;
        }

        .home-logo {
            max-width: 200px;
            margin-bottom: 1.5rem;
        }

        .home-nav a {
            color: white;
            text-decoration: none;
            font-size: 1.2rem;
            transition: text-decoration 0.3s;
        }

        .home-nav a:hover {
            text-decoration: underline;
        }

        .login-button {
            display: inline-block;
            padding: 1rem 2.5rem;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            font-size: 1.2rem;
            font-weight: bold;
            border-radius: 50px;
            transition: background-color 0.3s, transform 0.3s;
        }

        .login-button:hover {
            background-color: var(--primary-hover);
            transform: scale(1.05);
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 200px;
            z-index: 1;
            top: 0;
            overflow-x: hidden;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .sidebar a {
            margin-bottom: 10px;
            padding: 16px;
            font-size: 1rem;
            text-decoration: none;
            color: #fff;
            display: block;
        }

        .home-container {
            margin-left: 0;
            padding: 20px;
        }

        footer {
            position: fixed;
            left: 0;
            bottom: 0;
            width: 100%;
            background-color: #000;
            color: white;
            text-align: center;
            padding: 10px;
        }

        @media (max-width: 768px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
            }
        }
        /* Hide Google Top Bar */
        .goog-te-banner-frame { display: none !important; }
        body { top: 0 !important; }
    </style>
    <script type="text/javascript">
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: 'en',
                includedLanguages: 'en,lg,sw',
                layout: google.translate.TranslateElement.InlineLayout.SIMPLE
            }, 'google_translate_element');
        }
    </script>
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
</head>

<body class="public-home">
    <div class="sidebar">
        <div id="google_translate_element" style="padding: 10px; text-align: center;"></div>
        <a href="/about">About Us</a>
        <a href="/contact">Contact</a>
        <a href="/address">Address</a>
        <a href="/feedback">Feedback</a>
        <a href="/login" class="login-button">Login</a>
    </div>
    <div class="home-container">
        <img src="/images/smart.jpg" alt="Smart AgroLink System Logo" class="home-logo" style="border-radius: 50%; box-shadow: 0 0 20px rgba(255,255,255,0.2);">
        <h1 style="font-size: 3rem; margin-bottom: 1rem; text-shadow: 2px 2px 4px #000;">Smart AgroLink System</h1>
        <p style="font-size: 1.5rem; text-shadow: 1px 1px 2px #000;">Connecting Farmers to Markets</p>
    </div>
    <footer>&copy; <?php echo date('Y'); ?> Icarit David, Asiimwe Jordan, Nahigo Racheal, Nagginda Shirat and Tusiime Rhoda | Class of 2026</footer>
</body>
</html>