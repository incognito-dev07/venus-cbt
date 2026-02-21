<?php
/**
 * Authentication helper functions
 */

function hashPassword($password) {
    $hash_algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
    return password_hash($password, $hash_algo);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function createUserSession($user) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['login_time'] = time();
}

function destroyUserSession() {
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

function generateAuthToken($user_id) {
    return bin2hex(random_bytes(32));
}

function validateAuthToken($token, $user_id) {
    // Implement if using token-based auth
    return true;
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return findUserById($_SESSION['user_id']);
}

function requireGuest() {
    if (isLoggedIn()) {
        header("Location: /");
        exit();
    }
}

function checkPasswordStrength($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    return $errors;
}
?>