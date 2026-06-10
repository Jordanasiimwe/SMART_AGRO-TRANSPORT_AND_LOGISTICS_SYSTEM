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
    <meta property="og:title" content="Our Address - Smart AgroLink System">
    <meta property="og:description" content="SmartAgro-Link System is a digital platform designed to connect farmers directly with vendors in Nakawa Market. The system enables farmers to showcase available agricultural products, while vendors can easily search, order, and communicate with suppliers in real time. By reducing intermediaries, SmartAgro-Link System improves market access, enhances transparency, promotes fair pricing, and streamlines the agricultural supply chain.">
    <meta property="og:image" content="/images/smart.jpg">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Our Address - Smart AgroLink System">
    <meta name="twitter:description" content="SmartAgro-Link System is a digital platform designed to connect farmers directly with vendors in Nakawa Market. The system enables farmers to showcase available agricultural products, while vendors can easily search, order, and communicate with suppliers in real time. By reducing intermediaries, SmartAgro-Link System improves market access, enhances transparency, promotes fair pricing, and streamlines the agricultural supply chain.">
    <meta name="twitter:image" content="/images/smart.jpg">
    <title>Our Address - Smart AgroLink System</title>
    <link rel="stylesheet" href="/style.css">
    <link rel="icon" href="/images/smart.jpg" type="image/jpeg">
</head>
<body style="background-image: none;">

<div class="container">
    <div class="page-header">
        <h1>Our Address</h1>
        <a href="/" class="button-back">&larr; Back to Home</a>
    </div>
    
    <div class="static-content">
        <h3>Smart AgroLink System HQ</h3>
        <p>123 Agri-Lane Nakawa Market</p>
        <p>Kampala, Uganda</p>
    </div>
</div>

<style>
.static-content { padding-top: 1rem; font-size: 1.1rem; line-height: 1.8; }
</style>

<?php require_once __DIR__ . '/public_footer.php'; ?>