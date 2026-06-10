<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/config.php';

// Vendor-only page
if ($_SESSION['user_role'] !== 'vendor') {
    header('Location: /dashboard');
    exit;
}

require_once __DIR__ . '/Product.php';
$productModel = new Product();
$products = $productModel->getAllProducts();
?>

<div class="container">
    <div class="page-header">
        <h1>Browse Produce</h1>
        <a href="/dashboard" class="button-back">&larr; Back to Dashboard</a>
    </div>

    <div class="product-grid">
        <?php if ($products): ?>
            <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <img src="<?php echo htmlspecialchars($product['image_url'] ?: 'https://images.unsplash.com/photo-1598170845058-32b9d6a5da37?q=80&w=1770&auto=format&fit=crop'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <div class="product-card-body">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="product-farm">
                            from <?php echo htmlspecialchars($product['farm_name']); ?>
                            <?php if (!empty($product['location'])): ?>
                                <br>
                                <a href="/map-view?role=farmer&id=<?php echo $product['farmer_id']; ?>" class="location-link" target="_blank" title="Click to see distance to Nakawa Market">&#128205; <?php echo htmlspecialchars($product['location']); ?> (View Map)</a>
                            <?php endif; ?>
                        </p>
                        <p class="product-price"><?php echo number_format($product['price'], 2); ?> / <?php echo htmlspecialchars($product['unit']); ?></p>
                        <?php if ($product['quantity'] > 0): ?>
                            <p class="product-available" id="available-<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['quantity']); ?> <?php echo htmlspecialchars($product['unit']); ?> available</p>
                        <?php else: ?>
                            <p class="product-available" style="color: red; font-weight: bold;">Out of Stock</p>
                        <?php endif; ?>
                        <form action="/cart/add" method="POST" class="add-to-cart-form">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <input type="number" name="quantity" value="1" min="1" required class="form-control-small">
                            <select name="unit" class="form-control-small" onchange="updateAvailability(this, <?php echo $product['id']; ?>, <?php echo $product['quantity']; ?>, '<?php echo $product['unit']; ?>')">
                                <option value="kg" <?php echo ($product['unit'] == 'kg' ? 'selected' : ''); ?>>kg</option>
                                <option value="basin" <?php echo ($product['unit'] == 'basin' ? 'selected' : ''); ?>>basin</option>
                                <option value="sack" <?php echo ($product['unit'] == 'sack' ? 'selected' : ''); ?>>sack</option>
                                <option value="whole" <?php echo ($product['unit'] == 'whole' ? 'selected' : ''); ?>>whole</option>
                                <option value="tray" <?php echo ($product['unit'] == 'tray' ? 'selected' : ''); ?>>tray</option>
                            </select>
                            <?php if ($product['quantity'] > 0): ?>
                                <button type="submit" class="action-button button-add-cart" title="Add to Cart">&plus;</button>
                            <?php else: ?>
                                <button type="button" class="action-button button-add-cart" disabled style="background-color: #ccc; cursor: not-allowed;" title="Unavailable">&times;</button>
                            <?php endif; ?>
                            <?php if (isset($_SESSION['cart_message']) && $_SESSION['cart_message'] == $product['id']): ?>
                                <span style="color: green; font-size: smaller;">Added to cart!</span>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No products are available at the moment.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>

<script>
    // Pass PHP conversion factors to JavaScript
    const conversionFactors = <?php echo json_encode(CONVERSION_FACTORS); ?>;

    function updateAvailability(selectElement, productId, originalQty, originalUnit) {
        const selectedUnit = selectElement.value;
        const availableEl = document.getElementById('available-' + productId);

        if (availableEl) {
            const originalFactor = conversionFactors[originalUnit] || 1;
            const selectedFactor = conversionFactors[selectedUnit] || 1;

            // Calculate equivalent quantity: (Original Qty * Original Factor) / Selected Factor
            let newQty = (originalQty * originalFactor) / selectedFactor;

            // Format: Max 2 decimal places, remove trailing zeros (e.g., 3.3333 -> 3.33, 5.0 -> 5)
            newQty = parseFloat(newQty.toFixed(2));

            let displayUnit = selectedUnit;
            if (newQty > 1) {
                displayUnit += 's';
            }

            availableEl.innerText = newQty + ' ' + displayUnit + ' available';
        }
    }
</script>

<style>
    .location-link {
        color: #007bff;
        text-decoration: none;
        font-weight: bold;
        font-size: 0.9rem;
    }
    .location-link:hover {
        text-decoration: underline;
        color: #0056b3;
    }
</style>