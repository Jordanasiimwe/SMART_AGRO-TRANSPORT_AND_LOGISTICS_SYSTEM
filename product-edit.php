<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/config.php';

// Ensure user is a logged-in farmer
if ($_SESSION['user_role'] !== 'farmer') {
    header('Location: /login');
    exit;
}

// Check if an ID is provided in the URL
if (!isset($_GET['id'])) {
    header('Location: /products');
    exit;
}

require_once __DIR__ . '/Product.php';
$productModel = new Product();
$product = $productModel->findById((int)$_GET['id']);

// If product doesn't exist or doesn't belong to this farmer, redirect them.
if (!$product || $product['farmer_id'] !== $_SESSION['user_id']) {
    header('Location: /products');
    exit;
}

?>

<div class="container">
    <h1>Edit Product</h1>
    <form action="/products/update" method="POST"  enctype="multipart/form-data">
        <?php csrf_field(); ?>
        <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description"><?php echo htmlspecialchars($product['description']); ?></textarea>
        </div>
        <div class="form-group">
            <label for="unit">Unit of Measurement</label>
            <select id="unit" name="unit">
                <option value="kg" <?php $unit = $product['unit'] ?? 'kg'; echo ($unit === 'kg' || $unit === 'kgs') ? 'selected' : ''; ?>>per kg</option>
                <option value="basin" <?php echo ($product['unit'] ?? '') === 'basin' ? 'selected' : ''; ?>>per basin</option>
                <option value="sack" <?php echo ($product['unit'] ?? '') === 'sack' ? 'selected' : ''; ?>>per sack</option>
                <option value="whole" <?php echo ($product['unit'] ?? '') === 'whole' ? 'selected' : ''; ?>>whole</option>
                <option value="tray" <?php echo ($product['unit'] ?? '') === 'tray' ? 'selected' : ''; ?>>tray</option>
            </select>
        </div>
        <div class="form-group">
            <label for="price">Price (per selected unit)</label>
            <input type="number" step="0.01" id="price" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" required>
        </div>
        <div class="form-group">
            <label for="quantity">Available Quantity (in selected units)</label>
            <input type="number" id="quantity" name="quantity" value="<?php echo htmlspecialchars($product['quantity']); ?>" required>
        </div>
        <div class="form-group">
           <label for="image">Product Image</label>
            <input type="file" id="image" name="image_file" accept="image/*">
             <label for="image_url">Or, enter image URL:</label>
            <input type="text" id="image_url" name="image_url" value="<?php echo htmlspecialchars($product['image_url']); ?>" placeholder="e.g., https://example.com/images/your-product.jpg">
        </div>
        <button type="submit">Update Product</button>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>