<?php
/**
 * Application Configuration
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Application constants
define('APP_NAME', 'E-commerce Admin Panel');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost:3000');
define('UPLOAD_PATH', 'uploads/');

// Security settings
define('CSRF_TOKEN_NAME', '_token');
define('SESSION_TIMEOUT', 7200); // 2 hours

// Include required files
require_once 'database.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Product.php';
require_once __DIR__ . '/../classes/Category.php';
require_once __DIR__ . '/../classes/Order.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Customer.php';
require_once __DIR__ . '/../classes/Feedback.php';
require_once __DIR__ . '/../classes/Review.php';
require_once __DIR__ . '/../classes/Coupon.php';
require_once __DIR__ . '/../classes/Settings.php';

// Initialize database connection
try {
    $db = new Database();
} catch (Exception $e) {
    die("Application Error: " . $e->getMessage());
}

// Helper functions
function generateCSRFToken() {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function verifyCSRFToken($token) {
    return hash_equals($_SESSION[CSRF_TOKEN_NAME] ?? '', $token);
}

function redirectTo($url) {
    header("Location: " . $url);
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

function requireAuth() {
    if (!isLoggedIn()) {
        redirectTo('login.php');
    }
}

function hasRole($role) {
    return $_SESSION['user_role'] === $role;
}

function formatPrice($price) {
    return '$' . number_format($price, 2);
}

function formatDate($date) {
    return date('M j, Y g:i A', strtotime($date));
}