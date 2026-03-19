<?php
// includes/session.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
function redirectIfNotLoggedIn($role = 'patient') {
    if ($role === 'patient') {
        if (!isset($_SESSION['patient_id']) && !isset($_SESSION['patient_username'])) {
            header('Location: ../patient/login.php');
            exit();
        }
    } elseif ($role === 'admin') {
        if (!isset($_SESSION['admin_username'])) {
            header('Location: ../admin/login.php');
            exit();
        }
    }
}

// Check if patient is logged in - only declare if not already defined
if (!function_exists('isPatientLoggedIn')) {
    function isPatientLoggedIn() {
        return isset($_SESSION['patient_username']) && isset($_SESSION['patient_id']);
    }
}

// Check if admin is logged in - only declare if not already defined
if (!function_exists('isAdminLoggedIn')) {
    function isAdminLoggedIn() {
        return isset($_SESSION['admin_username']) && ($_SESSION['admin_role'] ?? '') == 'admin';
    }
}

// Redirect if not logged in - only declare if not already defined
if (!function_exists('redirectIfNotLoggedIn')) {
    function redirectIfNotLoggedIn($type = 'patient') {
        if ($type == 'patient' && !isPatientLoggedIn()) {
            header("Location: login.php");
            exit();
        }
        if ($type == 'admin' && !isAdminLoggedIn()) {
            header("Location: login.php");
            exit();
        }
    }
}

// Set session message
function setMessage($type, $message) {
    $_SESSION['message'] = [
        'type' => $type,
        'text' => $message
    ];
}

// Get and clear message
function getMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        unset($_SESSION['message']);
        return $message;
    }
    return null;
}

// Set error message
function setError($message) {
    setMessage('danger', $message);
}

// Set success message
function setSuccess($message) {
    setMessage('success', $message);
}

// Destroy session completely
function destroySession() {
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

// CSRF Token functions
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>