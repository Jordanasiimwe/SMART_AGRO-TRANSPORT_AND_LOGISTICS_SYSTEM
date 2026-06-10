<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/config.php';

// Ensure user is a logged-in admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /login');
    exit;
}

require_once __DIR__ . '/User.php';
require_once __DIR__ . '/Product.php';
require_once __DIR__ . '/DashboardStats.php';
require_once __DIR__ . '/Feedback.php';

$userModel = new User();
$productModel = new Product();
$statsModel = new DashboardStats();
$feedbackModel = new Feedback();

$totalFarmers = $userModel->countUsersByRole('farmer');
$totalVendors = $userModel->countUsersByRole('vendor');
$totalProducts = $productModel->countAllProducts();
$totalOrders = $statsModel->getTotalOrders();

// Fetch all product images for the marquee
$productImages = $statsModel->getProductImages();

$withdrawals = $userModel->getWithdrawals();
$fundStats = $statsModel->getMaintenanceFundStats($withdrawals);
$availableFund = $fundStats['available'];
$totalSales = $fundStats['total_sales'];

$statusCounts = $statsModel->getStatusCounts();
$pendingOrders = $statusCounts['pending'] ?? 0;
$approvedOrders = $statusCounts['approved'] ?? 0;
$cancelledOrders = $statusCounts['cancelled'] ?? 0;

require_once __DIR__ . '/Message.php';
$msgModel = new Message();
$unreadCount = $msgModel->countUnread($_SESSION['user_id']);

$unreadFeedbackCount = $feedbackModel->countUnread();

// Fetch online users (active in last 5 minutes)
$onlineUsers = $userModel->getOnlineUsers(5);

$is_maintenance_mode = file_exists(__DIR__ . '/maintenance.flag');
?>

