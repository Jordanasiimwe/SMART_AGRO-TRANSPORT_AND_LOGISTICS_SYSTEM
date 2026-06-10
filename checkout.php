<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';

// Ensure user is a logged-in vendor
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'vendor') {
    header('Location: /login');
    exit;
}

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    ?>
    <div class="container empty-cart-page">
        <div class="empty-cart-card">
            <div class="icon-container">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
            </div>
            <h1>Your Cart is Empty</h1>
            <p class="empty-cart-message">Looks like you haven't added any produce to your cart yet. Start browsing to find fresh goods from local farmers.</p>
            <div class="action-buttons">
                <a href='/browse' class='button primary-btn'>Browse Products</a>
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
    exit;
}

$db = Database::getInstance();
$productIds = array_keys($cart);

// Fetch products along with Farmer Location details
$placeholders = implode(',', array_fill(0, count($productIds), '?'));
$stmt = $db->prepare("
    SELECT p.id, p.name, p.price, p.unit, p.farmer_id, f.farm_name, f.location, f.contact
    FROM products p
    JOIN farmers f ON p.farmer_id = f.user_id
    WHERE p.id IN ($placeholders)
");
$stmt->execute($productIds);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group products by Farmer to handle transport per location
$groupedProducts = [];
foreach ($products as $product) {
    $pId = $product['id'];
    if (isset($cart[$pId])) {
        $product['cart_qty'] = $cart[$pId]['quantity'];
        $product['cart_unit'] = $cart[$pId]['unit'];
        $groupedProducts[$product['farmer_id']][] = $product;
    }
}

// Helper function to generate mock transport means
function getMockTransport($location) {
    $means = ['TukTuk', 'Boda Boda', 'Pickup Truck', 'Canter Van'];
    $drivers = ['Musa', 'John', 'David', 'Sarah', 'Peter', 'Hassan'];
    $mockOptions = [];
    $count = rand(2, 4); // Generate 2-4 options
    
    for($i=0; $i<$count; $i++) {
        $type = $means[array_rand($means)];
        $name = $drivers[array_rand($drivers)];
        $number = '07' . rand(0, 9) . rand(1000000, 9999999);
        $mockOptions[] = "$type - $name ($number)";
    }
    return $mockOptions;
}
?>

<div class="container">
    <h1>Checkout & Transport Selection</h1>
    <form action="/process-payment" method="POST">
        <?php csrf_field(); ?>
        
        <div class="checkout-groups">
            <?php foreach ($groupedProducts as $farmerId => $items): ?>
                <?php 
                    $farmName = $items[0]['farm_name'];
                    $location = !empty($items[0]['location']) ? $items[0]['location'] : 'Unknown Location';
                    $farmContact = !empty($items[0]['contact']) ? $items[0]['contact'] : 'Not Available';
                ?>
                <div class="card checkout-card">
                    <h3>Order from: <?php echo htmlspecialchars($farmName); ?></h3>
                    <p class="location-badge">Farmer Contact (for Mobile Money): <strong class="farmer-contact"><?php echo htmlspecialchars($farmContact); ?></strong></p>
                    <p class="location-badge">Farm Location: <strong><?php echo htmlspecialchars($location); ?></strong></p>
                    
                    <ul class="item-list">
                        <?php foreach ($items as $item): ?>
                            <li>
                                <strong><?php echo htmlspecialchars($item['name']); ?></strong> 
                                - <?php echo htmlspecialchars($item['cart_qty'] . ' ' . $item['cart_unit']); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="transport-section">
                        <h4>Choose Transport Method:</h4>
                        <div class="radio-group">
                            <label>
                                <input type="radio" name="transport[<?php echo $farmerId; ?>][type]" value="self" checked onchange="toggleTransport(this, '<?php echo $farmerId; ?>')">
                                Self Pickup
                            </label>
                            <label>
                                <input type="radio" name="transport[<?php echo $farmerId; ?>][type]" value="hired" onchange="toggleTransport(this, '<?php echo $farmerId; ?>')">
                                Hire Transport (Available in <?php echo htmlspecialchars($location); ?>)
                            </label>
                        </div>

                        <div id="transport-options-<?php echo $farmerId; ?>" class="transport-options" style="display:none;">
                            <label for="transporter-<?php echo $farmerId; ?>">Select a Driver:</label>
                            <select name="transport[<?php echo $farmerId; ?>][details]" id="transporter-<?php echo $farmerId; ?>">
                                <?php foreach (getMockTransport($location) as $option): ?>
                                    <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="hint">Select a driver to receive their contact details upon order completion.</p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card payment-section">
            <h3>Payment Method</h3>
            <div class="form-group">
                <select name="payment_method" id="payment_method" required>
                    <option value="cash">Cash on Delivery</option>
                    <option value="mobile_money">Mobile Money</option>
                </select>
            </div>
            <div id="mm-details" style="display:none; margin-top:10px;">
                <p class="hint" style="color: #0056b3; font-weight: bold;">Please send payment to the farmer's contact number listed in each order section above.</p>
                <label>Transaction ID:</label>
                <input type="text" name="transaction_id" placeholder="Enter MM Transaction ID">
            </div>
        </div>

        <button type="submit" class="button primary" style="width: 100%; padding: 15px; font-size: 1.1rem; margin-top: 20px;">Complete Purchase</button>
    </form>
</div>

<script>
    function toggleTransport(radio, id) {
        document.getElementById('transport-options-' + id).style.display = (radio.value === 'hired') ? 'block' : 'none';
    }
    document.getElementById('payment_method').addEventListener('change', function() {
        document.getElementById('mm-details').style.display = (this.value === 'mobile_money') ? 'block' : 'none';
    });
</script>

<?php require_once __DIR__ . '/footer.php'; ?>