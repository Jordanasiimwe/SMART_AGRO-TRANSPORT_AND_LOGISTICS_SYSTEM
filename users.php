<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Admin-only page
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /dashboard');
    exit;
}

require_once __DIR__ . '/User.php';

$role = $_GET['role'] ?? '';
if (!in_array($role, ['farmer', 'vendor'])) {
    header('Location: /dashboard');
    exit;
}

$userModel = new User();
$users = $userModel->getUsersByRole($role);

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = $role . "_records_" . date('Y-m-d') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // Header
    if ($role === 'farmer') {
        fputcsv($output, ['Username', 'Email', 'Farm Name', 'Location', 'Contact', 'Date Registered']);
    } else { // vendor
        fputcsv($output, ['Username', 'Email', 'Market Stall ID', 'SACCO', 'Contact', 'Date Registered']);
    }

    // Data
    if (!empty($users)) {
        foreach ($users as $user) {
            $rowData = [
                $user['username'],
                $user['email'],
            ];
            if ($role === 'farmer') {
                $rowData[] = $user['farm_name'] ?: 'N/A';
                $rowData[] = $user['location'] ?: 'N/A';
                $rowData[] = $user['contact'] ?: 'N/A';
            } else { // vendor
                $rowData[] = $user['market_stall_id'] ?: 'N/A';
                $rowData[] = $user['sacco_name'] ?: 'N/A';
                $rowData[] = $user['contact'] ?: 'N/A';
            }
            $rowData[] = (new DateTime($user['created_at']))->format('Y-m-d');
            fputcsv($output, $rowData);
        }
    }

    fclose($output);
    exit;
}

require_once __DIR__ . '/header.php';

$pageTitle = ucfirst($role) . ' Records';

?>

<div class="container">
    <div class="page-header no-print">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <div class="page-header-actions">
            <button onclick="window.print()" class="button button-pdf">Print / Save as PDF</button>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="button button-excel">Export to Excel</a>
            <a href="/dashboard" class="button-back">&larr; Back to Dashboard</a>
        </div>
    </div>

    <?php if (empty($users)): ?>
        <p>No <?php echo htmlspecialchars($role); ?>s found in the system.</p>
    <?php else: ?>
        <div class="table-responsive">
        <table class="records-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <?php if ($role === 'farmer'): ?>
                        <th>Farm Name</th>
                        <th>Location</th>
                        <th>Contact</th>
                    <?php elseif ($role === 'vendor'): ?>
                        <th>Market Stall ID</th>
                        <th>SACCO</th>
                        <th>Contact</th>
                    <?php endif; ?>
                    <th>Date Registered</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <?php if ($role === 'farmer'): ?>
                            <td><?php echo htmlspecialchars($user['farm_name'] ?: 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($user['location'] ?: 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($user['contact'] ?: 'N/A'); ?></td>
                        <?php elseif ($role === 'vendor'): ?>
                            <td><?php echo htmlspecialchars($user['market_stall_id'] ?: 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($user['sacco_name'] ?: 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($user['contact'] ?: 'N/A'); ?></td>
                        <?php endif; ?>
                        <td><?php echo (new DateTime($user['created_at']))->format('F j, Y'); ?></td>
                        <td>
                            <?php if (($user['status'] ?? 'active') === 'active'): ?>
                                <span style="color: green; font-weight: bold;">Active</span>
                            <?php else: ?>
                                <span style="color: red; font-weight: bold;">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions-cell">
                            <button onclick="openSmsModal('<?php echo $user['id']; ?>', '<?php echo htmlspecialchars($user['username']); ?>')" class="action-button button-sms">SMS</button>
                            
                            <!-- Status Toggle -->
                            <?php if (($user['status'] ?? 'active') === 'active'): ?>
                                <a href="/users/status?id=<?php echo $user['id']; ?>&status=inactive" class="action-button button-warning" onclick="return confirm('Disable this user? They will not be able to login.');">Disable</a>
                            <?php else: ?>
                                <a href="/users/status?id=<?php echo $user['id']; ?>&status=active" class="action-button button-success" onclick="return confirm('Re-activate this user?');">Enable</a>
                            <?php endif; ?>

                            <!-- Delete -->
                            <a href="/users/delete?id=<?php echo $user['id']; ?>" class="action-button button-delete" onclick="return confirm('Are you sure you want to PERMANENTLY delete this user? This cannot be undone.');">Delete</a>
                            <a href="/map-view?role=<?php echo $role; ?>&id=<?php echo $user['id']; ?>" class="action-button button-map" target="_blank" title="View on Map">&#128205; Map</a>
                       </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>

<style>
    .page-header-actions {
        display: flex;
        gap: 1rem;
        align-items: center;
    }
    .records-table { width: 100%; border-collapse: collapse; margin-top: 1.5rem; }
    .records-table th, .records-table td { border: 1px solid var(--border-color); padding: 0.75rem; text-align: left; }
    .records-table thead { background-color: var(--light-gray); }
    .records-table tbody tr:nth-child(even) { background-color: #f8f9fa; }
    .button-sms {
        background-color: #007bff;
    }
    .button-sms:hover {
        background-color: #0056b3;
    }
    .button-warning {
        background-color: #ffc107;
        color: #212529;
    }
    .button-warning:hover { background-color: #e0a800; }
    .button-success {
        background-color: #28a745;
    }
    .button-success:hover { background-color: #218838; }
    .button-map { background-color: #007bff; }
    .button-map:hover { background-color: #0056b3; }
</style>