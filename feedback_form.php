<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="SmartAgro-Link System is a digital platform designed to connect farmers directly with vendors in Nakawa Market. The system enables farmers to showcase available agricultural products, while vendors can easily search, order, and communicate with suppliers in real time. By reducing intermediaries, SmartAgro-Link System improves market access, enhances transparency, promotes fair pricing, and streamlines the agricultural supply chain.">
    <meta name="keywords" content="SmartAgro-Link, agriculture, farmers, vendors, Nakawa Market, agricultural products, supply chain, fair pricing">
    <meta property="og:title" content="Feedback - Smart AgroLink System">
    <meta property="og:description" content="SmartAgro-Link System is a digital platform designed to connect farmers directly with vendors in Nakawa Market. The system enables farmers to showcase available agricultural products, while vendors can easily search, order, and communicate with suppliers in real time. By reducing intermediaries, SmartAgro-Link System improves market access, enhances transparency, promotes fair pricing, and streamlines the agricultural supply chain.">
    <meta property="og:image" content="/images/smart.jpg">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Feedback - Smart AgroLink System">
    <meta name="twitter:description" content="SmartAgro-Link System is a digital platform designed to connect farmers directly with vendors in Nakawa Market. The system enables farmers to showcase available agricultural products, while vendors can easily search, order, and communicate with suppliers in real time. By reducing intermediaries, SmartAgro-Link System improves market access, enhances transparency, promotes fair pricing, and streamlines the agricultural supply chain.">
    <meta name="twitter:image" content="/images/smart.jpg">
    <title>Feedback - Smart AgroLink System</title>
    <link rel="stylesheet" href="/style.css">
    <link rel="icon" href="/images/smart.jpg" type="image/jpeg">
</head>
<body style="background-image: none;">

<div class="container">
    <div class="page-header">
   <h1>Feedback</h1>
        <a href="/" class="button-back">&larr; Back to Home</a>
    </div>
    
    <div class="static-content">
        <h2>We Value Your Voice</h2>
        <p class="tagline">Help us make Smart AgroLink System better for everyone.</p>
        
        <div class="welcome-message feedback-form-container">
            <?php if (isset($_GET['success'])): ?>
                <div class="success-message">
                    <strong>Thank you! Your feedback has been submitted successfully.</strong><br>
                    We are going to put it into consideration and make everything easy and enjoyable to all our users.
                </div>
                <script>
                    setTimeout(function() {
                        window.location.href = "/";
                    }, 5000);
                </script>
            <?php else: ?>
                <h3>Share Your Experience</h3>
                <p style="margin-bottom: 1.5rem;">Have a suggestion, compliment, or complaint? Please let us know.</p>

                <form action="/feedback" method="POST">
                <?php csrf_field(); ?>
                <div class="form-group">
                    <label for="message">Your Message</label>
                    <textarea id="message" name="message" rows="6" placeholder="Tell us what you think..." required></textarea>
                </div>

                <button type="submit">Submit Feedback</button>
            </form>
            <?php endif; ?>
        </div>

        <p class="commitment">
            Your feedback is confidential and helps us improve our services. We review every submission carefully.
        </p>
    </div>
</div>

<style>
.static-content { padding-top: 1rem; font-size: 1.1rem; line-height: 1.8; text-align: center; }
.static-content h2 { font-size: 2.5rem; color: var(--primary-color); margin-bottom: 0.5rem; }
.tagline { font-size: 1.2rem; font-style: italic; color: #555; margin-bottom: 2rem; }

.feedback-form-container { 
    margin: 2rem auto; 
    padding: 2rem; 
    background-color: var(--light-gray); 
    border-radius: var(--border-radius); 
    max-width: 600px; /* Limit width for better readability */
    text-align: left; /* Keep form elements left-aligned */
}

.feedback-form-container h3 { font-size: 1.5rem; margin-bottom: 0.5rem; text-align: center; color: var(--dark-text); }
.feedback-form-container p { text-align: center; color: #666; }
.success-message { background-color: #d4edda; color: #155724; padding: 1rem; border-radius: var(--border-radius); margin-bottom: 1.5rem; text-align: center; }
.commitment { font-size: 0.9rem; color: #777; margin-top: 2rem; }
</style>

<?php require_once __DIR__ . '/public_footer.php'; ?>