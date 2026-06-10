<?php require_once __DIR__ . '/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="SmartAgro-Link System is a digital platform designed to connect farmers directly with vendors in Nakawa Market. The system enables farmers to showcase available agricultural products, while vendors can easily search, order, and communicate with suppliers in real time. By reducing intermediaries, SmartAgro-Link System improves market access, enhances transparency, promotes fair pricing, and streamlines the agricultural supply chain.">
    <meta name="keywords" content="SmartAgro-Link, agriculture, farmers, vendors, Nakawa Market, agricultural products, supply chain, fair pricing">
    <meta property="og:title" content="Forgot Password - Smart AgroLink System">
    <meta property="og:description" content="SmartAgro-Link System is a digital platform designed to connect farmers directly with vendors in Nakawa Market. The system enables farmers to showcase available agricultural products, while vendors can easily search, order, and communicate with suppliers in real time. By reducing intermediaries, SmartAgro-Link System improves market access, enhances transparency, promotes fair pricing, and streamlines the agricultural supply chain.">
    <meta property="og:image" content="/images/smart.jpg">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Forgot Password - Smart AgroLink System">
    <meta name="twitter:description" content="SmartAgro-Link System is a digital platform designed to connect farmers directly with vendors in Nakawa Market. The system enables farmers to showcase available agricultural products, while vendors can easily search, order, and communicate with suppliers in real time. By reducing intermediaries, SmartAgro-Link System improves market access, enhances transparency, promotes fair pricing, and streamlines the agricultural supply chain.">
    <meta name="twitter:image" content="/images/smart.jpg">
    <title>Forgot Password - Smart AgroLink System</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <form action="/forgot-password" method="POST">
            <?php csrf_field(); ?>
            <h2>Forgot Password</h2>
            <p style="margin-bottom: 1.5rem; color: #666;">Enter your registered email and your new password twice to reset your account.</p>
            
            <?php if (isset($error)): ?><p class="error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
            <?php if (isset($success)): ?><p class="success" style="color: green; background: #d4edda; padding: 10px; border-radius: 5px; margin-bottom: 1rem; border: 1px solid #c3e6cb;"><?php echo htmlspecialchars($success); ?></p><?php endif; ?>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="Enter your registered email" required>
            </div>
            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
            </div>
            <button type="submit">Reset My Password</button>
        </form>
    </div>
    <p class="register-link">Remembered your password? <a href="/login">Login here</a></p>
</body>
</html>