<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Product.php';

// Vendor-only page
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'vendor') {
    header('Location: /dashboard');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header('Location: /cart');
    exit;
}

verify_csrf();

$db = Database::getInstance();
$vendor_id = $_SESSION['user_id'];
$cart = $_SESSION['cart'] ?? [];
$transports = $_POST['transport'] ?? [];
$payment_method = $_POST['payment_method'] ?? '';
$transaction_id = $_POST['transaction_id'] ?? null;

if (empty($cart) || empty($transports) || empty($payment_method)) {
    header('Location: /checkout');
    exit;
}

// 1. Get all product details from cart at once
$productIds = array_keys($cart);
$placeholders = implode(',', array_fill(0, count($productIds), '?'));
$stmt = $db->prepare("SELECT id, name, price, unit, farmer_id, quantity AS stock_quantity FROM products WHERE id IN ($placeholders)");
$stmt->execute($productIds);
$productsFromDb = $stmt->fetchAll(PDO::FETCH_ASSOC);

$productsByFarmer = [];
foreach ($productsFromDb as $product) {
    $pId = $product['id'];
    if (isset($cart[$pId])) {
        $product['cart_quantity'] = $cart[$pId]['quantity'];
        $product['cart_unit'] = $cart[$pId]['unit'];
        $productsByFarmer[$product['farmer_id']][] = $product;
    }
}

$createdOrdersInfo = [];

// 2. Loop through each farmer and create an order
foreach ($productsByFarmer as $farmer_id => $items) {
    $db->beginTransaction();
    try {
        $transport_type = $transports[$farmer_id]['type'] ?? 'self';
        $transport_info = ($transport_type === 'hired') ? ($transports[$farmer_id]['details'] ?? '') : null;
        $payment_status = ($payment_method === 'mobile_money') ? 'pending' : 'not_required';

        $stmt = $db->prepare(
            "INSERT INTO orders (vendor_id, payment_method, transaction_id, status, payment_status, transport_type, transport_info) 
             VALUES (:vendor_id, :payment_method, :transaction_id, 'pending', :payment_status, :transport_type, :transport_info)"
        );
        $stmt->execute([
            'vendor_id' => $vendor_id,
            'payment_method' => $payment_method,
            'transaction_id' => $transaction_id,
            'payment_status' => $payment_status,
            'transport_type' => $transport_type,
            'transport_info' => $transport_info
        ]);
        $order_id = $db->lastInsertId();
        $orderTotal = 0;

        foreach ($items as $item) {
            $price_per_kg = $item['price'] / (CONVERSION_FACTORS[$item['unit']] ?? 1);
            $cart_quantity_in_kg = $item['cart_quantity'] * (CONVERSION_FACTORS[$item['cart_unit']] ?? 1);
            $subtotal = $price_per_kg * $cart_quantity_in_kg;
            $orderTotal += $subtotal;
            $price_at_purchase = $price_per_kg * (CONVERSION_FACTORS[$item['cart_unit']] ?? 1);

            $stmt = $db->prepare(
                "INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase, unit) 
                 VALUES (:order_id, :product_id, :quantity, :price_at_purchase, :unit)"
            );
            $stmt->execute([
                'order_id' => $order_id,
                'product_id' => $item['id'],
                'quantity' => $item['cart_quantity'],
                'price_at_purchase' => $price_at_purchase,
                'unit' => $item['cart_unit']
            ]);

            $product_stock_kg = $item['stock_quantity'] * (CONVERSION_FACTORS[$item['unit']] ?? 1);
            $new_stock_kg = max(0, $product_stock_kg - $cart_quantity_in_kg);
            $new_stock_native = $new_stock_kg / (CONVERSION_FACTORS[$item['unit']] ?? 1);
            (new Product())->updateStock($item['id'], $new_stock_native);
        }

        $stmt = $db->prepare("UPDATE orders SET total_amount = :total WHERE id = :id");
        $stmt->execute(['total' => $orderTotal, 'id' => $order_id]);

        $db->commit();
        $createdOrdersInfo[$farmer_id] = [
            'order_id' => $order_id,
            'transport_type' => $transport_type,
            'transport_info' => $transport_info
        ];
    } catch (Exception $e) {
        $db->rollBack();
        echo "<div class='container'><div class='error'>An error occurred while placing an order. Please try again. Error: " . htmlspecialchars($e->getMessage()) . "</div></div>";
        require_once __DIR__ . '/footer.php';
        exit;
    }
}

// Clear cart after all orders are processed
$_SESSION['cart'] = [];
?>
<div class="container">
    <h1>Order Placed Successfully!</h1>
    <div class="success">Thank you! Your order(s) have been forwarded to the respective farmers.</div>

    <?php if ($payment_method == 'mobile_money'): ?>
        <div class="info">
            <strong>Payment Method:</strong> Mobile Money<br>
            <strong>Next Step:</strong> Please send payment to the farmer's contact number for each order. The farmer will confirm receipt before transport can proceed.
            <?php if ($transaction_id): ?>
                <br><strong>Your Transaction ID:</strong> <?php echo htmlspecialchars($transaction_id); ?>
            <?php endif; ?>
        </div>
    <?php elseif ($payment_method == 'cash'): ?>
        <p class="info"><strong>Payment Method:</strong> Cash on Delivery <br>Please pay the farmer upon receipt of goods.</p>
    <?php endif; ?>

    <h3>Transport Arrangements</h3>
    <div class="transport-summary">
        <?php foreach ($createdOrdersInfo as $farmer_id => $details): ?>
            <div class="transport-item">
                <strong>For Order #<?php echo $details['order_id']; ?>:</strong><br>
                <?php if ($details['transport_type'] === 'hired'): ?>
                    <strong>Delivery via:</strong> <?php echo htmlspecialchars($details['transport_info']); ?>
                    <br><span style="color:green; font-size:0.9rem;">&#10003; Please contact this driver immediately to arrange pickup.</span>
                <?php else: ?>
                    <strong>Self Pickup:</strong> You have chosen to pick up the items yourself.
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    
    <div style="margin-top: 2rem;"><a href="/dashboard" class="button primary">Return to Dashboard</a></div>
</div>
<?php require_once __DIR__ . '/footer.php'; ?>

<style>
 .success {
 color: #155724;
 background-color: #d4edda;
 border: 1px solid #c3e6cb;
 padding: 0.75rem 1.25rem;
 margin-bottom: 1rem;
 border-radius: 0.25rem;
 }

 .info {
 color: #0c5460;
 background-color: #d1ecf1;
 border: 1px solid #bee5eb;
 padding: 0.75rem 1.25rem;
 margin-bottom: 1rem;
 border-radius: 0.25rem;
 }

 .container {
 max-width: 800px;
 margin: 20px auto;
 padding: 20px;
 background-color: #f8f9fa;
 border-radius: 8px;
 }

 .transport-summary {
     background: #fff;
     border: 1px solid #ddd;
     border-radius: 4px;
     padding: 15px;
 }
 .transport-item {
     padding: 10px;
     border-bottom: 1px solid #eee;
 }
 .transport-item:last-child {
     border-bottom: none;
 }