<div class="admin-wrapper">
    <!-- Product Image Marquee (Background) -->
    <?php if (!empty($productImages)): ?>
    <div class="marquee-container no-print">
        <?php
            // Create multiple shuffled arrays for variety across rows
            $rows = 4;
            $image_rows = [];
            for ($i = 0; $i < $rows; $i++) {
                $shuffled = $productImages;
                shuffle($shuffled);
                // Duplicate the array to ensure a seamless loop for the marquee animation
                $image_rows[] = array_merge($shuffled, $shuffled);
            }
        ?>

        <?php foreach ($image_rows as $index => $images): ?>
            <div class="marquee-row <?php echo ($index % 2 == 1) ? 'reverse' : ''; ?>">
                <div class="marquee-content">
                    <?php foreach ($images as $imgUrl): ?>
                        <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="Product Image" class="marquee-image">
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Sidebar (Hidden by default) -->
    <div id="adminSidebar" class="sidebar no-print">
         <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
        <div class="sidebar-header">System Stats</div>
        <div class="sidebar-item"><span>Total Sales</span><span class="sidebar-value">UGX <?php echo number_format($totalSales); ?></span></div> 
        <a href="/admin-orders?status=pending" class="sidebar-item"><span>Pending Orders</span><span class="sidebar-value"><?php echo $pendingOrders; ?></span></a>
        <a href="/admin-orders?status=approved" class="sidebar-item"><span>Approved Orders</span><span class="sidebar-value"><?php echo $approvedOrders; ?></span></a>
        <a href="/admin-orders?status=cancelled" class="sidebar-item"><span>Cancelled Orders</span><span class="sidebar-value"><?php echo $cancelledOrders; ?></span></a>
        <div class="sidebar-item"><span>Active Farmers</span><span class="sidebar-value"><?php echo $totalFarmers; ?></span></div>
        <div class="sidebar-item"><span>Active Vendors</span><span class="sidebar-value"><?php echo $totalVendors; ?></span></div>
        <div class="sidebar-header">Quick Links</div>
        <a href="/users?role=farmer">Farmer Records</a>
        <a href="/users?role=vendor">Vendor Records</a>

        <!--<a href="/admin-orders">All Orders</a> -->

        <a href="/admin-orders" style="display: flex; justify-content: space-between; align-items: center; padding-right: 20px;">
            <span>All Orders</span>
            <?php if ($unreadCount > 0): ?>
                <span class="sidebar-value" style="background-color: #dc3545; border-radius: 50%; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; padding: 0;"><?php echo $unreadCount; ?></span>
            <?php endif; ?>
        </a>

        <a href="/messages" style="display: flex; justify-content: space-between; align-items: center; padding-right: 20px;">
            <span>All Messages</span>
            <?php if ($unreadCount > 0): ?>
                <span class="sidebar-value" style="background-color: #dc3545; border-radius: 50%; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; padding: 0;"><?php echo $unreadCount; ?></span>
            <?php endif; ?>
        </a>
        
        <a href="/admin-feedback" style="display: flex; justify-content: space-between; align-items: center; padding-right: 20px;">
            <span>View Feedback</span>
            <?php if ($unreadFeedbackCount > 0): ?>
                <span class="sidebar-value" style="background-color: #dc3545; border-radius: 50%; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; padding: 0;"><?php echo $unreadFeedbackCount; ?></span>
            <?php endif; ?>
        </a>
    </div>

    <!-- Main Content Area -->
    <div id="main-content">
        <!-- Admin Navigation Strip -->
        <?php if (isset($_SESSION['action_success'])): ?>
            <div class="success" style="margin: 1rem; padding: 10px; background: #d4edda; color: #155724; border-radius: 5px;">
                <?php echo $_SESSION['action_success']; unset($_SESSION['action_success']); ?>
            </div>
        <?php endif; ?>

        <div class="admin-nav-strip no-print">
            <div class="left-nav-items">
                <span class="open-btn" onclick="openNav()">&#9776; Home</span>
            </div>
            <div class="admin-search-container">
                <form action="/search" method="GET" class="admin-search-form">
                    <input type="search" name="q" placeholder="Search products, farmers, vendors, or SACCOs..." required>
                    <button type="submit">Search</button>
                </form>
            </div>
            <div class="right-nav-items">
                <!-- Bulk SMS Button for Maintenance -->
                <button onclick="openMaintenanceSmsModal()" class="sms-blast-btn" title="Send Bulk Maintenance SMS" style="border:none; cursor:pointer; font-size:1rem; font-family:inherit;">
                    <span class="icon">&#9993;</span> SMS Alert
                </button>

                <a href="/admin/toggle-maintenance"
                   class="maintenance-toggle <?php echo $is_maintenance_mode ? 'active' : ''; ?>"
                   title="<?php echo $is_maintenance_mode ? 'Maintenance Mode is ON. Click to deactivate.' : 'System is LIVE. Click to activate maintenance mode.'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-power"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path><line x1="12" y1="2" x2="12" y2="12"></line></svg>
                </a>
            </div>
        </div>

        <div class="container">
            <div class="page-header" style="margin-top: 1.5rem;">
                <img src="/images/smart.jpg" alt="Smart AgroLink System Logo" class="dashboard-logo">

                <h1>Admin Dashboard</h1>
                <p>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>. Here's a summary of system activity.</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card" onclick="openWithdrawModal()" style="cursor: pointer; border-left-color: #28a745; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                    <h3>Maintenance Fund</h3>
                    <p class="stat-number" style="color: #28a745;">****</p>
                    <small>Click to View Details & Withdraw</small>
                </div>
                <div class="stat-card">
                    <h3>Total Farmers</h3>
                    <p class="stat-number"><?php echo $totalFarmers; ?></p>
                    <a href="/users?role=farmer" class="stat-link">View All &rarr;</a>
                </div>
                <div class="stat-card">
                    <h3>Total Vendors</h3>
                    <p class="stat-number"><?php echo $totalVendors; ?></p>
                    <a href="/users?role=vendor" class="stat-link">View All &rarr;</a>
                </div>
                <div class="stat-card">
                    <h3>Product Listings</h3>
                    <p class="stat-number"><?php echo $totalProducts; ?></p>
                    <a href="/search?q=" class="stat-link">Search Products &rarr;</a>
                </div>
                <div class="stat-card">
                    <h3>Total Orders</h3>
                    <p class="stat-number"><?php echo $totalOrders; ?></p>
                    <a href="/admin-orders" class="stat-link">View All &rarr;</a>
                </div>
            </div>

            <!-- Statistical Review Chart -->
            <div class="stat-review-section">
                <h2>Order Status Overview</h2>
                <div style="height: 300px;">
                    <canvas id="orderStatusChart"></canvas>
                </div>
            </div>

            <!-- Real-Time Active Users Section -->
            <div class="online-users-section">
                <h2>
                    <span style="color: #28a745; font-size: 1.5rem;">&#9679;</span> 
                    Live Users (<?php echo count($onlineUsers); ?>)
                </h2>
                <p style="margin-bottom: 1rem; color: #666;">Users active in the last 5 minutes:</p>
                
                <div style="margin-bottom: 1rem;">
                    <a href="/active-users" style="color: var(--primary-color); font-weight: bold; text-decoration: none;">View Activity History &rarr;</a>
                </div>

                <?php if (!empty($onlineUsers)): ?>
                    <div class="online-users-grid">
                        <?php foreach ($onlineUsers as $user): ?>
                            <div class="user-chip">
                                <div class="user-avatar"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></div>
                                <div class="user-details">
                                    <span class="username"><?php echo htmlspecialchars($user['username']); ?></span>
                                    <span class="role role-<?php echo htmlspecialchars($user['role_name']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($user['role_name'])); ?>
                                    </span>
                                </div>
                                <div class="time-status">Online</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No other users are currently online.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Withdraw Funds Modal -->
