<?php
/**
 * Dashboard Admin - Halaman Utama dengan menu sidebar
 */

require_once 'auth_config.php';
requireLogin();
$user = getLoggedInUser();

// Get active page (default: dashboard)
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IoT Storage Dashboard</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📦</text></svg>">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transition: margin-left 0.3s ease;
            margin-left: 0;
        }

        .hamburger {
            position: absolute;
            top: 48px;
            right: 10px;
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 5px;
            transition: all 0.3s ease;
            z-index: 10;
        }

        .hamburger:hover {
            background: rgba(255,255,255,0.3);
        }

        .sidebar.collapsed {
            transform: translateX(-250px);
        }

        .sidebar.collapsed .hamburger {
            right: -50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 2px 2px 10px rgba(0,0,0,0.3);
        }

        .sidebar.collapsed + .main-content {
            margin-left: -250px;
        }

        @media (max-width: 768px) {
            .hamburger { display: block; }
            .sidebar { position: fixed; z-index: 1000; }
        }

        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            position: relative;
            transition: transform 0.3s ease;
        }
        
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h2 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .sidebar-header p {
            font-size: 12px;
            opacity: 0.8;
        }
        
        .sidebar-menu {
            flex: 1;
            padding: 20px 0;
        }
        
        .menu-item {
            display: block;
            padding: 15px 25px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            position: relative;
            overflow: hidden;
        }
        
        .menu-item::before {
            content: '';
            position: absolute;
            left : 0;
            top : 0;
            height: 100%;
            width: 0;
            background: rgba(255,255,255,0.1);
            transition: all 0.3s ease;

        }

        .menu-item:hover::before {
            width: 100%;
        }

        .menu-item:hover {
            background: rgba(255,255,255,0.1);
            border-left-color: white;
            padding-left: 30px;
        }
        
        .menu-item.active {
            background: rgba(255,255,255,0.2);
            border-left-color: white;
            font-weight: 600;
        }
        
        .menu-item-icon {
            margin-right: 10px;
            font-size: 18px;
        }
        
        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .user-info-sidebar {
            font-size: 13px;
            opacity: 0.9;
            margin-bottom: 10px;
        }
        
        .logout-btn-sidebar {
            display: block;
            width: 100%;
            padding: 10px;
            background: rgba(255,255,255,0.2);
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .logout-btn-sidebar:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .top-bar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .top-bar h1 {
            font-size: 24px;
            color: #333;
        }
        
        .top-bar-right {
            display: flex;
            gap: 10px;
        }
        
        .btn-small {
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .btn-refresh {
            background: #667eea;
            color: white;
        }
        
        .btn-refresh:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            background: #5568d3;
        }
        
        .btn-refresh:active,
        .btn-export:active {
            transform: scale(0.95);
        }

        .btn-pdf {
            background: #dc3545;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-pdf:hover {
            background: #c82333;
            transform: scale(1.05);
        }

        .btn-export {
            background: #28a745;
            color: white;
        }
        
        .btn-export:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            background: #218838;
        }
        
        .content-area {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
            background: #162f52;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #333;
        }
        
        .stat-unit {
            font-size: 16px;
            color: #999;
            margin-left: 5px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-top: 10px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .badge-low {
            background: #fee;
            color: #c33;
        }
        
        .badge-medium {
            background: #ffeaa7;
            color: #d63031;
        }
        
        .badge-full {
            background: #00b894;
            color: white;
        }
        
        .content-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .content-box h2 {
            margin-bottom: 15px;
            color: #333;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        tbody tr {
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

        th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        
        tr {
            transition: all 0.2s ease;
        }

        tr:hover {
            background: #f0f0ff !important;
            transform: scale(1.01);
        }
        
        canvas {
            max-height: 400px;
        }
        
        .timestamp {
            color: #999;
            font-size: 12px;
            text-align: right;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>🏪 Indikator Ketersediaan Barang</h2>
            <p>Monitoring Sistem</p>
            <button class="hamburger" onclick="toggleSidebar()">☰</button>
        </div>
        
        <div class="sidebar-menu">
            <a href="?page=dashboard" class="menu-item <?php echo ($page == 'dashboard') ? 'active' : ''; ?>">
                <span class="menu-item-icon">🏠</span> Dashboard
            </a>
            <a href="?page=logs" class="menu-item <?php echo ($page == 'logs') ? 'active' : ''; ?>">
                <span class="menu-item-icon">📊</span> Log Data
            </a>
            <a href="change_password.php" class="menu-item">
                <span class="menu-item-icon">🔐</span> Ganti Password
            </a>
            <a href="?page=qr_logs" class="menu-item">
                <span class="menu-item-icon">📦</span> Log Kode QR
            </a>
        </div>
        
        <div class="sidebar-footer">
            <div class="user-info-sidebar">
                👤 <?php echo escape($user['full_name']); ?>
            </div>
            <a href="logout.php" class="logout-btn-sidebar">🚪 Logout</a>
        </div>
    </div>
    
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- TOP BAR -->
        <div class="top-bar">
            <h1>
                <?php 
                if ($page == 'dashboard') echo '📈 Dashboard Overview';
                elseif ($page == 'logs') echo '📊 Log Data';
                elseif ($page == 'qr_logs') echo '📦 Log Scan Kode QR';
                else echo '🏠 Dashboard';
                ?>
            </h1>
            <div class="top-bar-right">
                <a href="?page=<?php echo $page; ?>" class="btn-small btn-refresh">🔄 Refresh</a>
                <?php if ($page == 'logs'): ?>
                <button class="btn-small btn-pdf" onclick="exportToPDF()" style="margin-right: 5px;">
                📄 Export ke PDF
                </button>
                <a href="#" onclick="exportToCSV(); return false;" class="btn-small btn-export">📥 Export CSV</a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- CONTENT AREA -->
        <div class="content-area">
            <?php
            require_once '../api/config.php';
            
            $conn = getDBConnection();
            $sql = "SELECT * FROM sensor_data ORDER BY timestamp DESC LIMIT 1";
            $result = $conn->query($sql);
            $latest = $result->fetch_assoc();
            
            if (!$latest) {
                $latest = array(
                    'berat' => 0,
                    'jumlah' => 0,
                    'jarak' => 0,
                    'status' => 'KOSONG',
                    'rfid' => '-',
                    'stock_level' => 'LOW'
                );
            }
            
            $sql_stats = "SELECT 
                            COUNT(*) as total_today,
                            AVG(berat) as avg_weight,
                            AVG(jumlah) as avg_quantity
                          FROM sensor_data 
                          WHERE DATE(timestamp) = CURDATE()";
            $result_stats = $conn->query($sql_stats);
            $stats = $result_stats->fetch_assoc();
            
            if (!$stats) {
                $stats = array(
                    'total_today' => 0,
                    'avg_weight' => 0,
                    'avg_quantity' => 0
                );
            }
            ?>
            
            <?php if ($page == 'dashboard'): ?>
                <!-- DASHBOARD PAGE -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Berat Terkini</div>
                        <div class="stat-value">
                            <?php echo number_format($latest['berat'], 1); ?>
                            <span class="stat-unit">g</span>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-label">Jumlah Item</div>
                        <div class="stat-value">
                            <?php echo $latest['jumlah']; ?>
                            <span class="stat-unit">item</span>
                        </div>
                        <?php
                        $badge_class = 'badge-low';
                        if ($latest['stock_level'] == 'MEDIUM') $badge_class = 'badge-medium';
                        if ($latest['stock_level'] == 'FULL') $badge_class = 'badge-full';
                        ?>
                        <span class="status-badge <?php echo $badge_class; ?>">
                            <?php echo $latest['stock_level']; ?> STOCK
                        </span>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-label">Jarak</div>
                        <div class="stat-value">
                            <?php echo number_format($latest['jarak'], 1); ?>
                            <span class="stat-unit">cm</span>
                        </div>
                        <div class="stat-label" style="margin-top: 10px;">
                            Status: <strong><?php echo $latest['status']; ?></strong>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-label">Pembacaan Hari Ini</div>
                        <div class="stat-value">
                            <?php echo $stats['total_today']; ?>
                            <span class="stat-unit">record</span>
                        </div>
                        <div class="stat-label" style="margin-top: 10px;">
                            Rata-rata: <?php echo number_format($stats['avg_quantity'], 1); ?> item
                        </div>
                    </div>
                </div>

                <!-- LIVE GRAPH -->
                <div class="content-box">
                    <h2>📈 Grafik Perubahan (20 Data Terakhir)</h2>
                    <canvas id="liveChart"></canvas>
                </div>
                
            <?php elseif ($page == 'logs'): ?>
                <!-- DATA LOGS PAGE -->
                <div class="content-box">
                    <h2>Pembacaan Terbaru (50 Data Terakhir)</h2>
                    <table id="dataTable">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Berat (g)</th>
                                <th>Jumlah</th>
                                <th>Jarak (cm)</th>
                                <th>Status</th>
                                <th>Level Stok</th>
                                <th>RFID</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql_recent = "SELECT * FROM sensor_data ORDER BY timestamp DESC LIMIT 50";
                            $result_recent = $conn->query($sql_recent);
                            
                            if ($result_recent && $result_recent->num_rows > 0) {
                                while ($row = $result_recent->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . date('Y-m-d H:i:s', strtotime($row['timestamp'])) . "</td>";
                                    echo "<td>" . number_format($row['berat'], 1) . "</td>";
                                    echo "<td>" . $row['jumlah'] . "</td>";
                                    echo "<td>" . number_format($row['jarak'], 1) . "</td>";
                                    echo "<td>" . $row['status'] . "</td>";
                                    
                                    $badge = 'badge-low';
                                    if ($row['stock_level'] == 'MEDIUM') $badge = 'badge-medium';
                                    if ($row['stock_level'] == 'FULL') $badge = 'badge-full';
                                    
                                    echo "<td><span class='status-badge " . $badge . "'>" . $row['stock_level'] . "</span></td>";
                                    echo "<td>" . ($row['rfid'] != '-' ? $row['rfid'] : '<em>No scan</em>') . "</td>";
                                    echo "</tr>";
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                    
                    <div class="timestamp">
                        Last updated: <?php echo date('Y-m-d H:i:s'); ?>
                    </div>
                </div>

            <?php elseif ($page == 'qr_logs'): ?>
                 <div class="content-box">
                     <h2>🎨 Generator Kode QR</h2>
                        <p style="color:#666; margin-bottom:15px;">Buat Kode QR untuk kotak penyimpanan</p>
        
                 <div style="display:flex; gap:10px; margin-bottom:20px;">
                    <input type="text" id="boxIdInput" placeholder="Masukkan ID Kotak (e.g., BOX001)" 
                   style="flex:1; padding:12px; border:2px solid #e0e0e0; border-radius:5px; font-size:14px;">
                    <button onclick="generateQR()" style="padding:12px 30px; background:#667eea; color:white; border:none; border-radius:5px; cursor:pointer; font-size:14px;">
                    Generate QR
                    </button>
                    <button onclick="downloadQR()" id="downloadBtn" style="display:none; padding:12px 30px; background:#28a745; color:white; border:none; border-radius:5px; cursor:pointer; font-size:14px;">
                    Download
                    </button>
                </div>
        
        <div id="qrContainer" style="display:none; text-align:center; background:#f5f5f5; padding:30px; border-radius:10px;">
            <div id="qrcode"></div>
            <p id="qrBoxId" style="font-weight:bold; font-size:18px; margin-top:15px; color:#333;"></p>
            <p style="font-size:12px; color:#666; margin-top:5px;">Klik kanan pada kode QR dan pilih "Simpan gambar" atau gunakan tombol Download</p>
        </div>
    </div>
                <div class="content-box">
                    <h2>Scan History Kode QR</h2>
                        <table>
            <thead>
                <tr>
                    <th>Scan Time</th>
                    <th>Box ID</th>
                    <th>Method</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql_qr = "SELECT * FROM qr_scans ORDER BY scan_time DESC LIMIT 50";
                $result_qr = $conn->query($sql_qr);
                
                if ($result_qr && $result_qr->num_rows > 0) {
                    while ($row = $result_qr->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . date('Y-m-d H:i:s', strtotime($row['scan_time'])) . "</td>";
                        echo "<td><strong>" . htmlspecialchars($row['box_id']) . "</strong></td>";
                        echo "<td>" . ucfirst($row['scan_method']) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='3' style='text-align:center; padding:20px; color:#999;'>Tidak ada scan QR yang tercatat</td></tr>";
                }
                ?>
            </tbody>
        </table>

         <div class="timestamp">
            Terakhir diupdate: <?php echo date('Y-m-d H:i:s'); ?>
        </div>
    </div>            

            <?php endif; ?>
            
            <?php closeDBConnection($conn); ?>
        </div>
    </div>
    
    <!-- Chart.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <script>
        // Auto-refresh setiap 30 detik
        setTimeout(function() {
            location.reload();
        }, 30000);
        
        // Fungsi Export ke CSV
        function exportToCSV() {
            var table = document.getElementById('dataTable');
            var csv = [];
            
            var headers = [];
            for (var i = 0; i < table.rows[0].cells.length; i++) {
                headers.push(table.rows[0].cells[i].innerText);
            }
            csv.push(headers.join(','));
            
            for (var i = 1; i < table.rows.length; i++) {
                var row = [];
                for (var j = 0; j < table.rows[i].cells.length; j++) {
                    row.push(table.rows[i].cells[j].innerText);
                }
                csv.push(row.join(','));
            }
            
            var csvContent = csv.join('\n');
            var blob = new Blob([csvContent], { type: 'text/csv' });
            var url = window.URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'iot_storage_data_' + new Date().toISOString().slice(0,10) + '.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }

        //Fungsi Export ke PDF
        function exportToPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('l', 'mm', 'a4');
    
        // Judul
        doc.setFontSize(18);
        doc.text('Sistem Indikator Ketersediaan Barang - Log Data', 14, 15);
    
        // Subjudul dengan tanggal
        doc.setFontSize(11);
        doc.setTextColor(100);
        doc.text('Dibuat: ' + new Date().toLocaleString('id-ID'), 14, 22);
    
        // Ambil data dari tabel
        const table = document.getElementById('dataTable');
        const headers = [];
        const data = [];
    
        for (let i = 0; i < table.rows[0].cells.length; i++) {
            headers.push(table.rows[0].cells[i].innerText);
        }
    
        for (let i = 1; i < table.rows.length; i++) {
            const row = [];
        for (let j = 0; j < table.rows[i].cells.length; j++) {
            row.push(table.rows[i].cells[j].innerText);
        }
        data.push(row);
    }
    
        // Buat Tabel
        doc.autoTable({
            head: [headers],
            body: data,
            startY: 28,
            theme: 'grid',
            styles: { fontSize: 9, cellPadding: 3 },
            headStyles: {
                fillColor: [102, 126, 234],
                textColor: 255,
                fontStyle: 'bold'
            },
            alternateRowStyles: {
                fillColor: [245, 245, 245]
            }
        });
    
        // Simpan PDF
        doc.save('IoT_Storage_Data_' + new Date().toISOString().slice(0,10) + '.pdf');
    }

        <?php if ($page == 'dashboard'): ?>
        <?php
        $conn = getDBConnection();
        $sql_chart = "SELECT timestamp, berat, jumlah, jarak 
                      FROM sensor_data 
                      ORDER BY timestamp DESC 
                      LIMIT 20";
        $result_chart = $conn->query($sql_chart);

        $chart_data = array();
        if ($result_chart && $result_chart->num_rows > 0) {
            while ($row = $result_chart->fetch_assoc()) {
                $chart_data[] = $row;
            }
        }
        $chart_data = array_reverse($chart_data);
        closeDBConnection($conn);
        ?>

        var timestamps = [];
        var weightData = [];
        var quantityData = [];
        var distanceData = [];

        <?php foreach ($chart_data as $data): ?>
        timestamps.push('<?php echo date('H:i:s', strtotime($data['timestamp'])); ?>');
        weightData.push(<?php echo $data['berat']; ?>);
        quantityData.push(<?php echo $data['jumlah']; ?>);
        distanceData.push(<?php echo $data['jarak']; ?>);
        <?php endforeach; ?>

        var ctx = document.getElementById('liveChart').getContext('2d');
        var liveChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: timestamps,
                datasets: [
                    {
                        label: 'Berat (g)',
                        data: weightData,
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.1)',
                        yAxisID: 'y',
                        tension: 0.3,
                        borderWidth: 2
                    },
                    {
                        label: 'Jumlah (item)',
                        data: quantityData,
                        borderColor: 'rgb(255, 99, 132)',
                        backgroundColor: 'rgba(255, 99, 132, 0.1)',
                        yAxisID: 'y1',
                        tension: 0.3,
                        borderWidth: 2
                    },
                    {
                        label: 'Jarak (cm)',
                        data: distanceData,
                        borderColor: 'rgb(255, 205, 86)',
                        backgroundColor: 'rgba(255, 205, 86, 0.1)',
                        yAxisID: 'y',
                        tension: 0.3,
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Monitoring Sensor secara real-time'
                    }
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Waktu'
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Berat (g) / Jarak (cm)',
                            color: 'rgb(75, 192, 192)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Jumlah (item)',
                            color: 'rgb(255, 99, 132)'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
        <?php endif; ?>

        <?php if ($page == 'qr_logs'): ?>
        let currentQR = null;
        
        function generateQR() {
            const boxId = document.getElementById('boxIdInput').value.trim();
            if (!boxId) {
                alert('Tolong masukkan ID Kotak');
                return;
            }
            
            // Hapus QR code sebelumnya
            document.getElementById('qrcode').innerHTML = '';
            document.getElementById('qrBoxId').textContent = 'Box ID: ' + boxId;
            
            // Tampilkan container
            document.getElementById('qrContainer').style.display = 'block';
            
            // Buat Kode QR baru
            currentQR = new QRCode(document.getElementById('qrcode'), {
                text: boxId,
                width: 300,
                height: 300,
                colorDark: '#000000',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.H
            });
            
            // Tampilkan tombol download
            document.getElementById('downloadBtn').style.display = 'inline-block';
        }
        
        function downloadQR() {
            const canvas = document.querySelector('#qrcode canvas');
            if (!canvas) {
                alert('Buat Kode QR terlebih dahulu!');
                return;
            }
            
            const boxId = document.getElementById('boxIdInput').value;
            const link = document.createElement('a');
            link.download = boxId + '_QRCode.png';
            link.href = canvas.toDataURL();
            link.click();
        }
        
        // Bolehkan tombol Enter untuk generate QR code
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.getElementById('boxIdInput');
            if (input) {
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        generateQR();
                    }
                });
            }
        });
        <?php endif; ?>

        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            sidebar.classList.toggle('collapsed');

            if (sidebar.classList.contains('collapsed')) {
                mainContent.style.marginLeft = '-250px';
            } else {
                mainContent.style.marginLeft = '0';
            }
        }
    </script>
</body>
</html>