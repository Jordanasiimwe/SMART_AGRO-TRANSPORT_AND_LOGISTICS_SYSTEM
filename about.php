<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/User.php';
$userModel = new User();

// Load team members from DB
$teamMembers = $userModel->getTeamMembers();
$defaultTeam = ["Icarit David Julius", "Asiimwe Jordan", "Nahigo Racheal", "Nagginda Shirat", "Tusiime Rhoda"];
if (empty($teamMembers)) {
    $teamMembers = $defaultTeam;
}

// Check if user is admin to enable editing
$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="SmartAgro-Link System is a digital platform designed to connect farmers directly with vendors in Nakawa Market. The system enables farmers to showcase available agricultural products, while vendors can easily search, order, and communicate with suppliers in real time. By reducing intermediaries, SmartAgro-Link System improves market access, enhances transparency, promotes fair pricing, and streamlines the agricultural supply chain.">
    <meta name="keywords" content="SmartAgro-Link, agriculture, farmers, vendors, Nakawa Market, agricultural products, supply chain, fair pricing">
    <meta property="og:title" content="About Us - Smart AgroLink System">
    <meta property="og:description" content="SmartAgro-Link System is a digital platform designed to connect farmers directly with vendors in Nakawa Market. The system enables farmers to showcase available agricultural products, while vendors can easily search, order, and communicate with suppliers in real time. By reducing intermediaries, SmartAgro-Link System improves market access, enhances transparency, promotes fair pricing, and streamlines the agricultural supply chain.">
    <meta property="og:image" content="/images/smart.jpg">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="About Us - Smart AgroLink System">
    <meta name="twitter:description" content="SmartAgro-Link System is a digital platform designed to connect farmers directly with vendors in Nakawa Market. The system enables farmers to showcase available agricultural products, while vendors can easily search, order, and communicate with suppliers in real time. By reducing intermediaries, SmartAgro-Link System improves market access, enhances transparency, promotes fair pricing, and streamlines the agricultural supply chain.">
    <meta name="twitter:image" content="/images/smart.jpg">
    <title>About Us - Smart AgroLink System</title>
    <link rel="stylesheet" href="/style.css">
    <link rel="icon" href="/images/smart.jpg" type="image/jpeg">
</head>
<body style="color:white;text-shadow: 1px 1px 2px black;background-color: #8FBC8F;background-image: none;">

    <div class="page-header">
        <h1>About Us</h1>
    </div>
    
    <div class="static-content">
        <h2 style="color: #558B2F;">Smart AgroLink <span style="color: #FFC107;">System</span></h2>
        <p class="tagline">Connecting Farmers to Markets</p>
        
        <div class="welcome-message" style="background-image: url('/images/market.jpg'); background-size: cover; background-position: center; color: skyblue;">
            <h3>YOU ARE WELCOME</h3>
            <p>OUR PLATFORM IS OPEN 24/7 TO SERVE YOU</p>
            <p>GET A TASTE OF FRESH, QUALITY PRODUCE</p>
        </div>

        <p class="app-importance" style="color:purple;">
            Smart AgroLink System is dedicated to revolutionizing the agricultural landscape by bridging the gap between farmers and markets. Our platform aims to empower farmers, enhance market access, and promote sustainable agriculture. We strive to ensure fair prices for farmers, provide consumers with quality produce, and build a resilient agricultural community. With Smart AgroLink System, we envision a future where agriculture is efficient, equitable, and environmentally conscious.
        </p>

        <div class="mv-container">
            <div class="mv-card">
                <h3>Our Mission</h3>
                <p>To empower farmers through digital connectivity, ensuring they receive fair value for their produce while providing consumers with easy access to fresh, high-quality agricultural goods.</p>
            </div>
            <div class="mv-card">
                <h3>Our Vision</h3>
                <p>To be the leading digital agricultural marketplace in Uganda, fostering a sustainable and transparent ecosystem where every farmer thrives and every household has access to healthy food.</p>
            </div>
        </div>

        <div class="team-section">
            <h3>Meet The Team</h3>
            <p>Class of 2026</p>
            <ul class="team-list">
                <?php foreach ($teamMembers as $index => $member): ?>
                    <?php if ($isAdmin): ?>
                        <li onclick="editMember(<?php echo $index; ?>, '<?php echo htmlspecialchars($member, ENT_QUOTES); ?>')" title="Click to edit" class="editable-member">
                            <?php echo htmlspecialchars($member); ?> 
                            <span class="edit-icon">&#9998;</span>
                        </li>
                    <?php else: ?>
                        <li><?php echo htmlspecialchars($member); ?></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>


        <p class="commitment" style="color:white;">
            We're committed to great produce, direct connections, and great service—an experience that will make your time with us fruitful. All visuals are for representation purposes only. Prices are quoted in Uganda Shillings and are determined by the farmers..
        </p>
    </div>


<style>


.static-content { padding-top: 1rem; font-size: 1.1rem; line-height: 1.8; }
.static-content {
    text-align: center;
}
.static-content h2 {
    font-size: 2.5rem;
    color: var(--primary-color); 
    margin-bottom: 0.5rem;
}
.tagline {
    font-size: 1.2rem;
    font-style: italic;
    color: #555;
    margin-bottom: 2rem;
}
.welcome-message {
    margin: 2rem 0;
    padding: 1.5rem;
    background-color: var(--light-gray);
    border-radius: var(--border-radius);
}
.welcome-message h3 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
}
.commitment {
    font-size: 0.9rem;
    color: #777;
    margin-top: 2rem;
}
 .app-importance {
    font-size: 1rem;
    color: grey;
    line-height: 1.6; 
    margin-bottom: 2rem;
    text-align: justify;
}
.mv-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: center;
    margin: 2rem 0;
}
.mv-card {
    flex: 1 1 300px;
    background: rgba(255, 255, 255, 0.2);
    padding: 1.5rem;
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}
.mv-card h3 {
    color: #FFC107; /* Matches the secondary color/accent */
    margin-top: 0;
    text-shadow: 1px 1px 2px black;
}
.team-section { margin-top: 2rem; }
.team-section h3 { color: white; text-shadow: 1px 1px 2px black; font-size: 1.8rem; }
.team-list { list-style: none; padding: 0; display: flex; flex-wrap: wrap; justify-content: center; gap: 15px; margin-top: 1rem; }
.team-list li {
    color: white;
    padding: 10px 20px;
    font-weight: bold;
    font-size: 1.1rem;
}
.editable-member {
    cursor: pointer;
    transition: transform 0.2s, background-color 0.2s;
}
.editable-member:hover {
    background-color: rgba(255, 255, 255, 0.1);
    transform: scale(1.05);
    border-radius: 10px;
}
.edit-icon { font-size: 0.8em; color: white; margin-left: 5px; opacity: 0.7; }

</style>

<?php require_once __DIR__ . '/public_footer.php'; ?>

<script>
function editMember(index, currentName) {
    const newName = prompt("Edit Team Member Name:", currentName);
    if (newName && newName.trim() !== "" && newName !== currentName) {
        fetch('/admin/update-team', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ index: index, name: newName })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert("Failed to update name.");
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert("An error occurred while saving.");
        });
    }
}
</script>