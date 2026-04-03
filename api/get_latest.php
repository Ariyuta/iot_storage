<?php
/**
 * Dapatkan API data sensor terbaru
 */

require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$conn = getDBConnection();

$sql = "SELECT * FROM sensor_data ORDER BY timestamp DESC LIMIT 1";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    http_response_code(200);
    sendJSONResponse(true, "Latest data retrieved", $row);
} else {
    http_response_code(404);
    sendJSONResponse(false, "No data found");
}

closeDBConnection($conn);
?>