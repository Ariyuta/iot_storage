<?php
/**
 * Dapatkan statistik API
 */

require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$period = isset($_GET['period']) ? sanitizeInput($_GET['period']) : 'all';

$conn = getDBConnection();

$where = "1=1";
switch ($period) {
    case 'today':
        $where = "DATE(timestamp) = CURDATE()";
        break;
    case 'week':
        $where = "timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $where = "timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
}

$sql = "SELECT 
            COUNT(*) as total_records,
            AVG(berat) as avg_berat,
            MIN(berat) as min_berat,
            MAX(berat) as max_berat,
            AVG(jumlah) as avg_jumlah,
            MIN(jumlah) as min_jumlah,
            MAX(jumlah) as max_jumlah,
            AVG(jarak) as avg_jarak,
            MIN(jarak) as min_jarak,
            MAX(jarak) as max_jarak,
            SUM(CASE WHEN status = 'TERISI' THEN 1 ELSE 0 END) as terisi_count,
            SUM(CASE WHEN status = 'KOSONG' THEN 1 ELSE 0 END) as kosong_count,
            SUM(CASE WHEN stock_level = 'LOW' THEN 1 ELSE 0 END) as low_stock_count,
            SUM(CASE WHEN stock_level = 'MEDIUM' THEN 1 ELSE 0 END) as medium_stock_count,
            SUM(CASE WHEN stock_level = 'FULL' THEN 1 ELSE 0 END) as full_stock_count
        FROM sensor_data
        WHERE $where";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $stats = $result->fetch_assoc();
    
    $stats['avg_berat'] = round($stats['avg_berat'], 2);
    $stats['avg_jumlah'] = round($stats['avg_jumlah'], 2);
    $stats['avg_jarak'] = round($stats['avg_jarak'], 2);
    
    http_response_code(200);
    sendJSONResponse(true, "Statistics retrieved", array(
        'period' => $period,
        'statistics' => $stats
    ));
} else {
    http_response_code(404);
    sendJSONResponse(false, "No data available for statistics");
}

closeDBConnection($conn);
?>