<div id="withdrawModal" class="modal no-print">
    <div class="modal-content">
        <span class="close-btn" onclick="closeWithdrawModal()">&times;</span>
        <h3>Fund Transfer / Withdrawal</h3>
        
        <!-- Mock Source Account Display -->
        <div style="background-color: #e9ecef; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #dee2e6;">
            <p style="margin: 0; font-size: 0.85rem; text-transform: uppercase; color: #6c757d;">From Source Account (System Reserve):</p>
            <p style="margin: 5px 0; font-family: monospace; font-size: 1.1rem; letter-spacing: 1px;"><strong>ACCT-9988-7766-MNT</strong></p>
            <p style="margin: 0;">Available Balance: <strong style="color: #28a745;">UGX <?php echo number_format($availableFund); ?></strong></p>
        </div>

        <form action="/admin/withdraw-maintenance" method="POST">
            <?php csrf_field(); ?>
            <div class="form-group">
                <label for="withdrawAmount">Amount to Transfer</label>
                <input type="number" id="withdrawAmount" name="amount" max="<?php echo $availableFund; ?>" required placeholder="Enter amount">
            </div>
            <div class="form-group">
                <label for="transferMethod">Transfer Method</label>
                <select id="transferMethod" name="method" required onchange="toggleTransferFields()">
                    <option value="">-- Select Method --</option>
                    <option value="bank">To Bank Account</option>
                    <option value="mobile_money">To Mobile Money</option>
                </select>
            </div>

            <div id="bankFields" style="display:none; background: #f9f9f9; padding: 10px; border-radius: 5px; margin-bottom: 10px;">
                <input type="text" name="bank_name" placeholder="Bank Name" style="width: 100%; margin-bottom: 5px;">
                <input type="text" name="account_number" placeholder="Account Number" style="width: 100%; margin-bottom: 5px;">
                <input type="text" name="account_name" placeholder="Account Name" style="width: 100%;">
            </div>

            <div id="mmFields" style="display:none; background: #f9f9f9; padding: 10px; border-radius: 5px; margin-bottom: 10px;">
                <input type="text" name="phone_number" placeholder="Enter Mobile Number" style="width: 100%;">
            </div>

            <button type="submit">Initiate Transfer</button>
        </form>
    </div>
</div>

<!-- Maintenance SMS Modal -->
<div id="maintenanceSmsModal" class="modal no-print">
    <div class="modal-content">
        <span class="close-btn" onclick="closeMaintenanceSmsModal()">&times;</span>
        <h3>Send Maintenance Alert</h3>
        <p>This will send an SMS to <strong>ALL</strong> active farmers and vendors (excluding admins).</p>
        
        <form action="/admin/send-maintenance-sms" method="POST">
            <?php csrf_field(); ?>
            <div class="form-group">
                <label for="maintenanceMessage">Message Content</label>
                <textarea id="maintenanceMessage" name="message" rows="4" required placeholder="e.g. System maintenance scheduled for 2:00 PM. Duration: 1 hour."></textarea>
            </div>
            <button type="submit">Send Broadcast</button>
        </form>
    </div>
</div>

<script>
function openNav() {
    document.getElementById("adminSidebar").style.width = "250px";
}

function closeNav() {
    document.getElementById("adminSidebar").style.width = "0";
}

function openWithdrawModal() {
    document.getElementById("withdrawModal").style.display = "block";
}

function closeWithdrawModal() {
    document.getElementById("withdrawModal").style.display = "none";
}

function openMaintenanceSmsModal() {
    document.getElementById("maintenanceSmsModal").style.display = "block";
}

