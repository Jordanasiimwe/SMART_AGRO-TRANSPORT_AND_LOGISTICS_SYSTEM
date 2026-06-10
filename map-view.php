<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/User.php';

// Check for required parameters
$roleToShow = $_GET['role'] ?? '';
$userIdToShow = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$userIdToShow || !in_array($roleToShow, ['farmer', 'vendor'])) {
    echo '<div class="container"><p class="error">Invalid request details.</p><a href="/dashboard" class="button-back">Back to Dashboard</a></div>';
    require_once __DIR__ . '/footer.php';
    exit;
}

$userModel = new User();
$googleMapsUrl = "";
$redirectMessage = "Redirecting to Google Maps...";

// Base Google Maps URL
$baseUrl = "https://www.google.com/maps/";

if ($roleToShow === 'vendor') {
    // Vendors are stationary at Nakawa Market
    // We open a search for Nakawa Market
    $googleMapsUrl = $baseUrl . "search/?api=1&query=Nakawa+Market,+Kampala";
    $redirectMessage = "Locating Nakawa Market on Google Maps...";

} elseif ($roleToShow === 'farmer') {
    $farmer = $userModel->getFarmerProfile($userIdToShow);
    
    if ($farmer) {
        $origin = "Nakawa+Market,+Kampala"; // Vendors are here
        $destination = "";

        // Priority 1: Use Precise Coordinates if available
        if (!empty($farmer['latitude']) && !empty($farmer['longitude'])) {
            $destination = $farmer['latitude'] . "," . $farmer['longitude'];
        } 
        // Priority 2: Use Text Location (e.g., "Mbarara")
        elseif (!empty($farmer['location'])) {
            $destination = urlencode($farmer['location'] . ", Uganda");
        }

        if (!empty($destination)) {
            // Open Directions: Origin (Nakawa) -> Destination (Farmer)
            $googleMapsUrl = $baseUrl . "dir/?api=1&origin=$origin&destination=$destination";
            $redirectMessage = "Calculating route from Nakawa Market to " . htmlspecialchars($farmer['farm_name'] ?: 'Farmer') . "...";
        } else {
            // Fallback: Farmer exists but has NO location set
            // Just search for the farm name, hoping Google knows it, or default to Uganda map
            $search = urlencode(($farmer['farm_name'] ?: 'Uganda'));
            $googleMapsUrl = $baseUrl . "search/?api=1&query=$search";
            $redirectMessage = "Farmer has no specific location set. Searching for " . htmlspecialchars($farmer['farm_name']) . "...";
        }
    } else {
        echo '<div class="container"><p class="error">Farmer profile not found.</p><a href="javascript:history.back()" class="button-back">Go Back</a></div>';
        require_once __DIR__ . '/footer.php';
        exit;
    }
}
?>

<div class="container" style="text-align: center; padding-top: 3rem;">
    <div class="loader"></div>
    <h2><?php echo $redirectMessage; ?></h2>
    <p>If Google Maps does not open automatically, <a href="<?php echo $googleMapsUrl; ?>">click here</a>.</p>

    <div style="margin-top: 2rem;">
        <a href="/dashboard" class="button-back">Return to Dashboard</a>
    </div>
</div>

<script>
    // Automatically redirect to Google Maps
    setTimeout(function() {
        window.location.replace("<?php echo $googleMapsUrl; ?>");
    }, 2500); // 2.5-second delay to let the user see the message and back button
</script>

<style>
    /* Simple spinner for better UX */
    .loader {
        border: 5px solid #f3f3f3;
        border-top: 5px solid var(--primary-color);
        border-radius: 50%;
        width: 50px;
        height: 50px;
        animation: spin 1s linear infinite;
        margin: 0 auto 1rem auto;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

<?php require_once __DIR__ . '/footer.php'; ?>