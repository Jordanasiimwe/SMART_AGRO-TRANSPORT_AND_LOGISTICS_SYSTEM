<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="SmartAgro-Link System is a digital platform designed to connect farmers directly with vendors in Nakawa Market. The system enables farmers to showcase available agricultural products, while vendors can easily search, order, and communicate with suppliers in real time. By reducing intermediaries, SmartAgro-Link System improves market access, enhances transparency, promotes fair pricing, and streamlines the agricultural supply chain.">
    <meta name="keywords" content="SmartAgro-Link, agriculture, farmers, vendors, Nakawa Market, agricultural products, supply chain, fair pricing">
    <meta property="og:title" content="Contact Us - Smart AgroLink System">
    <meta property="og:description" content="SmartAgro-Link System is a digital platform designed to connect farmers directly with vendors in Nakawa Market. The system enables farmers to showcase available agricultural products, while vendors can easily search, order, and communicate with suppliers in real time. By reducing intermediaries, SmartAgro-Link System improves market access, enhances transparency, promotes fair pricing, and streamlines the agricultural supply chain.">
    <meta property="og:image" content="/images/smart.jpg">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Contact Us - Smart AgroLink System">
    <meta name="twitter:description" content="SmartAgro-Link System is a digital platform designed to connect farmers directly with vendors in Nakawa Market. The system enables farmers to showcase available agricultural products, while vendors can easily search, order, and communicate with suppliers in real time. By reducing intermediaries, SmartAgro-Link System improves market access, enhances transparency, promotes fair pricing, and streamlines the agricultural supply chain.">
    <meta name="twitter:image" content="/images/smart.jpg">
    <title>Contact Us - Smart AgroLink System</title>
    
    <link rel="stylesheet" href="/style.css">
    <link rel="icon" href="/images/smart.jpg" type="image/jpeg">
</head>
<body>

<div class="container">
    <div class="page-header">
        <h1>Contact Us</h1>
        <a href="/" class="button-back">&larr; Back to Home</a>
    </div>
    
    <div class="static-content"  style="color:black;">
        <h2 style="color: #558B2F;">Smart AgroLink <span style="color: orange;">System</span></h2>
        <p class="tagline">Connecting Farmers to Markets</p>

        <div class="welcome-message">
            <h3>Contact Us</h3>
            <p>Have an inquiry or feedback? Let's connect.</p>
            
            <p><strong>Email:</strong> <a href="mailto:admin@smartagrolink.com">admin@smartagrolink.com</a></p>
            <p><strong>Phone:</strong> 📞 <a href="tel:+256761746776">+256 761746776</a></p>
            
            <p style="margin-top: 1.5rem;">We are located at 123 Agri-Lane Nakawa Market, Kampala, Uganda for more inquiries.</p>
        </div>
        
        <h3>Our Location</h3>
        <p>Visit our headquarters for support.</p>
        
        <p class="commitment">





            We're committed to great produce, direct connections, and great service—an experience that will make your time with us fruitful. All visuals are for representation purposes only. Prices are quoted in Uganda Shillings and are determined by the farmers.
        </p>
    </div>
</div>

<style>

.container { color: white; text-shadow: 1px 1px 2px black; background-color: rgba(220, 233, 221, 0.85); /* Semi-transparent Primary Green */}
.static-content { text-align: center; }
.static-content h2 { font-size: 2.5rem; color: white; margin-bottom: 0.5rem; }
.tagline { font-size: 1.2rem; font-style: italic; color: white; margin-bottom: 2rem; }
.welcome-message { margin: 2rem 0; padding: 1.5rem; border-radius: var(--border-radius); background-color: rgba(255,255,255,0.1); backdrop-filter: blur(5px); }
.commitment { font-size: 0.9rem; color: #777; margin-top: 2rem; }
body {
    background-image: url('/images/market.jpg');
    background-size: cover;
    background-position: center;
}

</style>

<?php require_once __DIR__ . '/public_footer.php'; ?>