function closeMaintenanceSmsModal() {
    document.getElementById("maintenanceSmsModal").style.display = "none";
}

function toggleTransferFields() {
    var method = document.getElementById("transferMethod").value;
    document.getElementById("bankFields").style.display = (method === 'bank') ? 'block' : 'none';
    document.getElementById("mmFields").style.display = (method === 'mobile_money') ? 'block' : 'none';
    
    // Toggle required attributes to prevent form validation errors on hidden fields
    document.querySelectorAll('#bankFields input').forEach(input => input.required = (method === 'bank'));
    document.querySelectorAll('#mmFields input').forEach(input => input.required = (method === 'mobile_money'));
}

// Chart.js Implementation
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('orderStatusChart').getContext('2d');
    const orderChart = new Chart(ctx, {
        type: 'bar', 
        data: {
            labels: ['Pending', 'Approved', 'Cancelled'],
            datasets: [{
                label: '# of Orders',
                data: [<?php echo $pendingOrders; ?>, <?php echo $approvedOrders; ?>, <?php echo $cancelledOrders; ?>],
                backgroundColor: [
                    'rgba(255, 193, 7, 0.7)',  // Yellow for Pending
                    'rgba(40, 167, 69, 0.7)',  // Green for Approved
                    'rgba(220, 53, 69, 0.7)'   // Red for Cancelled
                ],
                borderColor: [
                    'rgba(255, 193, 7, 1)',
                    'rgba(40, 167, 69, 1)',
                    'rgba(220, 53, 69, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1 // Ensure we don't see decimals (e.g. 1.5 orders)
                    }
                }
            },
            onClick: (e) => {
                const points = orderChart.getElementsAtEventForMode(e, 'nearest', { intersect: true }, true);
                if (points.length) {
                    const firstPoint = points[0];
                    const label = orderChart.data.labels[firstPoint.index].toLowerCase();
                    window.location.href = `/admin-orders?status=${label}`;
                }
            },
            plugins: { legend: { display: false } }
        }
    });
});
</script>
<!-- Load Chart.js from CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    /* Admin Wrapper */
    .admin-wrapper {
        position: relative;
        min-height: 80vh;
        overflow: hidden; /* Contain the background marquee */
    }

    /* Sub-Navigation Strip */
    .admin-nav-strip {
        background-color: #333;
        color: white;
        padding: 10px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .left-nav-items {
        display: flex;
        gap: 15px;
    }

    .open-btn {
        font-size: 1.2rem;
        cursor: pointer;
        padding: 8px 15px;
        border-radius: 5px;
        background-color: #444;
        transition: 0.3s;
    }
    .open-btn:hover { background-color: #555; }
    
    .nav-item {
        text-decoration: none;
        color: white;
        font-size: 1.2rem;
        cursor: pointer;
        padding: 8px 15px;
        border-radius: 5px;
        transition: 0.3s;
    }
    .nav-item:hover { background-color: #555; }

    .admin-search-form {
        display: flex;
        align-items: center;
    }
    .admin-search-form input[type="search"] {
        padding: 8px 12px;
        border-radius: 5px 0 0 5px;
        border: 1px solid #666;
        background-color: #3c3c3c;
        color: white;
        min-width: 300px;
        border-right: none;
    }
    .admin-search-form button {
        padding: 9px 12px;
        border-radius: 0 5px 5px 0;
        border: none;
        background-color: var(--primary-color);
        color: white;
        cursor: pointer;
        margin-top: 0;
        width: auto;
    }

    .sms-blast-btn {
        background-color: #ff9800;
        color: white;
        text-decoration: none;
        padding: 8px 12px;
        border-radius: 5px;
        font-weight: bold;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    /* Maintenance Toggle */
    .maintenance-toggle {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #4CAF50; /* Green for LIVE */
        color: white;
        transition: all 0.3s ease;
    }
    .maintenance-toggle:hover {
        transform: scale(1.1);
    }
    .maintenance-toggle.active {
        background-color: #f31707; /* Red for MAINTENANCE */
    }
    .maintenance-toggle svg {
        width: 20px;
        height: 20px;
    }

    /* Sidebar Styles */
    .sidebar {
        height: 100%;
        width: 0;
        position: fixed;
        z-index: 2000;
        top: 0;
        left: 0;
        background-color: #111;
        overflow-x: hidden;
        overflow-y: auto;
        transition: 0.5s;
        padding-top: 60px;
    }

    .sidebar a {
        padding: 8px 8px 8px 32px;
        text-decoration: none;
        font-size: 1.2rem;
        color: #818181;
        display: block;
        transition: 0.3s;
    }
    .sidebar a:hover { color: #f1f1f1; }
    .sidebar .closebtn { position: absolute; top: 0; right: 25px; font-size: 36px; margin-left: 50px; }
    .sidebar-header { color: white; padding: 0 32px; font-size: 0.9rem; text-transform: uppercase; margin-bottom: 10px; opacity: 0.7; }
    .sidebar-item {
        padding: 8px 32px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: #818181;
        font-size: 1rem;
    }
    .sidebar-item .sidebar-value {
        font-weight: bold;
        color: #fff;
        background-color: #333;
        padding: 2px 8px;
        border-radius: 5px;
        font-size: 0.9rem;
    }
    #main-content { transition: margin-left .5s; }
    #main-content { padding-left: 0; } /* Reset margin/padding */

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    .stat-card {
        background-color: #fff;
        padding: 1.5rem;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        border-left: 3px solid var(--primary-color);
        display: flex;
        flex-direction: column;
    }
    .stat-card h3 {
        margin: 0 0 0.5rem 0;
        font-size: 1.1rem;
        color: #555;
        font-weight: 600;
    }
    .stat-card .stat-number {
        margin: 0.5rem 0;
        font-size: 2.8rem;
        font-weight: bold;
        color: var(--dark-text);
        line-height: 1;
    }
    .stat-card .stat-link {
        text-decoration: none;
        color: var(--primary-color);
        font-weight: bold;
        margin-top: auto;
        padding-top: 1rem;
    }
    .stat-card .stat-link:hover {
        text-decoration: underline;
    }

    /* Online Users Styles */
    .online-users-section {
        background-color: #fff;
        padding: 1.5rem;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        margin-bottom: 2rem;
    }
    .online-users-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 15px;
    }
    .user-chip {
        display: flex;
        align-items: center;
        padding: 10px;
        background-color: #f8f9fa;
        border: 1px solid #eee;
        border-radius: 50px;
    }
    .user-avatar {
        width: 40px;
        height: 40px;
        background-color: var(--primary-color);
        color: white;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        font-weight: bold;
        margin-right: 10px;
    }
    .user-details { display: flex; flex-direction: column; line-height: 1.2; flex-grow: 1; }
    .user-details .username { font-weight: bold; font-size: 0.95rem; }
    .user-details .role { font-size: 0.75rem; color: #666; text-transform: uppercase; }
    .time-status { font-size: 0.75rem; color: #28a745; font-weight: bold; margin-left: 10px; }

    /* Marquee Styles */
    .marquee-container {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        overflow: hidden;
        white-space: nowrap;
        z-index: -1; /* Place it behind the content */
        opacity: 0.3; /* Make it even more visible */
        pointer-events: none; /* Make it non-interactive */
    }

    .marquee-row {
        position: absolute;
        width: 100%;
    }

    /* Distribute rows vertically and add animation delays for variety */
    .marquee-row:nth-child(1) { top: 5%; animation-delay: -10s; }
    .marquee-row:nth-child(2) { top: 30%; animation-delay: -5s; }
    .marquee-row:nth-child(3) { top: 55%; animation-delay: -15s; }
    .marquee-row:nth-child(4) { top: 80%; }

    .marquee-content {
        display: inline-block;
        animation: marquee 60s linear infinite; /* Slower animation for larger images */
    }

    .marquee-row.reverse .marquee-content {
        animation-name: marquee-reverse;
    }

    .marquee-image {
        height: 200px; /* Enlarged images */
        width: auto;
        margin-right: 50px; /* More space between larger images */
        /* Removed border-radius and box-shadow for a "loose" feel */
        vertical-align: middle;
    }

    @keyframes marquee {
        0% { transform: translateX(0); }
        100% { transform: translateX(-50%); }
    }

    @keyframes marquee-reverse {
        0% { transform: translateX(-50%); }
        100% { transform: translateX(0); }
    }

    /* Stat Review Chart Styles */
    .stat-review-section {
        background-color: #fff;
        padding: 1.5rem;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        margin-bottom: 2rem;
    }
    .stat-review-section h2 {
        margin-top: 0;
        margin-bottom: 1.5rem;
        text-align: center;
        color: var(--dark-text);
    }
</style>

<?php require_once __DIR__ . '/footer.php'; ?>