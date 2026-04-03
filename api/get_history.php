<?php
/**
 * Dapatkan History API data sensor
 */

require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
$date = isset($_GET['date']) ? sanitizeInput($_GET['date']) : null;
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : null;

if ($limit < 1 || $limit > 10000) {
    $limit = 100;
}

$conn = getDBConnection();

$sql = "SELECT * FROM sensor_data WHERE 1=1";

if ($date !== null) {
    $sql .= " AND DATE(timestamp) = '" . $conn->real_escape_string($date) . "'";
}

if ($status !== null && in_array($status, ['TERISI', 'KOSONG'])) {
    $sql .= " AND status = '" . $conn->real_escape_string($status) . "'";
}

$sql .= " ORDER BY timestamp DESC LIMIT " . $limit;

$result = $conn->query($sql);

if ($result) {
    $data = array();
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    http_response_code(200);
    sendJSONResponse(true, "Historical data retrieved", array(
        'count' => count($data),
        'records' => $data
    ));
} else {
    http_response_code(500);
    sendJSONResponse(false, "Database error: " . $conn->error);
}

closeDBConnection($conn);
?>