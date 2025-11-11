<?php
// Authentication functions

// Start session only if not already started
function startSessionIfNotStarted() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Check if user is logged in
function isLoggedIn() {
    startSessionIfNotStarted();
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// Redirect to login if not authenticated
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Get current user email
function getCurrentUserEmail() {
    startSessionIfNotStarted();
    return $_SESSION['user_email'] ?? null;
}

// Logout function
function logout() {
    startSessionIfNotStarted();
    
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header('Location: login.php');
    exit;
}
?>