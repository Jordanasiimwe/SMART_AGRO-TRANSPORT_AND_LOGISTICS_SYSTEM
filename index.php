<?php
session_start();

// Initialize CSRF token if not present
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Basic Autoloader
// We will load classes directly since they are in the same folder.
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Env.php';

// Load Environment Variables
Env::load(__DIR__ . '/.env');

require_once __DIR__ . '/User.php';
require_once __DIR__ . '/config.php';

// Helper function for API responses.
// Defined globally to be accessible by all routes and to avoid re-declaration.
if (!function_exists('send_json_response')) {
    function send_json_response(array $data, int $statusCode = 200) {
        // Aggressively clean up any output buffers to prevent stray content from PHP warnings or included files.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit();
    }
}

// --- Global Error Handler ---
set_exception_handler(function ($e) {
    // Log the error
    error_log($e->getMessage());
    
    http_response_code(500);
    echo "<div style='padding:20px;text-align:center;font-family:sans-serif;'>";
    echo "<h1>System Error</h1>";
    echo "<p>An unexpected error occurred.</p>";
    // Show the actual error message for debugging purposes
    echo "<div style='background:#f8d7da; color:#721c24; padding:15px; border-radius:5px; display:inline-block; text-align:left; max-width:800px;'>";
    echo "<strong>Debug Info:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>File:</strong> " . $e->getFile() . " on line " . $e->getLine();
    echo "</div></div>";
    exit();
});


// --- Maintenance Mode Check ---
$maintenance_flag_file = __DIR__ . '/maintenance.flag';
$is_maintenance_mode = file_exists($maintenance_flag_file);

