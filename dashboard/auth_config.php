<?php
/**
 * Konfigurasi autentikasi untuk dashboard admin
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../api/config.php';

define('SESSION_TIMEOUT', 1800); // 30 minutes

function isLoggedIn() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        return false;
    }
    
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
            session_unset();
            session_destroy();
            return false;
        }
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function getLoggedInUser() {
    if (isLoggedIn() && isset($_SESSION['admin_username'])) {
        return array(
            'username' => $_SESSION['admin_username'],
            'full_name' => $_SESSION['admin_full_name'] ?? 'Admin',
            'email' => $_SESSION['admin_email'] ?? ''
        );
    }
    return null;
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function logLoginAttempt($username, $success, $conn) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $sql = "INSERT INTO login_logs (username, login_time, ip_address, user_agent, success) 
            VALUES (?, NOW(), ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("sssi", $username, $ip, $user_agent, $success);
        $stmt->execute();
        $stmt->close();
    }
}

function escape($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?>