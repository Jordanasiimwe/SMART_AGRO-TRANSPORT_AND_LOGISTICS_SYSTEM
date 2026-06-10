<?php require_once __DIR__ . '/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="SmartAgro-Link System is a digital platform designed to connect farmers directly with vendors in Nakawa Market. The system enables farmers to showcase available agricultural products, while vendors can easily search, order, and communicate with suppliers in real time. By reducing intermediaries, SmartAgro-Link System improves market access, enhances transparency, promotes fair pricing, and streamlines the agricultural supply chain.">
    <meta name="keywords" content="SmartAgro-Link, agriculture, farmers, vendors, Nakawa Market, agricultural products, supply chain, fair pricing">
    <meta property="og:title" content="Register - Smart AgroLink System">
    <meta property="og:description" content="SmartAgro-Link System is a digital platform designed to connect farmers directly with vendors in Nakawa Market. The system enables farmers to showcase available agricultural products, while vendors can easily search, order, and communicate with suppliers in real time. By reducing intermediaries, SmartAgro-Link System improves market access, enhances transparency, promotes fair pricing, and streamlines the agricultural supply chain.">
    <meta property="og:image" content="/images/smart.jpg">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Register - Smart AgroLink System">
    <meta name="twitter:description" content="SmartAgro-Link System is a digital platform designed to connect farmers directly with vendors in Nakawa Market. The system enables farmers to showcase available agricultural products, while vendors can easily search, order, and communicate with suppliers in real time. By reducing intermediaries, SmartAgro-Link System improves market access, enhances transparency, promotes fair pricing, and streamlines the agricultural supply chain.">
    <meta name="twitter:image" content="/images/smart.jpg">
    <title>Register - Smart AgroLink System</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <form action="/register" method="POST">
            <?php csrf_field(); ?>
            <h2>Smart AgroLink System Registration</h2>

            <?php if (isset($error)): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="role">Registering as:</label>
                <select id="role" name="role" onchange="toggleSaccoSelect()">
                    <option value="2">Farmer</option>
                    <option value="3">Vendor</option>
                </select>
            </div>

            <div class="form-group" id="sacco-group" style="display:none;">
                <label for="sacco">Your SACCO Name</label>
                <input type="text" id="sacco" name="sacco" placeholder="e.g. Nakawa Market Vendors SACCO">
            </div>

            <button type="submit">Register</button>
            <p class="register-link" style="text-align: center; margin-top: 1rem;">Already have an account? <a href="/login">Login here</a></p>
        </form>
    </div>
    <script>
        function toggleSaccoSelect() {
            var roleSelect = document.getElementById('role');
            var saccoGroup = document.getElementById('sacco-group');
            saccoGroup.style.display = (roleSelect.value === '2' || roleSelect.value === '3') ? 'block' : 'none';
        }
        // Run on page load to check the initial value
        document.addEventListener('DOMContentLoaded', toggleSaccoSelect);
    </script>
</body>
</html>