<?php
// Start session and check authentication before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Admin-only page
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /dashboard');
    exit;
}

require_once __DIR__ . '/Product.php';
require_once __DIR__ . '/User.php';

$query = $_GET['q'] ?? '';
$pageTitle = 'Search Results for "' . htmlspecialchars($query) . '"';

$products = [];
$farmers = [];
$vendors = [];

$productModel = new Product();
$userModel = new User();

if (!empty($query)) {
    $products = $productModel->searchProducts($query);
    // Search for farmers and vendors
    $farmers = $userModel->searchUsersByRole('farmer', $query);
    $vendors = $userModel->searchUsersByRole('vendor', $query);
} else {
    // If no query is provided, show all products by default.
    // This is for the "Product Listings" link on the admin dashboard.
    $products = $productModel->getAllProducts();
    $pageTitle = 'All Product Listings';
}

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = "products_report_" . date('Y-m-d') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // Header
    fputcsv($output, ['Product Name', 'Farmer', 'Price', 'Available Quantity', 'Unit']);

    // Data
    if (!empty($products)) {
        foreach ($products as $product) {
            fputcsv($output, [
                $product['name'],
                $product['farm_name'],
                $product['price'],
                $product['quantity'],
                $product['unit']
            ]);
        }
    }

    fclose($output);
    exit;
}

require_once __DIR__ . '/header.php';
?>

<div class="container">
    <div class="page-header no-print">
        <h1><?php echo $pageTitle; ?></h1>
        <div class="page-header-actions">
            <button onclick="window.print()" class="button button-pdf">Print / Save as PDF</button>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="button button-excel">Export to Excel</a>
            <a href="/dashboard" class="button-back">&larr; Back to Admin Dashboard</a>
        </div>
    </div>

    <?php if (empty($products) && empty($farmers) && empty($vendors)): ?>
        <p>No results found for "<?php echo htmlspecialchars($query); ?>".</p>
    <?php else: ?>
        
        <!-- Farmers Results -->
        <?php if (!empty($farmers)): ?>
        <div class="search-results-section">
            <h2>Farmers (<?php echo count($farmers); ?>)</h2>
            <div class="table-responsive">
            <table class="records-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Farm Name</th>
                        <th>Location</th>
                        <th>Contact</th>
                        <th>Registered Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($farmers as $farmer): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($farmer['username']); ?></td>
                            <td><?php echo htmlspecialchars($farmer['farm_name']); ?></td>
                            <td><?php echo htmlspecialchars($farmer['location']); ?></td>
                            <td><?php echo htmlspecialchars($farmer['contact']); ?></td>
                            <td><?php echo (new DateTime($farmer['created_at']))->format('Y-m-d'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Vendors Results -->
        <?php if (!empty($vendors)): ?>
        <div class="search-results-section">
            <h2>Vendors (<?php echo count($vendors); ?>)</h2>
            <div class="table-responsive">
            <table class="records-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>SACCO</th>
                        <th>Market Stall ID</th>
                        <th>Contact</th>
                        <th>Registered Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vendors as $vendor): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($vendor['username']); ?></td>
                            <td><?php echo htmlspecialchars($vendor['sacco_name']); ?></td>
                            <td><?php echo htmlspecialchars($vendor['market_stall_id']); ?></td>
                            <td><?php echo htmlspecialchars($vendor['contact']); ?></td>
                            <td><?php echo (new DateTime($vendor['created_at']))->format('Y-m-d'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Product Results -->
        <?php if (!empty($products)): ?>
        <div class="search-results-section">
            <h2>Products (<?php echo count($products); ?>)</h2>
            <div class="table-responsive">
                <table class="records-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Product Name</th>
                            <th>Farmer</th>
                            <th>Price / Unit</th>
                            <th>Available</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><img src="<?php echo htmlspecialchars($product['image_url'] ?: '/images/smart.jpg'); ?>" alt="Product" class="product-thumbnail"></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars($product['farm_name']); ?></td>
                                <td><?php echo number_format($product['price'], 2) . ' / ' . htmlspecialchars($product['unit']); ?></td>
                                <td>
                                    <?php if ($product['quantity'] > 0): ?>
                                        <?php echo htmlspecialchars($product['quantity']) . ' ' . htmlspecialchars($product['unit']) . '(s)'; ?>
                                    <?php else: ?>
                                        <span style="color: red; font-weight: bold;">Out of Stock</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>

<style>
.page-header-actions {
    display: flex;
    gap: 1rem;
    align-items: center;
}
.search-results-section { margin-bottom: 2.5rem; background-color: #fff; padding: 1.5rem; border-radius: var(--border-radius); box-shadow: var(--shadow); }
.search-results-section h2 { color: var(--primary-color); border-bottom: 2px solid var(--light-gray); padding-bottom: 0.5rem; margin-bottom: 1rem; margin-top: 0; }
.records-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
.records-table th, .records-table td { border: 1px solid var(--border-color); padding: 0.75rem; text-align: left; }
.records-table thead { background-color: var(--light-gray); }
.records-table tbody tr:nth-child(even) { background-color: #f8f9fa; }
</style>