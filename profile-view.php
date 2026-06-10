<?php
require_once __DIR__ . '/header.php';

// Ensure user is a logged-in farmer
if ($_SESSION['user_role'] !== 'farmer') {
    header('Location: /login');
    exit;
}

require_once __DIR__ . '/User.php';
$userModel = new User();
$farmerProfile = $userModel->getFarmerProfile($_SESSION['user_id']);

?>

<div class="container profile-view">
    <div class="page-header">
        <h1>Farmer Profile</h1>
        <a href="/dashboard" class="button-back">&larr; Back to Dashboard</a>
    </div>
    <p><strong>Username:</strong> <?php echo htmlspecialchars($farmerProfile['username'] ?? 'N/A'); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($farmerProfile['email'] ?? 'N/A'); ?></p>
    <p><strong>Farm Name:</strong> <?php echo htmlspecialchars($farmerProfile['farm_name'] ?? 'Not Set'); ?></p>
    <p><strong>Location:</strong> <?php echo htmlspecialchars($farmerProfile['location'] ?? 'Not Set'); ?></p>
    <p><strong>Contact:</strong> <?php echo htmlspecialchars($farmerProfile['contact'] ?? 'Not Set'); ?></p>
</div>

<?php
require_once __DIR__ . '/footer.php';
?>