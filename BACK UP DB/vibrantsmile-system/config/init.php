<?php
// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
}

// Set session timeout to 30 minutes
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
    session_unset();
    session_destroy();
    header("location: /index.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// CSRF validation function
function validateCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Error reporting in production
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Create logs directory if it doesn't exist
if (!file_exists(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0755, true);
}

// Set timezone
date_default_timezone_set('Asia/Manila');

// Medical Data Encryption Functions
define('MEDICAL_ENCRYPTION_KEY', 'cfdf7bb1293b22c7e5bff24c9503c713483570bda51708743e4ba05d5eae54a6'); // Replace with a secure key
define('MEDICAL_ENCRYPTION_ALGO', 'aes-256-gcm');

function encryptMedicalData($data) {
    $iv = random_bytes(openssl_cipher_iv_length(MEDICAL_ENCRYPTION_ALGO));
    $encrypted = openssl_encrypt(
        $data,
        MEDICAL_ENCRYPTION_ALGO,
        MEDICAL_ENCRYPTION_KEY,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );
    return base64_encode($iv . $tag . $encrypted);
}

function decryptMedicalData($encryptedData) {
    $data = base64_decode($encryptedData);
    $ivLength = openssl_cipher_iv_length(MEDICAL_ENCRYPTION_ALGO);
    $iv = substr($data, 0, $ivLength);
    $tag = substr($data, $ivLength, 16);
    $encrypted = substr($data, $ivLength + 16);
    return openssl_decrypt(
        $encrypted,
        MEDICAL_ENCRYPTION_ALGO,
        MEDICAL_ENCRYPTION_KEY,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );
}
?> 