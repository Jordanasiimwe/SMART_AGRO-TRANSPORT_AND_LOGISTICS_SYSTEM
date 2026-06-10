<?php
// Start session and check authentication before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Admin-only page
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /login');
    exit;
}

require_once __DIR__ . '/Database.php';

$db = Database::getInstance();

$status_filter = $_GET['status'] ?? '';
$pageTitle = 'All System Orders';

// Base query
$sql = "
    SELECT
        o.id AS order_id,
        o.created_at AS order_date,
        o.status AS order_status,
        o.payment_method,
        vendor_user.username AS vendor_name,
        vendor_user.id AS vendor_id,
        p.name AS product_name,
        p.image_url,
        f.farm_name,
        oi.quantity AS item_quantity,
        p.farmer_id,
        oi.unit AS item_unit,
        oi.price_at_purchase
    FROM orders o
    JOIN users vendor_user ON o.vendor_id = vendor_user.id
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    JOIN farmers f ON p.farmer_id = f.user_id
";

// Add filter if status is present
if (!empty($status_filter)) {
    $sql .= " WHERE o.status = :status";
    $pageTitle = ucfirst($status_filter) . " Orders";
}

$sql .= " ORDER BY o.created_at DESC, o.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute(!empty($status_filter) ? ['status' => $status_filter] : []);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group the flat results by order ID for easier display
$orders = [];
foreach ($results as $row) {
    $order_id = $row['order_id'];
    if (!isset($orders[$order_id])) {
        $orders[$order_id] = [
            'id' => $order_id,
            'date' => new DateTime($row['order_date']),
            'status' => $row['order_status'],
            'vendor_name' => $row['vendor_name'],
            'vendor_id' => $row['vendor_id'],
            'payment_method' => $row['payment_method'],
            'items' => [],
            'total' => 0
        ];
    }
    
    // Price was stored per unit at purchase, so subtotal is straightforward.
    $subtotal = $row['price_at_purchase'] * $row['item_quantity'];

    $orders[$order_id]['items'][] = [
        'product_name' => $row['product_name'],
        'image_url' => $row['image_url'],
        'farm_name' => $row['farm_name'],
        'farmer_id' => $row['farmer_id'],
        'quantity' => $row['item_quantity'],
        'unit' => $row['item_unit'],
        'price' => $row['price_at_purchase'],
        'subtotal' => $subtotal
    ];

    $orders[$order_id]['total'] += $subtotal;
}

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = "orders_report_" . date('Y-m-d') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // Header
    fputcsv($output, ['Order ID', 'Date', 'Status', 'Vendor', 'Payment Method', 'Product', 'Farm', 'Quantity', 'Unit', 'Price at Purchase', 'Subtotal']);

    // Data
    if (!empty($orders)) {
        foreach ($orders as $order) {
            foreach ($order['items'] as $item) {
                fputcsv($output, [
                    $order['id'],
                    $order['date']->format('Y-m-d H:i:s'),
                    $order['status'],
                    $order['vendor_name'],
                    $order['payment_method'],
                    $item['product_name'],
                    $item['farm_name'],
                    $item['quantity'],
                    $item['unit'],
                    $item['price'],
                    $item['subtotal']
                ]);
            }
        }
    }

    fclose($output);
    exit;
}

require_once __DIR__ . '/header.php';
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

    <?php if (empty($orders)): ?>
        <p>There are no orders in the system yet.</p>
    <?php else: ?>
        <div class="orders-list">
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <h3>Order #<?php echo htmlspecialchars($order['id']); ?></h3>
                            <p>Placed on: <?php echo $order['date']->format('F j, Y, g:i a'); ?></p>
                            <p>Vendor: <strong><?php echo htmlspecialchars($order['vendor_name']); ?></strong> <button onclick="openSmsModal('<?php echo $order['vendor_id']; ?>', '<?php echo htmlspecialchars($order['vendor_name']); ?>')" class="action-button button-sms">Contact</button></p>
                        </div>
                        <div class="order-status status-<?php echo htmlspecialchars(str_replace(' ', '-', $order['status'])); ?>">
                            <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                        </div>
                    </div>
                    <div class="order-body">
                        <div class="table-responsive">
                        <table class="order-items-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Product</th>
                                    <th>From Farm</th>
                                    <th>Quantity</th>
                                    <th>Price at Purchase</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order['items'] as $item): ?>
                                    <tr>
                                        <td><img src="<?php echo htmlspecialchars($item['image_url'] ?: '/images/smart.jpg'); ?>" alt="Product" class="product-thumbnail"></td>
                                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['farm_name'] ?: 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($item['quantity']); ?> <?php echo htmlspecialchars($item['unit']); ?>(s)</td>
                                        <td>UGX <?php echo number_format($item['price'], 2); ?></td>
                                        <td>UGX <?php echo number_format($item['subtotal'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                    <div class="order-footer">
                        <?php if (!empty($order['items'])): ?>
                            <?php
                                $farmer_id = $order['items'][0]['farmer_id'];
                                $farm_name = $order['items'][0]['farm_name'];
                            ?>
                            <button onclick="openSmsModal('<?php echo $farmer_id; ?>', '<?php echo htmlspecialchars($farm_name); ?>')" class="action-button button-sms">Contact Farmer (<?php echo htmlspecialchars($farm_name); ?>)</button>
                        <?php endif; ?>
                        <strong>Order Total: UGX <?php echo number_format($order['total'], 2); ?></strong>
                        <br>
                        <small style="color: #28a745;">Maintenance Fee Generated (1%): UGX <?php echo number_format($order['total'] * 0.01, 2); ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>

<!-- Re-using styles from my-orders.php for consistency -->
<style>
    .page-header-actions {
        display: flex;
        gap: 1rem;
        align-items: center;
    }
    .orders-list { display: flex; flex-direction: column; gap: 1.5rem; }
    .order-card { background-color: #fff; border: 1px solid var(--border-color); border-radius: var(--border-radius); box-shadow: var(--shadow); overflow: hidden; }
    .order-header { padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: flex-start; background-color: var(--light-gray); }
    .order-header h3 { margin: 0 0 0.25rem 0; color: var(--primary-color); }
    .order-header p { margin: 0; font-size: 0.9rem; color: #6c757d; }
    .order-status { padding: 0.4rem 0.8rem; border-radius: 20px; font-weight: bold; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; }
    .status-pending { background-color: #fff3cd; color: #856404; }
    .status-approved { background-color: #d1ecf1; color: #0c5460; }
    .status-completed { background-color: #d4edda; color: #155724; }
    .status-cancelled { background-color: #f8d7da; color: #721c24; }
    .order-body { padding: 1.5rem; }
    .order-items-table { width: 100%; border: none; }
    .order-items-table th, .order-items-table td { text-align: left; padding: 0.75rem; border-bottom: 1px solid var(--border-color); }
    .order-items-table th { background-color: transparent; color: var(--dark-text); font-size: 0.9rem; }
    .order-items-table tr:last-child td { border-bottom: none; }
    .order-footer { padding: 1rem 1.5rem; background-color: var(--light-gray); display: flex; justify-content: space-between; align-items: center; font-size: 1.1rem; border-top: 1px solid var(--border-color); }
    .action-button.button-sms {
        width: auto;
        margin-top: 0;
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
        background-color: #007bff;
    }
    .order-header .button-sms {
        padding: 2px 8px;
        font-size: 0.8rem;
        margin-left: 10px;
    }
</style>