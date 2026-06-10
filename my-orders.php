<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/config.php';
// Vendor-only page
if ($_SESSION['user_role'] !== 'vendor') {
    header('Location: /dashboard');
    exit;
}

require_once __DIR__ . '/Database.php';

$vendor_id = $_SESSION['user_id'];
$db = Database::getInstance();

// This query finds all orders placed by the current vendor.
$stmt = $db->prepare("
    SELECT
        o.id AS order_id,
        o.created_at AS order_date,
        o.status AS order_status,
        o.payment_status,
        o.payment_method,
        p.name AS product_name,
        p.image_url,
        f.farm_name,
        f.user_id AS farmer_id,
        oi.quantity AS item_quantity,
        oi.unit AS item_unit,
        oi.price_at_purchase
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    JOIN farmers f ON p.farmer_id = f.user_id
    WHERE o.vendor_id = :vendor_id
    ORDER BY o.created_at DESC, o.id DESC
");
$stmt->execute(['vendor_id' => $vendor_id]);
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
            'payment_status' => $row['payment_status'],
            'payment_method' => $row['payment_method'],
            'farmer_id' => $row['farmer_id'],
            'farm_name' => $row['farm_name'],
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
        'quantity' => $row['item_quantity'],
        'unit' => $row['item_unit'],
        'price' => $row['price_at_purchase'],
        'subtotal' => $subtotal
    ];

    $orders[$order_id]['total'] += $subtotal;
}

?>

<div class="container">
    <div class="page-header">
        <h1>My Orders</h1>
        <a href="/dashboard" class="button-back">&larr; Back to Dashboard</a>
    </div>

    <?php if (empty($orders)): ?>
        <p>You have not placed any orders yet. <a href="/browse">Start shopping!</a></p>
    <?php else: ?>
        <div class="orders-list">
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <h3>Order #<?php echo htmlspecialchars($order['id']); ?></h3>
                            <p>Placed on: <?php echo $order['date']->format('F j, Y, g:i a'); ?></p>
                        </div>
                        <div style="text-align: right;">
                            <div class="order-status status-<?php echo htmlspecialchars(str_replace(' ', '-', $order['status'])); ?>">
                                <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                            </div>
                            <?php if ($order['payment_method'] === 'mobile_money'): ?>
                                <?php if ($order['payment_status'] === 'paid'): ?>
                                    <div class="payment-badge confirmed" style="margin-top: 5px;">&#10003; Mobile Money Confirmed</div>
                                <?php else: ?>
                                    <div class="payment-badge pending" style="margin-top: 5px;">Waiting for Payment Confirmation</div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="order-body">
                        <h4>Items in this Order:</h4>
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
                                        <td>UGX <?php echo number_format($item['price'], 2); ?> / <?php echo htmlspecialchars($item['unit']); ?></td>
                                        <td>UGX <?php echo number_format($item['subtotal'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                    <div class="order-footer">
                        <div>
                            <button onclick="openSmsModal('<?php echo $order['farmer_id']; ?>', '<?php echo htmlspecialchars($order['farm_name']); ?>')" class="action-button button-sms">Contact Farmer</button>
                            <a href="/map-view?role=farmer&id=<?php echo $order['farmer_id']; ?>" class="action-button button-map" target="_blank" title="View on Map">&#128205; Map</a>
                        </div>
                        <strong>Order Total: UGX <?php echo number_format($order['total'], 2); ?></strong>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>

<style>
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
    .order-body h4 { margin-bottom: 1rem; }
    .order-items-table { width: 100%; border: none; }
    .order-items-table th, .order-items-table td { text-align: left; padding: 0.75rem; border-bottom: 1px solid var(--border-color); }
    .order-items-table th { background-color: transparent; color: var(--dark-text); font-size: 0.9rem; }
    .order-items-table tr:last-child td { border-bottom: none; }
    .order-footer { padding: 1rem 1.5rem; background-color: var(--light-gray); display: flex; justify-content: space-between; align-items: center; font-size: 1.1rem; border-top: 1px solid var(--border-color); }
    .order-footer .action-button {
        width: auto;
        margin-top: 0;
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }
    .button-sms {
        background-color: #007bff;
    }
    .button-sms:hover {
        background-color: #0056b3;
    }
    .button-map { background-color: #007bff; }
    .button-map:hover { background-color: #0056b3; }
    .payment-badge {
        font-size: 0.8rem;
        padding: 4px 8px;
        border-radius: 12px;
        display: inline-block;
        font-weight: bold;
    }
    .payment-badge.confirmed {
        background-color: #28a745;
        color: white;
    }
    .payment-badge.pending {
        background-color: #ffc107;
        color: #333;
    }
</style>