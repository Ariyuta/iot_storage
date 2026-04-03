<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scanner Kode QR</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 600px; margin: 0 auto; }
        .header { text-align: center; color: white; margin-bottom: 30px; }
        .scanner-box {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        #reader { border-radius: 10px; overflow: hidden; margin-bottom: 20px; }
        .status {
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            text-align: center;
            font-weight: bold;
        }
        .status.success { background: #d4edda; color: #155724; }
        .status.error { background: #f8d7da; color: #721c24; }
        .status.info { background: #d1ecf1; color: #0c5460; }
        .scan-history { margin-top: 20px; max-height: 300px; overflow-y: auto; }
        .scan-item {
            padding: 10px;
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        .scan-item strong { color: #667eea; }
        .btn {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }
        .btn:hover { background: #5568d3; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📦 Scanner Kode QR</h1>
            <p>Sistem Indikator Ketersediaan Barang</p>
        </div>
        
        <div class="scanner-box">
            <div id="reader"></div>
            <div id="status" style="display:none;"></div>
            <button class="btn" onclick="location.reload()">🔄 Reset Scanner</button>
            <div class="scan-history" id="history"></div>
        </div>
    </div>

    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        const statusDiv = document.getElementById('status');
        const historyDiv = document.getElementById('history');
        
        function showStatus(message, type) {
            statusDiv.style.display = 'block';
            statusDiv.className = 'status ' + type;
            statusDiv.textContent = message;
        }
        
        function addToHistory(boxId, data) {
            const item = document.createElement('div');
            item.className = 'scan-item';
            item.innerHTML = `<strong>${boxId}</strong><br><small>Time: ${data.timestamp}</small>`;
            historyDiv.insertBefore(item, historyDiv.firstChild);
        }
        
        function onScanSuccess(decodedText, decodedResult) {
            console.log('Scanned:', decodedText);
            showStatus('Processing...', 'info');
            
            const formData = new FormData();
            formData.append('box_id', decodedText);
            
            fetch('/iot_storage/api/scan_qr.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response:', data);
                if (data.success) {
                    showStatus('✅ Scanned: ' + decodedText, 'success');
                    addToHistory(decodedText, data.data);
                } else {
                    showStatus('❌ ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showStatus('❌ Error: ' + error.message, 'error');
            });
            
            html5QrcodeScanner.pause();
            setTimeout(() => html5QrcodeScanner.resume(), 2000);
        }
        
        const html5QrcodeScanner = new Html5QrcodeScanner(
            "reader",
            { fps: 10, qrbox: { width: 250, height: 250 } },
            false
        );
        
        html5QrcodeScanner.render(onScanSuccess, () => {});
        showStatus('Buka kamera untuk scan QR', 'info');
    </script>
</body>
</html>