<?php

// Ensure session is started for CSRF token generation
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF Token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrf_field() {
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
}

function verify_csrf() {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        die("CSRF token validation failed.");
    }
}

// Application-wide configuration constants

// --- Unit Conversion Logic ---
// This is a simplified conversion. In a real-world app, this might be stored in the database,
// possibly per product category, as a "basin" of feathers weighs less than a "basin" of stones.
// For this application, we assume a general conversion for agricultural produce.
define('CONVERSION_FACTORS', [
    'kg'    => 1,
    'basin' => 15, // Assumed: 1 basin ~ 15 kg
    'sack'  => 50, // Assumed: 1 sack ~ 50 kg
    'whole' => 1,  // 1 Whole item
    'tray'  => 1,  // 1 Tray (e.g., eggs)
]);