if ($is_maintenance_mode) {
    $is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    $requestUriForMaintenance = strtok($_SERVER["REQUEST_URI"], '?');
    // $requestUriForMaintenance = str_replace('/uict', '', $requestUriForMaintenance);

    // Allow admin access to all pages, and allow anyone to access the login page.
    // This ensures an admin can log in to disable maintenance mode.
    if (!$is_admin && $requestUriForMaintenance !== '/login') {
        http_response_code(503); // Service Unavailable
        echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="SmartAgro-Link System is a digital platform designed to connect farmers directly with vendors in Nakawa Market. The system enables farmers to showcase available agricultural products, while vendors can easily search, order, and communicate with suppliers in real time. By reducing intermediaries, SmartAgro-Link System improves market access, enhances transparency, promotes fair pricing, and streamlines the agricultural supply chain.">
    <meta name="keywords" content="SmartAgro-Link, agriculture, farmers, vendors, Nakawa Market, agricultural products, supply chain, fair pricing">
    <meta property="og:title" content="System Maintenance">
    <meta property="og:description" content="SmartAgro-Link System is a digital platform designed to connect farmers directly with vendors in Nakawa Market. The system enables farmers to showcase available agricultural products, while vendors can easily search, order, and communicate with suppliers in real time. By reducing intermediaries, SmartAgro-Link System improves market access, enhances transparency, promotes fair pricing, and streamlines the agricultural supply chain.">
    <meta property="og:image" content="/images/smart.jpg">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="System Maintenance">
    <meta name="twitter:description" content="SmartAgro-Link System is a digital platform designed to connect farmers directly with vendors in Nakawa Market. The system enables farmers to showcase available agricultural products, while vendors can easily search, order, and communicate with suppliers in real time. By reducing intermediaries, SmartAgro-Link System improves market access, enhances transparency, promotes fair pricing, and streamlines the agricultural supply chain.">
    <meta name="twitter:image" content="/images/smart.jpg">
    <title>System Maintenance</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background-color: #f8f9fa; color: #343a40; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; text-align: center; }
        .maintenance-container { max-width: 600px; padding: 2rem; }
        .maintenance-icon { width: 80px; height: 80px; color: #ffc107; margin-bottom: 1.5rem; animation: spin 4s linear infinite; }
        h1 { font-size: 2.5rem; color: #343a40; margin-bottom: 1rem; }
        p { font-size: 1.2rem; color: #6c757d; line-height: 1.6; }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <svg class="maintenance-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
        </svg>
        <h1>System is Undergoing Maintenance</h1>
        <p>We are sorry for the inconveniences. We shall be back as soon as possible.</p>
    </div>
</body>
</html>
HTML;
        exit();
    }
}
// --- End Maintenance Mode Check ---

// Update user activity if logged in
if (isset($_SESSION['user_id'])) {
    (new User())->updateLastActivity($_SESSION['user_id']);
}

// Simple Router
$requestUri = strtok($_SERVER["REQUEST_URI"], '?');
// $requestUri = str_replace('/uict', '', $requestUri); // Remove subfolder from URI (Not needed on InfinityFree root)

// Ensure empty URI maps to home
if ($requestUri === '') $requestUri = '/';

switch ($requestUri) {
    case '/':
        require_once __DIR__ . '/public_home.php';
        break;

    case '/login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            // Handle login logic
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            $userModel = new User();
            $loggedInUser = $userModel->login($username, $password);

            if ($loggedInUser && isset($loggedInUser['status']) && $loggedInUser['status'] === 'inactive') {
                $error = "Your account has been deactivated. Please contact the admin.";
                require_once __DIR__ . '/login.php';
                break; // Stop execution for this case
            }

            if ($loggedInUser) {
                // Prevent Session Fixation
                session_regenerate_id(true);

                $_SESSION['user_id'] = $loggedInUser['id'];
                $_SESSION['user_role'] = $loggedInUser['role_name'];
                $_SESSION['username'] = $loggedInUser['username'];
                
                // Redirect to a future dashboard
                header('Location: /dashboard');
                exit();
            } else {
                $error = "Invalid username or password.";
                require_once __DIR__ . '/login.php';
            }
        } else {
            // Show login form
            require_once __DIR__ . '/login.php';
        }
        break;

    case '/forgot-password':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';

            $userModel = new User();
            
            if (empty($password) || $password !== $confirm) {
                $error = "Passwords do not match or are empty.";
                require_once __DIR__ . '/forgot-password.php';
            } else {
                if ($userModel->resetPasswordByEmail($email, $password)) {
                    header('Location: /login?reset=success');
                    exit();
                } else {
                    $error = "No account found with that email address.";
                    require_once __DIR__ . '/forgot-password.php';
                }
            }
        } else {
            require_once __DIR__ . '/forgot-password.php';
        }
        break;

    case '/reset-password':
        header('Location: /forgot-password');
        exit();
        break;

    case '/register':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            // Handle registration logic
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $email = $_POST['email'] ?? '';
            $role_id = $_POST['role'] ?? '';
            $sacco = $_POST['sacco'] ?? '';

            // Validate data (basic checks)
            if (empty($username) || empty($password) || empty($email) || !in_array($role_id, ['2', '3'])) {
                $error = "Please fill in all fields correctly.";
                require_once __DIR__ . '/register.php';
                break;
            }

            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert the user into the database
            $db = Database::getInstance();
            $stmt = $db->prepare("
                INSERT INTO users (username, password_hash, email, role_id)
                VALUES (:username, :password_hash, :email, :role_id)
            ");
            $stmt->execute([
                'username' => $username,
                'password_hash' => $hashedPassword,
                'email' => $email,
                'role_id' => $role_id
            ]);

            $user_id = $db->lastInsertId();

            // Create farmer/vendor profile
            if ($role_id == 2) {
                $stmt = $db->prepare("INSERT INTO farmers (user_id, farm_name) VALUES (:user_id, '')");
                $stmt->execute(['user_id' => $user_id]);
            } else {
                $stmt = $db->prepare("INSERT INTO vendors (user_id, market_stall_id, sacco) VALUES (:user_id, '', :sacco)");
                $stmt->execute(['user_id' => $user_id, 'sacco' => $sacco]);
            }

            header('Location: /login');
            exit();
        } else {
            require_once __DIR__ . '/register.php';
        }
        break;
    case '/dashboard':
        // Protect this route - only logged-in users can see it
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit();
        }

        // Route to the correct dashboard based on user role
        $role = $_SESSION['user_role'] ?? 'guest';

        switch ($role) {
            case 'admin':
                require_once __DIR__ . '/admin_dashboard.php';
                break;
            case 'farmer':
                require_once __DIR__ . '/farmer_dashboard.php';
                break;
            case 'vendor':
                require_once __DIR__ . '/vendor_dashboard.php';
                break;
            default:
                // If role is unknown, log them out
                header('Location: /login');
                exit();
        }
        break;


    case '/products':
        require_once __DIR__ . '/ProductController.php';
        (new ProductController())->index();
        break;

    case '/products/add':
        require_once __DIR__ . '/ProductController.php';
        (new ProductController())->add();
        break;

    case '/products/create':
        require_once __DIR__ . '/ProductController.php';
        (new ProductController())->create();
        break;

    case '/products/edit':
        require_once __DIR__ . '/ProductController.php';
        (new ProductController())->edit();
        break;

    case '/products/update':
        require_once __DIR__ . '/ProductController.php';
        (new ProductController())->update();
        break;

    case '/products/delete':
        require_once __DIR__ . '/ProductController.php';
        (new ProductController())->delete();
        break;

    case '/profile/edit':
        require_once __DIR__ . '/profile-edit.php';
        break;

    case '/profile/update':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['farmer', 'vendor'])) {
                header('Location: /login');
                exit();
            }

            require_once __DIR__ . '/User.php';
            $userModel = new User();
            $userId = $_SESSION['user_id'];
            $role = $_SESSION['user_role'];
            $username = $_POST['username'] ?? $_SESSION['username'];

            // Update username if it has changed
            if ($username !== $_SESSION['username']) {
                $userModel->updateUsername($userId, $username);
                $_SESSION['username'] = $username; // Update session
            }

            if ($role === 'farmer') {
                $farmName = $_POST['farm_name'] ?? '';
                $location = $_POST['location'] ?? '';
                $contact = $_POST['contact'] ?? '';
                $sacco = $_POST['sacco'] ?? null;
                $latitude = filter_input(INPUT_POST, 'latitude', FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
                $longitude = filter_input(INPUT_POST, 'longitude', FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
                $userModel->updateFarmerProfile($userId, $farmName, $location, $contact, $sacco, $latitude, $longitude);
            } elseif ($role === 'vendor') {
                $marketStallId = $_POST['market_stall_id'] ?? '';
                $sacco = $_POST['sacco'] ?? '';
                $contact = $_POST['contact'] ?? '';
                $userModel->updateVendorProfile($userId, $marketStallId, $sacco, $contact);
            }

            header('Location: /dashboard');
            exit();
        }
        header('Location: /dashboard');
        exit();
        break;

    case '/profile/view':
        require_once __DIR__ . '/profile-view.php';
        break;

    case '/orders':
        require_once __DIR__ . '/orders.php';
        break;

    case '/orders/confirm-payment':
        require_once __DIR__ . '/OrderController.php';
        (new OrderController())->confirmPayment();
        break;

    case '/orders/update-status':
        require_once __DIR__ . '/OrderController.php';
        (new OrderController())->updateStatus();
        break;

    case '/browse':
        require_once __DIR__ . '/browse.php';
        break;

    case '/cart/add':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            // Only vendors can add to cart
            if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'vendor') {
                header('Location: /login');
                exit();
            }

            $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
            $requested_quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
            $requested_unit = $_POST['unit'] ?? '';

            // Basic validation
            if (!$product_id || !$requested_quantity || $requested_quantity <= 0 || !in_array($requested_unit, ['kg', 'basin', 'sack', 'whole', 'tray'])) {
                // TODO: Add a user-facing error message
                header('Location: /browse');
                exit();
            }

            require_once __DIR__ . '/Product.php';
            require_once __DIR__ . '/config.php';
            $productModel = new Product();
            $product = $productModel->findById($product_id);

            if (!$product) {
                header('Location: /browse');
                exit();
            }

            // Convert everything to KG to check stock
            $product_stock_in_kg = $product['quantity'] * (CONVERSION_FACTORS[$product['unit']] ?? 1);
            $requested_quantity_in_kg = $requested_quantity * (CONVERSION_FACTORS[$requested_unit] ?? 1);

            if ($requested_quantity_in_kg > $product_stock_in_kg) {
                // TODO: Add a user-facing error message for out of stock
                header('Location: /browse');
                exit();
            }

            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }

            // Add item to cart with quantity and unit. This overwrites existing entries for the same product.
            $_SESSION['cart'][$product_id] = [
                'quantity' => $requested_quantity,
                'unit' => $requested_unit
            ];

            $_SESSION['cart_message'] = $product_id;

        }
        // Redirect back to the browse page for GET requests or after POST processing
        header('Location: /browse');
        exit();
        break;

    case '/cart/update':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            // Only vendors can update cart
            if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'vendor') {
                header('Location: /login');
                exit();
            }

            $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
            $new_quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

            // Check if product is in cart and quantity is valid
            if (!$product_id || !$new_quantity || $new_quantity <= 0 || !isset($_SESSION['cart'][$product_id])) {
                header('Location: /cart');
                exit();
            }

            $unit_in_cart = $_SESSION['cart'][$product_id]['unit'];

            require_once __DIR__ . '/Product.php';
            require_once __DIR__ . '/config.php';
            $productModel = new Product();
            $product = $productModel->findById($product_id);

            if (!$product) {
                unset($_SESSION['cart'][$product_id]); // Product removed from DB
                header('Location: /cart');
                exit();
            }

            // Convert everything to KG to check stock
            $product_stock_in_kg = $product['quantity'] * (CONVERSION_FACTORS[$product['unit']] ?? 1);
            $requested_quantity_in_kg = $new_quantity * (CONVERSION_FACTORS[$unit_in_cart] ?? 1);

            if ($requested_quantity_in_kg > $product_stock_in_kg) {
                $_SESSION['cart_error'] = "Not enough stock for '{$product['name']}'. Only {$product['quantity']} {$product['unit']} available.";
            } else {
                // Update quantity in cart
                $_SESSION['cart'][$product_id]['quantity'] = $new_quantity;
            }
        }
        header('Location: /cart');
        exit();
        break;

    case '/cart/remove':
        if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'vendor') {
            $product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if ($product_id && isset($_SESSION['cart'][$product_id])) {
                unset($_SESSION['cart'][$product_id]);
            }
        }
        // Redirect back to the cart to see the change
        header('Location: /cart');
        exit();
        break;

    case '/cart/clear':
        if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'vendor') {
            if (isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
        }
        // Redirect back to the cart to see the change
        header('Location: /cart');
        exit();
        break;

    case '/cart':
        require_once __DIR__ . '/cart.php';
        break;

    case '/process-payment':
        require_once __DIR__ . '/process-payment.php';
        break;

    case '/checkout':
        require_once __DIR__ . '/checkout.php';
        break;
     case '/api/cart/get':
        // API endpoint to get cart HTML
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'vendor') {
          require_once __DIR__ . '/Database.php';
          require_once __DIR__ . '/config.php';

          $cart_items = [];
          $total_price = 0;

          if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
            $cart_product_ids = array_keys($_SESSION['cart']);

            // Create placeholders for the IN clause: ?,?,?
            $placeholders = implode(',', array_fill(0, count($cart_product_ids), '?'));

            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT p.id, p.name, p.price, p.unit, p.image_url, p.quantity AS stock_quantity
                FROM products p
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

                // To calculate price, convert both product price and cart quantity to a common base (kg)
                $price_per_kg = $product['price'] / (CONVERSION_FACTORS[$product['unit']] ?? 1);
                $cart_quantity_in_kg = $cart_quantity * (CONVERSION_FACTORS[$cart_unit] ?? 1);

                $subtotal = $price_per_kg * $cart_quantity_in_kg;
                $total_price += $subtotal;

                // For display, we use the user's chosen quantity and unit
                $product['cart_quantity'] = $cart_quantity;
                $product['cart_unit'] = $cart_unit;
                $product['subtotal'] = $subtotal;
                $cart_items[] = $product;
              } else {
                // Product might have been removed from the database, so we remove it from the cart
                unset($_SESSION['cart'][$product_id]);
              }
            }
          }

          // Output HTML for the cart
          if (empty($cart_items)) {
            echo "<p>Your shopping cart is currently empty.</p>";
          } else {
            echo "<table class='cart-table'>";
            echo "<thead><tr><th colspan='2'>Product</th><th>Price</th><th>Quantity</th><th>Subtotal</th><th>Action</th></tr></thead>";
            echo "<tbody>";
            foreach ($cart_items as $item) {
              echo "<tr>";
              echo "<td class='cart-product-image'><img src='" . htmlspecialchars($item['image_url'] ?: 'https://via.placeholder.com/100') . "' alt='" . htmlspecialchars($item['name']) . "'></td>";
              echo "<td><strong>" . htmlspecialchars($item['name']) . "</strong></td>";
              echo "<td>" . number_format($item['price'], 2) . " / " . htmlspecialchars($item['unit']) . "</td>";
              echo "<td>" . htmlspecialchars($item['cart_quantity']) . " " . htmlspecialchars($item['cart_unit']) . "(s)</td>";
              echo "<td><strong>" . number_format($item['subtotal'], 2) . "</strong></td>";
              echo "<td class='actions-cell'><a href='/cart/remove?id=" . $item['id'] . "' class='action-button button-delete'>Remove</a></td>";
              echo "</tr>";
            }
            echo "</tbody>";
            echo "</table>";

            echo "<div class='cart-summary'>";
            echo "<div class='cart-total'>";
            echo "<h3>Total: <span>" . number_format($total_price, 2) . "</span></h3>";
            echo "</div>";
            echo "</div>";
          }
          exit(); // Stop further script execution
        } else {
          http_response_code(403);
          echo "Access Denied";
          exit();
        }
        break;

    case '/api/send-sms':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 1. Check if user is logged in
            if (!isset($_SESSION['user_id'])) {
                send_json_response(['success' => false, 'message' => 'Authentication required.'], 403);
            }

            // 2. Get and validate input
            $recipient_id = filter_input(INPUT_POST, 'recipient_id', FILTER_VALIDATE_INT);
            $message = $_POST['message'] ?? '';

            if (!$recipient_id || empty(trim($message))) {
                send_json_response(['success' => false, 'message' => 'Recipient and message are required.'], 400);
            }

            require_once __DIR__ . '/User.php';
            require_once __DIR__ . '/SmsService.php';

            $userModel = new User();
            $contactNumber = $userModel->findContactByUserId($recipient_id);

            if (!$contactNumber) {
                // Send a 200 OK response with success:false so the frontend can display the error in the modal.
                send_json_response(['success' => false, 'message' => 'Recipient does not have a valid contact number on file.']);
            }

            $smsService = new SmsService();
            $sender_id = $_SESSION['user_id'];
            $success = $smsService->sendSms($sender_id, $recipient_id, $contactNumber, $message);

            if ($success) {
                send_json_response(['success' => true, 'message' => 'SMS sent successfully!']);
            } else {
                // This error is often due to file permissions on sms_log.txt
                send_json_response(['success' => false, 'message' => 'Failed to send SMS. Check server logs or file permissions.'], 500);
            }
        }
        // Deny GET requests
        send_json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
        break;

    case '/messages':
        require_once __DIR__ . '/messages.php';
        break;

    case '/messages/delete':
        if (isset($_SESSION['user_id'])) {
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if ($id) {
                require_once __DIR__ . '/Message.php';
                // Check ownership/permission before deleting
                $db = Database::getInstance();
                $stmt = $db->prepare("SELECT sender_id, recipient_id FROM messages WHERE id = :id");
                $stmt->execute(['id' => $id]);
                $msg = $stmt->fetch();

                if ($msg && ($_SESSION['user_role'] === 'admin' || $msg['sender_id'] == $_SESSION['user_id'] || $msg['recipient_id'] == $_SESSION['user_id'])) {
                    $msgModel = new Message();
                    if ($msgModel->delete($id)) {
                        $_SESSION['action_success'] = "Message deleted successfully.";
                    } else {
                        $_SESSION['action_error'] = "Failed to delete message.";
                    }
                }
            }
        }
        header('Location: /messages');
        exit();
        break;




   case '/cart/checkout':
      require_once __DIR__ . '/OrderController.php';
      (new OrderController())->checkout();
      break;

    case '/users/delete':
        // Admin only
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: /login');
            exit();
        }
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if ($id && $id !== $_SESSION['user_id']) { // Prevent self-deletion
            $userModel = new User();
            if ($userModel->delete($id)) {
                $_SESSION['action_success'] = "User deleted successfully.";
            } else {
                $_SESSION['action_error'] = "Failed to delete user.";
            }
        }
        // Redirect to the list they came from (defaulting to farmers list if referrer is unclear)
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/users?role=farmer'));
        exit();
        break;
    
    case '/users/status':
        // Admin only - Toggle user status
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: /login');
            exit();
        }
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $status = $_GET['status'] ?? 'active';
        
        if ($id && in_array($status, ['active', 'inactive'])) {
            $userModel = new User();
            $userModel->updateStatus($id, $status);
        }
        // Redirect back
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/dashboard'));
        exit();
        break;

    case '/profile/leave':
        // Allow farmers/vendors to deactivate their own account
        if (isset($_SESSION['user_id']) && ($_SERVER['REQUEST_METHOD'] === 'POST')) {
            verify_csrf();
            $userModel = new User();
            // Set status to inactive
            $userModel->updateStatus($_SESSION['user_id'], 'inactive');
            
            // Destroy session and logout
            session_destroy();
            header('Location: /login?msg=left');
            exit();
        }
        header('Location: /dashboard');
        exit();
        break;

    case '/admin/update-team':
        // Admin only
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403);
            send_json_response(['success' => false, 'message' => 'Unauthorized']);
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            // CSRF for JSON requests would require passing the token in headers or body.
            // For simplicity here, we assume admin session auth is primary guard, or check header if possible.
            
            $index = $data['index'] ?? 0;
            $newName = $data['name'] ?? '';
            
            $userModel = new User();
            if (!empty(trim($newName)) && $userModel->updateTeamMember($index, trim($newName))) {
                 send_json_response(['success' => true]);
            }
        }
        send_json_response(['success' => false]);
        break;

    case '/admin/send-maintenance-sms':
        // Admin only
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: /login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $messageBody = trim($_POST['message'] ?? '');

            if (!empty($messageBody)) {
                require_once __DIR__ . '/User.php';
                require_once __DIR__ . '/SmsService.php';
                
                $userModel = new User();
                // getAllUsersWithContacts() already excludes role_id 1 (Admin)
                $users = $userModel->getAllUsersWithContacts();
                $smsService = new SmsService();
                $sender_id = $_SESSION['user_id'];
                $finalMessage = "System Alert: " . $messageBody;

                foreach ($users as $user) {
                    $smsService->sendSms($sender_id, $user['id'], $user['contact'], $finalMessage);
                }
                header('Location: /dashboard?sms_sent=1');
                exit();
            }
        }
        
        header('Location: /dashboard');
        exit();
        break;

    case '/admin/withdraw-maintenance':
        // Admin only
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: /login');
            exit();
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
            $method = $_POST['method'] ?? '';
            
            if ($amount && $amount > 0) {
                $userModel = new User();
                $userModel->logWithdrawal($amount, $method, $_SESSION['user_id']);
                
                $_SESSION['action_success'] = "Transfer of UGX " . number_format($amount) . " initiated via " . htmlspecialchars($method) . ".";
            }
        }
        header('Location: /dashboard');
        exit();
        break;

    case '/my-orders':
        require_once __DIR__ . '/my-orders.php';
        break;

    case '/users':
        require_once __DIR__ . '/users.php';
        break;

    case '/active-users':
        require_once __DIR__ . '/active_users.php';
        break;

    case '/map-view':
        require_once __DIR__ . '/map-view.php';
        break;

    case '/admin-orders':
        // Admin only
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: /login');
            exit();
        }
        require_once __DIR__ . '/admin-orders.php';
        break;

    case '/search':
        // Admin only
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: /login');
            exit();
        }
        require_once __DIR__ . '/search-results.php';
        break;

    case '/contact':
        require_once __DIR__ . '/contact.php';
        break;

    case '/address':
        require_once __DIR__ . '/address.php';
        break;

    case '/about':
        require_once __DIR__ . '/about.php';
        break;

    case '/feedback':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            require_once __DIR__ . '/Feedback.php';
            $subject = 'General Feedback'; // Set a default subject
            $message = $_POST['message'] ?? '';
            $user_id = $_SESSION['user_id'] ?? null; // Can be null if not logged in

            if (!empty($message)) {
                $feedbackModel = new Feedback();
                $feedbackModel->create($user_id, $subject, $message);
            }
            header('Location: /feedback?success=1');
            exit();
        }
        require_once __DIR__ . '/feedback_form.php';
        break;

    case '/admin-feedback':
        // Admin only
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: /login');
            exit();
        }
        require_once __DIR__ . '/admin-feedback.php';
        break;

    case '/admin-feedback/delete':
        // Admin only
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: /login');
            exit();
        }
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            require_once __DIR__ . '/Feedback.php';
            $feedbackModel = new Feedback();
            if ($feedbackModel->delete($id)) {
                 $_SESSION['action_success'] = "Feedback deleted successfully.";
            } else {
                 $_SESSION['action_error'] = "Failed to delete feedback.";
            }
        }
        header('Location: /admin-feedback');
        exit();
        break;

    case '/admin/toggle-maintenance':
        // Admin only
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: /login');
            exit();
        }

        $maintenance_flag_file = __DIR__ . '/maintenance.flag';
        if (file_exists($maintenance_flag_file)) {
            // System is IN MAINTENANCE, so turn it ON (by deleting the flag)
            unlink($maintenance_flag_file);
        } else {
            // System is ON, so turn it OFF for maintenance (by creating the flag)
            file_put_contents($maintenance_flag_file, 'Maintenance mode is active.');
        }

        header('Location: /dashboard');
        exit();
        break;

   case '/logout':
        session_destroy();
        header('Location: /login');
        exit();

    default:
        http_response_code(404);
        echo "<h1>404 Page Not Found</h1>";
        break;
}
?>