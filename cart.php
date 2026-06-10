<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/config.php';

// Vendor-only page
if ($_SESSION['user_role'] !== 'vendor') {
    header('Location: /dashboard');
    exit;
}

$cart_by_farmer = [];

// Check if cart exists and is not empty
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $cart_product_ids = array_keys($_SESSION['cart']);
 
    // Create placeholders for the IN clause: ?,?,?
    $placeholders = implode(',', array_fill(0, count($cart_product_ids), '?'));

    $db = Database::getInstance();
    $stmt = $db->prepare("
        SELECT p.id, p.name, p.price, p.unit, p.image_url, p.quantity AS stock_quantity,
               f.user_id AS farmer_id, f.farm_name
        FROM products p
        JOIN farmers f ON p.farmer_id = f.user_id
        WHERE p.id IN ($placeholders)
    ");
    $stmt->execute($cart_product_ids);
    $product_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $products_from_db = [];
    foreach ($product_rows as $product) {
        $products_from_db[$product['id']] = $product;
    } 

    foreach ($_SESSION['cart'] as $product_id => $cart_item) {
        if (isset($products_from_db[$product_id])) {
            $product = $products_from_db[$product_id];

            // Defensive check: Ensure cart item is in the new array format.
            // This handles items added with the old logic, preventing errors.
            if (!is_array($cart_item) || !isset($cart_item['quantity']) || !isset($cart_item['unit'])) {
                unset($_SESSION['cart'][$product_id]); // Clean up the invalid session data
                continue; // Skip to the next item
            }

            $cart_quantity = $cart_item['quantity'];
            $cart_unit = $cart_item['unit'];

            $farmer_id = $product['farmer_id'];
            if (!isset($cart_by_farmer[$farmer_id])) {
                $cart_by_farmer[$farmer_id] = [
                    'farm_name' => $product['farm_name'],
                    'items' => [],
                    'total' => 0
                ];
            }

            // To calculate price, convert both product price and cart quantity to a common base (kg)
            $price_per_kg = $product['price'] / (CONVERSION_FACTORS[$product['unit']] ?? 1);
            $cart_quantity_in_kg = $cart_quantity * (CONVERSION_FACTORS[$cart_unit] ?? 1);

            $subtotal = $price_per_kg * $cart_quantity_in_kg;

            // For display, we use the user's chosen quantity and unit
            $product['cart_quantity'] = $cart_quantity;
            $product['cart_unit'] = $cart_unit;
            $product['subtotal'] = $subtotal;
            $cart_by_farmer[$farmer_id]['items'][] = $product;
            $cart_by_farmer[$farmer_id]['total'] += $subtotal;
        } else {
            // Product might have been removed from the database, so we remove it from the cart
            unset($_SESSION['cart'][$product_id]);
        }
    }
}
?>

<div class="container">
    <div class="page-header">
        <h1>Shopping Cart</h1>
        <a href="/dashboard" class="button-back">&larr; Back to Dashboard</a>
    </div>

    <?php if (isset($_SESSION['cart_error'])): ?>
        <p class="error"><?php echo $_SESSION['cart_error']; unset($_SESSION['cart_error']); ?></p>
    <?php endif; ?>

    <?php if (empty($cart_by_farmer)): ?>
        <div class="empty-cart-page" style="min-height: 0; padding: 2rem 0;">
            <div class="empty-cart-card">
                <div class="icon-container">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                </div>
                <h1>Your Cart is Empty</h1>
                <p class="empty-cart-message">Looks like you haven't added any produce to your cart yet. Start browsing to find fresh goods from local farmers.</p>
                <div class="action-buttons">
                    <a href='/browse' class='button primary-btn'>Continue Shopping</a>
                </div>
            </div>
        </div>
        <style>
            .empty-cart-page { display: flex; justify-content: center; align-items: center; text-align: center; }
            .empty-cart-card { background: white; padding: 3rem; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); max-width: 500px; width: 100%; border-top: 5px solid #ffc107; }
            .empty-cart-card .icon-container { color: #ffc107; margin-bottom: 1.5rem; }
            .empty-cart-card .icon-container svg { width: 80px; height: 80px; }
            .empty-cart-card h1 { color: #343a40; margin-bottom: 1rem; font-size: 1.8rem; margin-top: 0; }
            .empty-cart-message { color: #666; margin-bottom: 2rem; line-height: 1.5; }
            .action-buttons .primary-btn { background-color: #28a745; color: white; padding: 12px 20px; border-radius: 50px; text-decoration: none; font-weight: bold; transition: background 0.3s; display: block; width: 100%; box-sizing: border-box; }
            .action-buttons .primary-btn:hover { background-color: #218838; }
        </style>
    <?php else: ?>
        <?php foreach ($cart_by_farmer as $farmer_id => $farmer_cart): ?>
            <div class="farmer-cart-group">
                <h2 class="farmer-cart-header">Order from: <?php echo htmlspecialchars($farmer_cart['farm_name']); ?></h2>
                <div class="table-responsive">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th colspan="2">Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($farmer_cart['items'] as $item): ?>
                            <tr>
                                <td class="cart-product-image"><img src="<?php echo htmlspecialchars($item['image_url'] ?: 'https://via.placeholder.com/100'); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>"></td>
                                <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                                <td><?php echo number_format($item['price'], 2); ?> / <?php echo htmlspecialchars($item['unit']); ?></td>
                                <td class="quantity-update-cell">
                                    <form method="POST" action="/cart/update">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                        <input type="number" name="quantity" value="<?php echo htmlspecialchars($item['cart_quantity']); ?>" min="1" class="quantity-input">
                                        <span><?php echo htmlspecialchars($item['cart_unit']); ?>(s)</span>
                                        <button type="submit" class="action-button button-edit">Update</button>
                                    </form>
                                </td>
                                <td><strong><?php echo number_format($item['subtotal'], 2); ?></strong></td>
                                <td class="actions-cell">
                                    <a href="/cart/remove?id=<?php echo $item['id']; ?>" class="action-button button-delete">Remove</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>

                <div class="cart-summary">
                    <div class="cart-total">
                        <h3>Total for this farmer: <span><?php echo number_format($farmer_cart['total'], 2); ?></span></h3>
                    </div>
                    <div class="cart-actions">
                        <form action="/checkout" method="POST">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="farmer_id" value="<?php echo $farmer_id; ?>">
                            <button type="submit" class="action-button button-checkout">Checkout from this Farmer</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<style>
    .farmer-cart-group {
        margin-bottom: 3rem;
        background-color: #fff;
        padding: 1.5rem;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
    }
    .farmer-cart-header {
        color: var(--primary-color);
        border-bottom: 2px solid var(--light-gray);
        padding-bottom: 0.5rem;
        margin-top: 0;
    }
    .cart-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .actions-cell {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .button-edit {
        background-color: #28a745;
        /* Bootstrap success color */
        color: white;
        padding: 8px 12px;
        border-radius: 5px;
        text-decoration: none;
        font-size: 0.85em;
    }

    .button-edit:hover {
        background-color: #218838;
    }

    .quantity-update-cell form {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .quantity-input {
        width: 70px;
        padding: 8px;
        border-radius: 5px;
        border: 1px solid var(--border-color);
    }
</style>

<?php require_once __DIR__ . '/footer.php'; ?>