<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Product.php';
require_once __DIR__ . '/config.php';

class OrderController {
    
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function checkout() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /cart');
            exit();
        }

        verify_csrf();

        // Ensure vendor is logged in
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'vendor') {
            header('Location: /login');
            exit;
        }

        // Check if the cart is empty
        if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
            require_once __DIR__ . '/header.php';
            ?>
            <div class="container empty-cart-page">
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
                .empty-cart-page { display: flex; justify-content: center; align-items: center; min-height: 60vh; text-align: center; }
                .empty-cart-card { background: white; padding: 3rem; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); max-width: 500px; width: 100%; border-top: 5px solid #ffc107; }
                .empty-cart-card .icon-container { color: #ffc107; margin-bottom: 1.5rem; }
                .empty-cart-card .icon-container svg { width: 80px; height: 80px; }
                .empty-cart-card h1 { color: #343a40; margin-bottom: 1rem; font-size: 1.8rem; margin-top: 0; }
                .empty-cart-message { color: #666; margin-bottom: 2rem; line-height: 1.5; }
                .action-buttons .primary-btn { background-color: #28a745; color: white; padding: 12px 20px; border-radius: 50px; text-decoration: none; font-weight: bold; transition: background 0.3s; display: block; width: 100%; box-sizing: border-box; }
                .action-buttons .primary-btn:hover { background-color: #218838; }
            </style>
            <?php
            require_once __DIR__ . '/footer.php';
            exit();
        }

        $farmer_id = filter_input(INPUT_POST, 'farmer_id', FILTER_VALIDATE_INT);
        $payment_method = $_POST['payment_method'] ?? '';
        $order_total = filter_input(INPUT_POST, 'order_total', FILTER_VALIDATE_FLOAT);

        // Validate payment method
        if (!in_array($payment_method, ['mobile_money', 'cash_on_delivery'])) {
            $error = "Invalid payment method.";
            require_once __DIR__ . '/cart.php';
            exit();
        }

        if (!$farmer_id) {
            $error = "Invalid farmer specified for checkout.";
            require_once __DIR__ . '/cart.php';
            exit();
        }

        // Create the order
        $vendor_id = $_SESSION['user_id'];

        // Begin transaction
        $this->db->beginTransaction();

        try {
            // 1. Create the order
            $stmt = $this->db->prepare("INSERT INTO orders (vendor_id, payment_method, status) VALUES (:vendor_id, :payment_method, 'pending')");
            $stmt->execute([
                'vendor_id' => $vendor_id,
                'payment_method' => $payment_method
            ]);
            $order_id = $this->db->lastInsertId();

            // 2. Add order items
            $processed_product_ids = [];
            
            foreach ($_SESSION['cart'] as $product_id => $details) {
                $quantity = $details['quantity'];
                $unit = $details['unit'];

                // Fetch product details
                $stmt = $this->db->prepare("SELECT id, price, farmer_id, quantity, unit FROM products WHERE id = :product_id");
                $stmt->execute(['product_id' => $product_id]);
                $product = $stmt->fetch();

                // Only process items for the specified farmer
                if ($product && $product['farmer_id'] == $farmer_id) {
                    $processed_product_ids[] = $product_id;
                    $price_at_purchase = $product['price'];

                    // Insert into order_items
                    $stmt = $this->db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase, unit) VALUES (:order_id, :product_id, :quantity, :price_at_purchase, :unit)");
                    $stmt->execute([
                        'order_id' => $order_id,
                        'product_id' => $product_id,
                        'quantity' => $quantity,
                        'price_at_purchase' => $price_at_purchase,
                        'unit' => $unit
                    ]);

                    // Update Stock
                    $product_stock_kg = $product['quantity'] * (CONVERSION_FACTORS[$product['unit']] ?? 1);
                    $purchase_kg = $quantity * (CONVERSION_FACTORS[$unit] ?? 1);
                    $new_stock_kg = max(0, $product_stock_kg - $purchase_kg);
                    $product_unit_factor = CONVERSION_FACTORS[$product['unit']] ?? 1;
                    $new_stock_native = $new_stock_kg / $product_unit_factor;

                    (new Product())->updateStock($product_id, $new_stock_native);
                }
            }

            if (empty($processed_product_ids)) {
                throw new Exception("No items found in cart for this farmer.");
            }

            $this->db->commit();

            // Clear processed items
            foreach($processed_product_ids as $id) {
                unset($_SESSION['cart'][$id]);
            }

            require_once __DIR__ . '/header.php';
            ?>
            <div class="container success-page">
                <div class="success-card">
                    <div class="icon-container">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    </div>
                    <h1>Order Placed Successfully!</h1>
                    <p class="order-total">Total Amount: <strong>UGX <?php echo number_format($order_total, 2); ?></strong></p>
                    <p class="order-message">Thank you for supporting our farmers. Your order has been sent to the farmer for approval.</p>
                    <div class="action-buttons">
                        <a href='/browse' class='button primary-btn'>Continue Shopping</a>
                        <a href='/my-orders' class='button secondary-btn'>View My Orders</a>
                    </div>
                </div>
            </div>
            <style>
                .success-page { display: flex; justify-content: center; align-items: center; min-height: 60vh; text-align: center; }
                .success-card { background: white; padding: 3rem; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); max-width: 500px; width: 100%; border-top: 5px solid #28a745; }
                .icon-container { color: #28a745; margin-bottom: 1.5rem; }
                .icon-container svg { width: 80px; height: 80px; }
                .success-card h1 { color: #28a745; margin-bottom: 1rem; font-size: 1.8rem; margin-top: 0; }
                .order-total { font-size: 1.2rem; color: #333; margin-bottom: 0.5rem; }
                .order-message { color: #666; margin-bottom: 2rem; line-height: 1.5; }
                .action-buttons { display: flex; flex-direction: column; gap: 1rem; }
                .primary-btn { background-color: #28a745; color: white; padding: 12px 20px; border-radius: 50px; text-decoration: none; font-weight: bold; transition: background 0.3s; display: block; width: 100%; box-sizing: border-box; }
                .primary-btn:hover { background-color: #218838; }
                .secondary-btn { background-color: white; color: #28a745; border: 2px solid #28a745; padding: 10px 20px; border-radius: 50px; text-decoration: none; font-weight: bold; transition: all 0.3s; display: block; width: 100%; box-sizing: border-box; }
                .secondary-btn:hover { background-color: #f0f9f0; }
            </style>
            <?php
            require_once __DIR__ . '/footer.php';
            exit;
        } catch (Exception $e) {
            $this->db->rollBack();
            require_once __DIR__ . '/header.php';
            ?>
            <div class="container success-page">
                <div class="success-card" style="border-top-color: #dc3545;">
                    <div class="icon-container" style="color: #dc3545;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    </div>
                    <h1 style="color: #dc3545;">Order Failed</h1>
                    <p class="order-message">An error occurred while processing your order:<br><strong><?php echo htmlspecialchars($e->getMessage()); ?></strong></p>
                    <div class="action-buttons">
                        <a href='/cart' class='button primary-btn' style="background-color: #dc3545;">Back to Cart</a>
                    </div>
                </div>
            </div>
            <!-- Reusing styles from success block above implicitly or explicitly included if both share a stylesheet, but inline styles here ensure it works standalone -->
            <style>
                .success-page { display: flex; justify-content: center; align-items: center; min-height: 60vh; text-align: center; }
                .success-card { background: white; padding: 3rem; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); max-width: 500px; width: 100%; border-top: 5px solid #28a745; }
                .icon-container svg { width: 80px; height: 80px; }
                .action-buttons { display: flex; flex-direction: column; gap: 1rem; }
                .primary-btn { color: white; padding: 12px 20px; border-radius: 50px; text-decoration: none; font-weight: bold; display: block; width: 100%; box-sizing: border-box; }
            </style>
            <?php
            require_once __DIR__ . '/footer.php';
        }
    }

    public function updateStatus() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             header('Location: /orders');
             exit;
        }
        verify_csrf();

        // 1. Verify user is farmer
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'farmer') {
            header('Location: /login');
            exit();
        }

        $order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
        $status = $_POST['status'] ?? '';
        $farmer_id = $_SESSION['user_id'];
        
        // 2. Verify this order contains products from this farmer
        $stmt = $this->db->prepare("
            SELECT o.id FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.id
            WHERE o.id = :order_id AND p.farmer_id = :farmer_id
            LIMIT 1
        ");
        $stmt->execute(['order_id' => $order_id, 'farmer_id' => $farmer_id]);
        
        if ($stmt->fetch() && in_array($status, ['approved', 'cancelled'])) {
            $updateStmt = $this->db->prepare("UPDATE orders SET status = :status WHERE id = :order_id");
            $updateStmt->execute(['status' => $status, 'order_id' => $order_id]);
        }

        header('Location: /orders');
        exit();
    }

    public function confirmPayment() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             header('Location: /orders');
             exit;
        }
        verify_csrf();

        // 1. Verify user is a farmer
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'farmer') {
            header('Location: /login');
            exit();
        }

        $order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
        $farmer_id = $_SESSION['user_id'];

        if (!$order_id) {
            header('Location: /orders');
            exit();
        }

        // 2. Verify this order contains products from this farmer
        $stmt = $this->db->prepare("
            SELECT o.id FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.id
            WHERE o.id = :order_id AND p.farmer_id = :farmer_id
            LIMIT 1
        ");
        $stmt->execute(['order_id' => $order_id, 'farmer_id' => $farmer_id]);
        
        if ($stmt->fetch()) {
            $updateStmt = $this->db->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = :order_id");
            $updateStmt->execute(['order_id' => $order_id]);
        }

        header('Location: /orders');
        exit();
    }
}