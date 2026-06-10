<?php
require_once __DIR__ . '/header.php';

// Farmer-only page
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'farmer') {
    header('Location: /login');
    exit;
}

require_once __DIR__ . '/Message.php';
$msgModel = new Message();
$unreadCount = $msgModel->countUnread($_SESSION['user_id']);

require_once __DIR__ . '/User.php';
$userModel = new User();
$farmerProfile = $userModel->getFarmerProfile($_SESSION['user_id']);
$saccoName = $farmerProfile['sacco'] ?? 'Not affiliated';

?>

<div class="sidebar no-print">
    <a href="/about">About Us</a>
    <a href="/contact">Contact</a>
    <a href="/address">Address</a>
    <a href="/feedback">Feedback</a>
</div>

<div class="container dashboard">
        <img src="/images/smart.jpg" alt="Smart AgroLink System Logo" class="dashboard-logo">
        <h1>Farmer Dashboard</h1>
        <section class="welcome-section">
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
            <p>From here, you can view your orders and manage your products.</p>
            <p class="sacco-info">Member of: <strong><?php echo htmlspecialchars($saccoName); ?></strong></p>
        </section>

        <section class="farmer-actions">
            <a href="/products" class="button primary"><span>Manage Products</span></a>
            <a href="/profile/edit" class="button primary"><span>Edit Profile</span></a>
            <a href="/orders" class="button primary"><span>View Orders</span></a>
            <a href="/messages" class="button primary">
                <span>Messages</span>
                <?php if ($unreadCount > 0): ?>
                    <span class="notification-badge"><?php echo $unreadCount; ?></span>
                <?php endif; ?>
            </a>
        </section>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>

<style>
.sacco-info {
    font-size: 0.95rem;
    color: #6c757d;
    margin-top: 0.5rem;
}

/* Sidebar Styles (Matching public_home.php) */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100%;
    width: 200px;
    z-index: 900; /* Behind sticky header (1000) but above content */
    overflow-x: hidden;
    background-color: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(10px);
    display: flex;
    flex-direction: column;
    align-items: center;
    padding-top: 100px; /* Space for top nav */
}

.sidebar a {
    width: 100%;
    text-align: center;
    display: block;
    padding: 16px;
    margin-bottom: 10px;
    text-decoration: none;
    color: #fff;
    font-size: 1rem;
    transition: background-color 0.3s;
}
.sidebar a:hover {
    background-color: rgba(255, 255, 255, 0.1);
    text-decoration: underline;
}

/* Adjust main dashboard container to sit to the right of the fixed sidebar */
.container.dashboard {
    margin-left: 240px; /* 200px sidebar + 40px spacing */
    margin-right: 40px; /* 40px spacing on the right for balance */
    width: auto;        /* Allow container to fill available width */
    max-width: 1600px;  /* Enlarge the container significantly */
}

@media (max-width: 768px) {
    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
        padding-top: 20px;
        flex-direction: row;
        flex-wrap: wrap;
        justify-content: center;
        background-color: #333;
    }
    .sidebar a { width: auto; padding: 10px 20px; }
    
    .container.dashboard {
        margin-left: auto;
        margin-right: auto;
        width: 90%;
    }
}
</style>