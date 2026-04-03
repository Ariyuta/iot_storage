<?php
/**
 * Masukkan API Data Sensor
 * Dapatkan POST data dari ESP32 dan simpan ke database
 */

require_once 'config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    sendJSONResponse(false, "Method not allowed. Use POST.");
}

// Dapatkan POST data dari ESP32
$berat = isset($_POST['berat']) ? floatval($_POST['berat']) : null;
$jumlah = isset($_POST['jumlah']) ? intval($_POST['jumlah']) : null;
$jarak = isset($_POST['jarak']) ? floatval($_POST['jarak']) : null;
$status = isset($_POST['status']) ? sanitizeInput($_POST['status']) : null;
$rfid = isset($_POST['rfid']) ? sanitizeInput($_POST['rfid']) : "-";

// Validasi field yang diperlukan
if ($berat === null || $jumlah === null || $jarak === null || $status === null) {
    http_response_code(400);
    sendJSONResponse(false, "Missing required parameters: berat, jumlah, jarak, status");
}

// Validasi rentang data untuk mencegah input yang tidak masuk akal
if ($berat < 0 || $berat > 100000) {
    http_response_code(400);
    sendJSONResponse(false, "Invalid berat value (must be 0-100000)");
}

if ($jumlah < 0 || $jumlah > 1000) {
    http_response_code(400);
    sendJSONResponse(false, "Invalid jumlah value (must be 0-1000)");
}

if ($jarak < 0 || $jarak > 1000) {
    http_response_code(400);
    sendJSONResponse(false, "Invalid jarak value (must be 0-1000)");
}

if (!in_array($status, ['TERISI', 'KOSONG'])) {
    http_response_code(400);
    sendJSONResponse(false, "Invalid status value (must be TERISI or KOSONG)");
}

// Hitung stock level berdasarkan jumlah
$stock_level = calculateStockLevel($jumlah);

// Dapatkan koneksi database
$conn = getDBConnection();

// Siapkan statement SQL untuk memasukkan data sensor
$sql = "INSERT INTO sensor_data (timestamp, berat, jumlah, jarak, status, rfid, stock_level) 
        VALUES (NOW(), ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    closeDBConnection($conn);
    sendJSONResponse(false, "Database error: " . $conn->error);
}

// Mengikat Parameter
$stmt->bind_param("dddsss", $berat, $jumlah, $jarak, $status, $rfid, $stock_level);

// eksekusi query dan dapatkan ID insert untuk referensi
if ($stmt->execute()) {
    $insert_id = $stmt->insert_id;
    
    // Update RFID last_scan jika terdaftar
    if ($rfid !== "-") {
        $sql_rfid = "UPDATE rfid_users SET last_scan = NOW() WHERE rfid_uid = ?";
        $stmt_rfid = $conn->prepare($sql_rfid);
        if ($stmt_rfid) {
            $stmt_rfid->bind_param("s", $rfid);
            $stmt_rfid->execute();
            $stmt_rfid->close();
        }
    }
    
    http_response_code(200);
    sendJSONResponse(true, "Data inserted successfully", array(
        'id' => $insert_id,
        'berat' => $berat,
        'jumlah' => $jumlah,
        'jarak' => $jarak,
        'status' => $status,
        'rfid' => $rfid,
        'stock_level' => $stock_level
    ));
} else {
    http_response_code(500);
    sendJSONResponse(false, "Failed to insert data: " . $stmt->error);
}

$stmt->close();
closeDBConnection($conn);
?>