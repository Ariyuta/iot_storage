<?php
/**
 * Konfigurasi Database
 * Sistem Indikator Ketersediaan Barang
 */

// Kredential database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'iot_storage_db');

// Konfigurasi zona waktu
date_default_timezone_set('Asia/Jakarta');

// Laporan error
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fungsi koneksi database
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

// Fungsi untuk menutup koneksi database
function closeDBConnection($conn) {
    if ($conn) {
        $conn->close();
    }
}

// Sanitisasi input data
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Hitung stock level berdasarkan jumlah
function calculateStockLevel($jumlah) {
    if ($jumlah <= 2) {
        return "LOW";
    } elseif ($jumlah <= 5) {
        return "MEDIUM";
    } else {
        return "FULL";
    }
}

// Fungsi untuk mengirim respons JSON
function sendJSONResponse($success, $message, $data = null) {
    header('Content-Type: application/json');
    $response = array(
        'success' => $success,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    );
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}
?>