<?php
require_once __DIR__ . '/header.php';

// Admin check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /dashboard');
    exit;
}

require_once __DIR__ . '/User.php';
$userModel = new User();
$users = $userModel->getAllActiveUsers();

?>
<div class="container">
    <div class="page-header">
        <h1>User Activity Log</h1>
        <a href="/dashboard" class="button-back">&larr; Back to Dashboard</a>
    </div>

    <?php if (empty($users)): ?>
        <p>No user activity recorded yet.</p>
        <p style="color: #666; font-size: 0.9rem;">Note: Activity tracking starts from when the 'Live Users' feature was enabled.</p>
    <?php else: ?>
        <div class="table-responsive">
        <table class="records-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Email</th>
                    <th>Last Active</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <?php 
                        $lastActive = new DateTime($user['last_active_at']);
                        $now = new DateTime();
                        $diff = $now->getTimestamp() - $lastActive->getTimestamp();
                        // Consider online if active in last 5 minutes (300 seconds)
                        $isOnline = $diff < 300; 
                    ?>
                    <tr>
                        <td>
                            <?php if ($isOnline): ?>
                                <span style="color: #28a745; font-weight: bold; font-size: 1.2rem; vertical-align: middle;">&#9679;</span>
                            <?php else: ?>
                                <span style="color: #ccc; font-size: 1.2rem; vertical-align: middle;">&#9679;</span>
                            <?php endif; ?>
                            <span style="vertical-align: middle;"><?php echo htmlspecialchars($user['username']); ?></span>
                        </td>
                        <td><?php echo ucfirst(htmlspecialchars($user['role_name'])); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo $lastActive->format('F j, Y, g:i a'); ?></td>
                        <td>
                            <?php if ($isOnline): ?>
                                <span style="color: #28a745; font-weight: bold;">Online</span>
                            <?php else: ?>
                                <span style="color: #666;">Offline</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>

<style>
    .records-table { width: 100%; border-collapse: collapse; margin-top: 1.5rem; }
    .records-table th, .records-table td { border: 1px solid var(--border-color); padding: 0.75rem; text-align: left; }
    .records-table thead { background-color: var(--light-gray); }
    .records-table tbody tr:nth-child(even) { background-color: #f8f9fa; }
</style>

<?php require_once __DIR__ . '/footer.php'; ?>