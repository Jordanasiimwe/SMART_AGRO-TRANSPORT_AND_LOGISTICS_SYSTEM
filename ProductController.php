<?php
require_once __DIR__ . '/Product.php';
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/SmsService.php';

class ProductController {

    private Product $productModel;

    public function __construct() {
        $this->productModel = new Product();
    }

    /**
     * Handles displaying the list of products for a farmer.
     * The required file (products.php) already contains the logic to fetch and display.
     */
    public function index() {
        require_once __DIR__ . '/products.php';
    }

    /**
     * Handles showing the form to add a new product.
     */
    public function add() {
        require_once __DIR__ . '/product-add.php';
    }

    /**
     * Handles the POST request to create a new product.
     */
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'farmer') {
                header('Location: /login');
                exit();
            }

            $farmer_id = $_SESSION['user_id'];
            $name = trim($_POST['name'] ?? '');
            $description = $_POST['description'] ?? '';
            $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
            $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_FLOAT);
            $unit = $_POST['unit'] ?? 'kgs';
            
            if ($price === false || $quantity === false || empty($name)) {
                // Check if POST is empty but Content-Length exists (indicates file size limit exceeded)
                if (empty($_POST) && $_SERVER['CONTENT_LENGTH'] > 0) {
                    $_SESSION['product_action_error'] = "The image file is too large. Please resize it or choose a smaller file.";
                } else {
                    $_SESSION['product_action_error'] = "Please ensure the product name, price, and quantity are filled correctly.";
                }
                header('Location: ' . BASE_URL . '/products/add');
                exit();
            }
        
            // Handle image upload
            $image_url = null;
            if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['image_file'];
                $uploadDir = __DIR__ . '/images/';
                $uploadFile = $uploadDir . basename($file['name']);
        
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (in_array($file['type'], $allowedTypes) && move_uploaded_file($file['tmp_name'], $uploadFile)) {
                    $image_url = '/images/' . basename($file['name']);
                }
            } else if (!empty($_POST['image_url'])) {
                $image_url = filter_input(INPUT_POST, 'image_url', FILTER_SANITIZE_URL);
            }
           
            $productId = $this->productModel->create($farmer_id, $name, $description, $price, $quantity, $unit, $image_url);

            if ($productId) {
                $userModel = new User();
                $smsService = new SmsService();

                $farmerProfile = $userModel->getFarmerProfile($farmer_id);
                $farmerName = !empty($farmerProfile['farm_name']) ? $farmerProfile['farm_name'] : $_SESSION['username'];
                
                $allProducts = $this->productModel->findByFarmerId($farmer_id);
                $productCount = is_array($allProducts) ? count($allProducts) : 0;

                $vendorMessage = "Farmer {$farmerName} has uploaded a new product. They now have {$productCount} product(s) available. If interested, please checkout and make an order.";
                $adminMessage = "Farmer {$farmerName} has added another product to the market. Total products for this farmer: {$productCount}.";

                $recipients = $userModel->getSystemNotificationRecipients();
                foreach ($recipients as $recipient) {
                    // Select the message based on the recipient's role
                    $body = ($recipient['role_name'] === 'admin') ? $adminMessage : $vendorMessage;
                    
                    $smsService->sendSms($farmer_id, $recipient['id'], $recipient['contact'], $body);
                }
            }
        }
        header('Location: /products');
        exit();
    }

    /**
     * Handles showing the form to edit an existing product.
     */
    public function edit() {
        require_once __DIR__ . '/product-edit.php';
    }

    /**
     * Handles the POST request to update an existing product.
     */
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'farmer') {
                header('Location: /login');
                exit();
            }

            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $name = trim($_POST['name'] ?? '');
            $description = $_POST['description'] ?? '';
            $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
            $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_FLOAT);
            $unit = $_POST['unit'] ?? 'kgs';
        
            if ($id === false || $price === false || $quantity === false || empty($name)) {
                $_SESSION['product_action_error'] = "Update failed: Invalid input data.";
                header('Location: ' . BASE_URL . '/products/edit?id=' . $id);
                exit();
            }
        
            // Handle image upload
            $image_url = null;
            if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['image_file'];
                $uploadDir = __DIR__ . '/images/';
                $uploadFile = $uploadDir . basename($file['name']);
        
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (in_array($file['type'], $allowedTypes) && move_uploaded_file($file['tmp_name'], $uploadFile)) {
                    $image_url = '/images/' . basename($file['name']);
                }
            } else if (!empty($_POST['image_url'])) {
                $image_url = filter_input(INPUT_POST, 'image_url', FILTER_SANITIZE_URL);
            } else {
                $existingProduct = $this->productModel->findById($id);
                $image_url = $existingProduct['image_url'];
            }
        
            $this->productModel->update($id, $name, $description, $price, $quantity, $unit, $image_url);
        }
        header('Location: /products');
        exit();
    }

    /**
     * Handles the GET request to delete a product.
     */
    public function delete() {
        if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'farmer') {
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if ($id) {
                $product = $this->productModel->findById($id);
                
                if ($product && $product['farmer_id'] == $_SESSION['user_id']) {
                    if ($this->productModel->delete($id)) {
                        $_SESSION['product_action_success'] = "Product deleted successfully.";
                    } else {
                        $_SESSION['product_action_error'] = "Failed to delete product. It might be part of an existing order that could not be cleared.";
                    }
                }
            }
        }
        header('Location: /products');
        exit();
    }
}