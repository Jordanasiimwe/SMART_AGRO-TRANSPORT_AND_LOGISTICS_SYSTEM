<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/config.php';

// Ensure user is a logged-in farmer
if ($_SESSION['user_role'] !== 'farmer') {
    header('Location: /login');
    exit;
}

require_once __DIR__ . '/Database.php';

$farmer_id = $_SESSION['user_id'];
$db = Database::getInstance();

// This query finds all order items belonging to the current farmer's products,
// and joins them with order and vendor information.
$stmt = $db->prepare("
    SELECT
        o.id AS order_id,
        o.created_at AS order_date,
        o.status AS order_status,
        o.payment_method,
        o.payment_status,
        o.transport_type,
        o.transport_info,
        v.username AS vendor_name,
        v.id AS vendor_id,
        p.name AS product_name,
        p.image_url,
        oi.quantity AS item_quantity,
        oi.unit AS item_unit,
        oi.price_at_purchase
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    JOIN users v ON o.vendor_id = v.id
    WHERE p.farmer_id = :farmer_id
    ORDER BY o.created_at DESC, o.id DESC
");
$stmt->execute(['farmer_id' => $farmer_id]);
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
            'vendor' => $row['vendor_name'],
            'vendor_id' => $row['vendor_id'],
            'payment_method' => $row['payment_method'],
            'payment_status' => $row['payment_status'],
            'transport_type' => $row['transport_type'],
            'transport_info' => $row['transport_info'],
            'items' => [],
            'total' => 0
        ];
    }
    
    // Calculate subtotal for the item
    $subtotal = $row['price_at_purchase'] * $row['item_quantity'];

    $orders[$order_id]['items'][] = [
        'product_name' => $row['product_name'],
        'image_url' => $row['image_url'],
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
        <p>You have not received any orders for your products yet.</p>
    <?php else: ?>
        <div class="orders-list">
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <h3>Order #<?php echo htmlspecialchars($order['id']); ?></h3>
                            <p>Placed on: <?php echo $order['date']->format('F j, Y, g:i a'); ?></p>
                            <p>From Vendor: 
                                <strong><?php echo htmlspecialchars($order['vendor']); ?></strong>
                                <a href="/map-view?role=vendor&id=<?php echo $order['vendor_id']; ?>" class="map-link" target="_blank" title="View vendor location">&#128205;</a>
                            </p>
                        </div>
                        <div class="order-status status-<?php echo htmlspecialchars($order['status']); ?>">
                            <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                        </div>
                    </div>
                    <?php if ($order['payment_status'] === 'paid'): ?>
                        <div class="info" style="margin: 0 1.5rem; border-radius: 0; background-color: #d4edda; color: #155724;">&#10004; Payment confirmed. Please prepare the products for transport.</div>
                    <?php endif; ?>
                    <div class="order-body">
                        <h4>Transport Details:</h4>
                        <div class="transport-details">
                            <?php if ($order['transport_type'] === 'hired'): ?>
                                <p>Vendor has hired transport: <strong><?php echo htmlspecialchars($order['transport_info']); ?></strong>. Get in touch.</p>
                            <?php elseif ($order['transport_type'] === 'self'): ?>
                                <p>Vendor will pick up the items themselves (<strong>Self Pickup</strong>).</p>
                            <?php else: ?>
                                <p>No transport information available.</p>
                            <?php endif; ?>
                        </div>
                        <hr>
                        <h4>Items in this Order:</h4>
                        <div class="table-responsive">
                        <table class="order-items-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Product</th>
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
                                        <td><?php echo htmlspecialchars($item['quantity']); ?> <?php echo htmlspecialchars($item['unit']); ?>(s)</td>
                                        <td><?php echo number_format($item['price'], 2); ?> / <?php echo htmlspecialchars($item['unit']); ?></td>
                                        <td><?php echo number_format($item['subtotal'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                    <div class="order-body">
                        <div>
                            <strong>Order Total: UGX <?php echo number_format($order['total'], 2); ?></strong>
                            <?php 
                                $maintenance_fee = $order['total'] * 0.01;
                                $net_earnings = $order['total'] - $maintenance_fee;
                            ?>
                            <div style="font-size: 0.9rem; margin-top: 5px;">
                                <span style="color: #dc3545;">System Maintenance (1%): -UGX <?php echo number_format($maintenance_fee, 2); ?></span><br>
                                <span style="color: #28a745; font-weight: bold;">Net Earnings: UGX <?php echo number_format($net_earnings, 2); ?></span>
                            </div>
                            <?php if ($order['payment_method'] === 'mobile_money'): ?>
                                <div class="payment-confirmation">
                                    <?php if ($order['payment_status'] === 'pending'): ?>
                                        <form action="/orders/confirm-payment" method="POST" style="margin: 0 1rem;">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <button type="submit" class="action-button button-approve">Confirm Payment Received</button>
                                        </form>
                                    <?php elseif ($order['payment_status'] === 'paid'): ?>
                                        <div class="payment-confirmed-badge">&#10004; Payment Received</div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <button onclick="openSmsModal('<?php echo $order['vendor_id']; ?>', '<?php echo htmlspecialchars($order['vendor']); ?>')" class="action-button button-sms" style="margin-left: 1rem; width: auto; margin-top: 0; padding: 0.5rem 1rem;">Contact Vendor</button>
                            <a href="/map-view?role=vendor&id=<?php echo $order['vendor_id']; ?>" class="action-button button-map" target="_blank" style="margin-left: 0.5rem; width: auto; margin-top: 0; padding: 0.5rem 1rem; text-decoration: none;">&#128205; Map</a>
                        </div>
                        <?php if ($order['status'] === 'pending'): ?>
                            <form action="/orders/update-status" method="POST" class="order-actions-form">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <button type="submit" name="status" value="approved" class="action-button button-approve">Approve</button>
                                <button type="submit" name="status" value="cancelled" class="action-button button-cancel">Decline</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/footer.php';
?>

<style>
    .orders-list {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }
    .order-card {
        background-color: #fff;
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
    }
    .order-header {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        background-color: var(--light-gray);
    }
    .order-header h3 {
        margin: 0 0 0.25rem 0;
        color: var(--primary-color);
    }
    .order-header p {
        margin: 0;
        font-size: 0.9rem;
        color: #6c757d;
    }
    .order-status {
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-weight: bold;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .status-pending {
        background-color: #fff3cd;
        color: #856404;
    }
    .status-approved {
        background-color: #d1ecf1;
        color: #0c5460;
    }
    .status-completed {
        background-color: #d4edda;
        color: #155724;
    }
    .status-cancelled {
        background-color: #f8d7da;
        color: #721c24;
    }
    .order-body {
        padding: 1.5rem;
    }
    .order-body h4 {
        margin-bottom: 1rem;
    }
    .order-items-table {
        width: 100%;
        border: none;
    }
    .order-items-table th, .order-items-table td {
        text-align: left;
        padding: 0.75rem;
        border-bottom: 1px solid var(--border-color);
    }
    .order-items-table th {
        background-color: transparent;
        color: var(--dark-text);
        font-size: 0.9rem;
    }
    .order-items-table tr:last-child td {
        border-bottom: none;
    }
    .order-footer {
        padding: 1rem 1.5rem;
        background-color: var(--light-gray);
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 1.1rem;
        border-top: 1px solid var(--border-color);
    }
    .order-actions-form {
        display: flex;
        gap: 0.5rem;
    }
    .order-actions-form button {
        width: auto;
        margin-top: 0;
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }
    .button-approve {
        background-color: var(--primary-color);
    }
    .button-cancel {
        background-color: #c82333;
    }
    .button-sms {
        background-color: #007bff;
    }
    .button-sms:hover {
        background-color: #0056b3;
    }
    .map-link {
        text-decoration: none;
        margin-left: 5px;
    }
    .transport-details {
        background-color: #f0f8ff;
        padding: 10px 15px;
        border-radius: 5px;
        margin-bottom: 1rem;
    }
    .payment-confirmed-badge {
        background-color: #28a745;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: bold;
        font-size: 0.9rem;
        margin: 0 1rem;
        display: inline-block;
    }
</style>