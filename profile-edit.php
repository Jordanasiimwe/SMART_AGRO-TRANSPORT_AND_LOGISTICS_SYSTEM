<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/config.php';

// This page is for farmers and vendors
if (!in_array($_SESSION['user_role'], ['farmer', 'vendor'])) {
    header('Location: /login');
    exit;
}

require_once __DIR__ . '/User.php';
$userModel = new User();
$user_id = $_SESSION['user_id'];
$role = $_SESSION['user_role'];

if ($role === 'farmer') {
    $profile = $userModel->getFarmerProfile($user_id);
} else { // vendor
    $profile = $userModel->getVendorDetails($user_id);
}

?>

<div class="container">
    <h1>Edit Your Profile</h1>
    <form action="/profile/update" method="POST">
        <?php csrf_field(); ?>
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>" required>
        </div>

        <?php if ($role === 'farmer'): ?>
            <div class="form-group">
                <label for="farm_name">Farm Name</label>
                <input type="text" id="farm_name" name="farm_name" value="<?php echo htmlspecialchars($profile['farm_name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="sacco">SACCO Group (Optional)</label>
                <input type="text" id="sacco" name="sacco" value="<?php echo htmlspecialchars($profile['sacco'] ?? ''); ?>">
            </div>
            <!-- Modified Location Input for Geocoding -->
            <div class="form-group">
                <label for="location">Location (e.g., Town, District)</label>
                <div style="display: flex; gap: 10px;">
                    <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($profile['location'] ?? ''); ?>" placeholder="e.g. Jinja, Mbarara, Luwero" required>
                    <button type="button" id="locate-btn" style="width: auto; padding: 0 20px; white-space: nowrap;">Detect Coordinates</button>
                </div>
                <small id="location-status" style="display: block; margin-top: 5px; color: #666; font-style: italic;">Enter your district or town and click "Detect Coordinates" to enable distance calculation.</small>
            </div>
            <div class="form-group">
                <label for="contact">Contact (e.g., Phone Number)</label>
                <input type="text" id="contact" name="contact" value="<?php echo htmlspecialchars($profile['contact'] ?? ''); ?>" required>
            </div>

            <!-- Hidden inputs for coordinates -->
            <input type="hidden" name="latitude" id="latitude" value="<?php echo htmlspecialchars($profile['latitude'] ?? ''); ?>">
            <input type="hidden" name="longitude" id="longitude" value="<?php echo htmlspecialchars($profile['longitude'] ?? ''); ?>">
            
        <?php elseif ($role === 'vendor'): ?>
            
            <div class="form-group">
                <label for="market_stall_id">Market Stall ID</label>
                <input type="text" id="market_stall_id" name="market_stall_id" value="<?php echo htmlspecialchars($profile['market_stall_id'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="sacco">SACCO Name</label>
                <input type="text" id="sacco" name="sacco" value="<?php echo htmlspecialchars($profile['sacco_name'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="contact">Contact Phone (for SMS)</label>
                <input type="text" id="contact" name="contact" value="<?php echo htmlspecialchars($profile['contact'] ?? ''); ?>" placeholder="e.g. 0772123456" required>
            </div>
        <?php endif; ?>
        
        <button type="submit">Update Profile</button>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>

<?php if ($role === 'farmer'): ?>
<script>
    document.getElementById('locate-btn').addEventListener('click', function() {
        const locationInput = document.getElementById('location').value;
        const statusEl = document.getElementById('location-status');
        const latInput = document.getElementById('latitude');
        const lngInput = document.getElementById('longitude');

        if (!locationInput.trim()) {
            statusEl.innerHTML = '<span style="color: red;">Please enter a location first.</span>';
            return;
        }

        statusEl.innerHTML = 'Searching coordinates...';
        
        // Use OpenStreetMap Nominatim API for geocoding
        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(locationInput)}, Uganda&limit=1`)
            .then(response => response.json())
            .then(data => {
                if (data && data.length > 0) {
                    const lat = data[0].lat;
                    const lon = data[0].lon;
                    latInput.value = lat;
                    lngInput.value = lon;
                    statusEl.innerHTML = `<span style="color: green;">Coordinates found! (${lat}, ${lon})</span>`;
                } else {
                    statusEl.innerHTML = '<span style="color: red;">Location not found. Please try a nearby town or district.</span>';
                    latInput.value = '';
                    lngInput.value = '';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                statusEl.innerHTML = '<span style="color: red;">Error connecting to map service.</span>';
            });
    });
</script>
<?php endif; ?>