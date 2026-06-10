<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/config.php';

// Check if the user is a farmer
if ($_SESSION['user_role'] !== 'farmer') {
    echo "You are not authorized to view this page.";
    exit;
}

?>

<div class="container">
    <h1>Add New Product </h1>
    <form action="/products/create" method="POST" enctype="multipart/form-data">
        <?php csrf_field(); ?>
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" required>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description"></textarea>
        </div>
        <div class="form-group">
            <label for="unit">Unit of Measurement</label>
            <select id="unit" name="unit">
                <option value="kg">per kg</option>
                <option value="basin">per basin</option>
                <option value="sack">per sack</option>
                <option value="whole">whole</option>
                <option value="tray">tray</option>
            </select>
        </div>
        <div class="form-group">
            <label for="price">Price (per selected unit)</label>
            <input type="number" step="0.01" id="price" name="price" placeholder="e.g., 5000" required>
        </div>
        <div class="form-group">
            <label for="quantity">Available Quantity (in selected units)</label>
            <input type="number" id="quantity" name="quantity" placeholder="e.g., 10" required>
        </div>
        <div class="form-group">
            <label for="image">Product Image</label>
            <input type="file" id="image" name="image_file" accept="image/*" >
            <label for="image_url">Or, enter image URL:</label>
            <input type="text" id="image_url" name="image_url" placeholder="e.g., https://example.com/images/your-product.jpg">
        </div>
        <button type="submit">Add Product</button>
    </form>
</div>

<?php
require_once __DIR__ . '/footer.php';
?>