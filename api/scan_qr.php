<?php
// Suppress all errors from output
error_reporting(0);
ini_set('display_errors', 0);

// JSON headers first
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Include config
require_once 'config.php';

// Check POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

try {
    // Get connection
    $conn = getDBConnection();
    
    // Get box_id from POST
    $box_id = isset($_POST['box_id']) ? trim($_POST['box_id']) : '';
    
    if (empty($box_id)) {
        echo json_encode([
            'success' => false,
            'message' => 'Box ID diperlukan'
        ]);
        exit;
    }
    
    $sql = "INSERT INTO qr_scans (box_id, scan_method) VALUES (?, 'phone')";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Database gagal disiapkan');
    }
    
    $stmt->bind_param("s", $box_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Kode QR berhasil disimpan',
            'data' => [
                'box_id' => $box_id,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        throw new Exception('Gagal menyimpan kode QR');
    }
    
    $stmt->close();
    closeDBConnection($conn);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>