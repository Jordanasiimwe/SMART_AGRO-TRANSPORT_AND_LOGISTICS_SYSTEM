<?php
require_once __DIR__ . '/header.php';

// Check if the user is a farmer
if ($_SESSION['user_role'] !== 'farmer') {
    echo "You are not authorized to view this page.";
    exit;
}

// Get the farmer's ID
$farmer_id = $_SESSION['user_id'];

// Include the Product model
require_once __DIR__ . '/Product.php';

// Create a new Product object
$productModel = new Product();


// Get the farmer's products
$products = $productModel->findByFarmerId($farmer_id);

?>
<div class="container products">
    <div class="page-header">
        <h1>Manage Your Products</h1>
        <a href="/dashboard" class="button-back">&larr; Back to Dashboard</a>
    </div>

    <?php if (isset($_SESSION['product_action_error'])): ?>
        <div class="error" style="margin-bottom: 1rem;"><?php echo $_SESSION['product_action_error']; unset($_SESSION['product_action_error']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['product_action_success'])): ?>
        <div class="success" style="color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 0.75rem 1.25rem; margin-bottom: 1rem; border-radius: 0.25rem;"><?php echo $_SESSION['product_action_success']; unset($_SESSION['product_action_success']); ?></div>
    <?php endif; ?>

    <a href="/products/add" class="button primary">Add New Product</a>

    <?php if ($products): ?>
        <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Price / Unit</th>
                    <th>Available</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <?php
                        // Prepare unit for display (always singular for price, dynamic for quantity)
                        $unit_singular = htmlspecialchars($product['unit'] ?? 'kg');
                        if (substr($unit_singular, -1) === 's') {
                            $unit_singular = substr($unit_singular, 0, -1);
                        }
                        $quantity = htmlspecialchars($product['quantity']);
                    ?>
                    <tr>
                        <td><img src="<?php echo htmlspecialchars($product['image_url'] ?: '/images/smart.jpg'); ?>" alt="Product" class="product-thumbnail"  onerror="this.src='/images/smart.jpg'"></td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo number_format($product['price'], 2) . ' / ' . $unit_singular; ?></td>
                        <td>
                            <?php
                                // Automate pluralization for display
                                if ($product['quantity'] > 0) {
                                    echo $quantity . ' ' . ($quantity != 1 ? $unit_singular . 's' : $unit_singular);
                                } else {
                                    echo '<span style="color: red; font-weight: bold;">Out of Stock</span>';
                                }
                            ?>
                        </td>
                        <td>
                            <div class="actions-cell">
                                <a href="/products/edit?id=<?php echo $product['id']; ?>" class="action-button button-edit">Edit</a>
                                <a href="/products/delete?id=<?php echo $product['id']; ?>" class="action-button button-delete" onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php else: ?>
    <p>You have not added any products yet.</p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>