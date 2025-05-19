<?php
// config.php - Konfigurasi sistem
define('APP_NAME', 'Pelayaran Kepri');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/pelayaran_kepri/');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pelayaran_kepri');

// Admin Configuration
define('ADMIN_SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('DEFAULT_ADMIN_USERNAME', 'admin');
define('DEFAULT_ADMIN_PASSWORD', 'password');

// Reservasi Configuration
define('MAX_PASSENGERS_PER_BOOKING', 5);
define('ADVANCE_BOOKING_DAYS', 30); // Berapa hari ke depan bisa booking
define('MIN_BOOKING_HOURS', 2); // Minimum jam sebelum keberangkatan

// Email Configuration (jika diperlukan di masa depan)
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');

// Upload Configuration
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

// Pagination
define('RECORDS_PER_PAGE', 25);

// Time Zone
date_default_timezone_set('Asia/Jakarta');

// Security
define('SESSION_NAME', 'PELAYARAN_KEPRI_SESSION');
define('CSRF_TOKEN_NAME', '_token');

// Log Configuration
define('LOG_PATH', __DIR__ . '/logs/');
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR

// Cache Configuration (jika menggunakan cache)
define('CACHE_ENABLED', false);
define('CACHE_LIFETIME', 300); // 5 minutes

// Error Handling
if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    define('DEBUG_MODE', true);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    define('DEBUG_MODE', false);
}

// Auto-create directories if they don't exist
if (!is_dir(LOG_PATH)) {
    mkdir(LOG_PATH, 0755, true);
}

/**
 * Get configuration value
 */
function getConfig($key, $default = null) {
    return defined($key) ? constant($key) : $default;
}

/**
 * Check if in debug mode
 */
function isDebugMode() {
    return getConfig('DEBUG_MODE', false);
}

/**
 * Log message
 */
function logMessage($message, $level = 'INFO') {
    $log_file = LOG_PATH . 'app_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] [$level] $message" . PHP_EOL;
    
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * Validate CSRF token
 */
function validateCSRF($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Generate CSRF token
 */
function generateCSRF() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Sanitize input
 */
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Indonesia format)
 */
function validatePhone($phone) {
    // Remove all non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Check if it starts with 08 or 628 and has proper length
    return preg_match('/^(08|628)[0-9]{8,12}$/', $phone);
}

/**
 * Format phone number
 */
function formatPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    if (substr($phone, 0, 1) === '8') {
        $phone = '0' . $phone;
    } elseif (substr($phone, 0, 3) === '628') {
        $phone = '0' . substr($phone, 2);
    }
    
    return $phone;
}

/**
 * Validate Indonesian ID number (KTP)
 */
function validateKTP($ktp) {
    $ktp = preg_replace('/[^0-9]/', '', $ktp);
    return strlen($ktp) === 16 && is_numeric($ktp);
}

/**
 * Check if date is in future
 */
function isFutureDate($date) {
    $check_date = new DateTime($date);
    $now = new DateTime();
    return $check_date > $now;
}

/**
 * Check if booking is within allowed advance days
 */
function isWithinBookingWindow($date) {
    $check_date = new DateTime($date);
    $max_date = new DateTime();
    $max_date->add(new DateInterval('P' . ADVANCE_BOOKING_DAYS . 'D'));
    
    return $check_date <= $max_date;
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )), 1, $length);
}

/**
 * Convert to slug
 */
function toSlug($string) {
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
}

/**
 * Get client IP address
 */
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}
?>