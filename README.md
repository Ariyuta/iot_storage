[README.md](https://github.com/user-attachments/files/28420903/README.md)
# IoT Storage Monitoring System

A real-time shelf inventory monitoring system built with ESP32, multiple sensors, and a local web dashboard — designed for small-to-medium businesses as an affordable alternative to cloud-based inventory solutions.

---

## Overview

Manual inventory management is prone to human error, delayed updates, and data inconsistencies. This project addresses those challenges by automating shelf monitoring using IoT sensors, providing real-time visual indicators and a web-based dashboard — all running locally without cloud dependency.

---

## Features

-  **Weight-based item detection** using Load Cell (HX711)
- **Distance-based shelf monitoring** using Ultrasonic Sensor (HC-SR04)
- **User identification** via RFID card scanning (RC522)
- **Visual stock indicators** with LED traffic light (Red / Yellow / Green)
- **Audio notification** via buzzer on stock level change
- **Local LCD display** for real-time on-device readings
- **Web dashboard** with live graph, data table, and statistics
- **Export data** to PDF and CSV
- **QR code scanning** via smartphone for box identification
- **Auto WiFi reconnect** with non-blocking state machine
- **Dual-trigger failsafe**: if ultrasonic fails, weight sensor acts as backup

---

## Tech Stack

### Hardware
| Component | Role |
|-----------|------|
| ESP32 | Main microcontroller |
| Load Cell + HX711 | Weight measurement |
| HC-SR04 | Distance / shelf fullness detection |
| RFID RC522 | User identification |
| LCD I2C (16x2) | Local display |
| LED (Red, Yellow, Green) | Stock level indicator |
| Active Buzzer | Audio alert on status change |

### Software
| Technology | Usage |
|------------|-------|
| C++ (Arduino IDE) | ESP32 firmware |
| PHP | Backend & dashboard logic |
| MySQL | Local database (via XAMPP) |
| HTML & CSS | Dashboard UI |
| JavaScript | Live charts, export, QR scanner |
| Chart.js | Real-time data visualization |
| jsPDF + AutoTable | PDF export |
| html5-qrcode | QR code scanning via browser |

---

## Project Structure

```
iot_storage/
├── assets/
│   ├── css/
│   │   ├── dashboard.css       # Dashboard styling
│   │   └── scanner.css         # Scanner page styling
│   └── js/
│       ├── chart.js            # Live graph logic
│       ├── export.js           # CSV & PDF export
│       ├── qr-generator.js     # QR code generator
│       └── sidebar.js          # Sidebar toggle & animations
├── includes/
│   ├── auth.php                # Session authentication
│   ├── config.php              # Database connection
│   ├── header.php              # Common HTML head
│   └── sidebar.php             # Sidebar navigation
├── api/
│   ├── config.php              # API DB config
│   ├── insert_data.php         # Receive data from ESP32
│   └── scan_qr.php             # Receive QR scan from phone
├── pages/
│   ├── dashboard.php           # Dashboard page content
│   ├── logs.php                # Data logs page content
│   └── qr_logs.php             # QR scan logs page content
├── index.php                   # Main entry point & router
├── login.php                   # Login page
├── logout.php                  # Logout handler
├── change_password.php         # Password settings
├── scan.php                    # QR code scanner (phone)
└── generate_qr.php             # QR code generator
```

---

## System Architecture

```
                    ┌─────────────────────────────┐
                    │           ESP32              │
                    │                              │
        ┌───────────┤  Load Cell   Ultrasonic      │
        │           │  RFID RC522  LCD I2C         │
        │           │  LED x3      Buzzer          │
        │           └──────────┬───────────────────┘
        │                      │ HTTP POST (WiFi)
        │                      ▼
        │           ┌─────────────────────────────┐
        │           │      XAMPP Local Server      │
        │           │  Apache + MySQL + PHP        │
        │           │                              │
        │           │  api/insert_data.php         │
        │           │  api/scan_qr.php             │
        │           │  database: iot_storage_db    │
        │           └──────────┬───────────────────┘
        │                      │
        │           ┌──────────▼───────────────────┐
        └──────────►│     Web Dashboard            │
                    │  Real-time stats & graphs    │
                    │  Data logs + export          │
                    │  QR code scanner & generator │
                    └─────────────────────────────┘
```

---

## Getting Started

### Prerequisites

- [XAMPP](https://www.apachefriends.org/) (Apache + MySQL)
- [Arduino IDE](https://www.arduino.cc/en/software)
- ESP32 board with all sensors wired (see wiring section)
- Browser (Chrome recommended for QR scanner)

---

### 1. Database Setup

1. Start **Apache** and **MySQL** in XAMPP Control Panel
2. Open [phpMyAdmin](http://localhost/phpmyadmin)
3. Create database: `iot_storage_db`
4. Import the SQL schema:

```sql
CREATE TABLE sensor_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    berat DECIMAL(10,2) DEFAULT 0,
    jumlah INT DEFAULT 0,
    jarak DECIMAL(10,2) DEFAULT 0,
    status VARCHAR(20) DEFAULT 'KOSONG',
    rfid VARCHAR(50) DEFAULT '-',
    stock_level VARCHAR(10) DEFAULT 'LOW',
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE qr_scans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    box_id VARCHAR(50) NOT NULL,
    scan_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    scan_method VARCHAR(20) DEFAULT 'phone'
);
```

---

### 2. Dashboard Setup

1. Copy the `iot_storage/` folder to:
   ```
   C:/xampp/htdocs/iot_storage/
   ```
2. Open browser and go to:
   ```
   http://localhost/iot_storage/login.php
   ```
3. Login with default credentials:
   ```
   Username: admin
   Password: admin
   ```

---

### 3. ESP32 Firmware Setup

1. Open Arduino IDE
2. Install required libraries via Library Manager:
   - `HX711_ADC` by Olav Kallhovd
   - `MFRC522` by GithubCommunity
   - `LiquidCrystal_I2C` by Frank de Brabander
3. Open `firmware/main.ino`
4. Update WiFi and server configuration:

```cpp
const char* ssid       = "YOUR_WIFI_SSID";
const char* password   = "YOUR_WIFI_PASSWORD";
const char* serverName = "http://YOUR_PC_IP/iot_storage/api/insert_data.php";
```

5. Select board: **ESP32 Dev Module**
6. Upload to ESP32

---

### 4. QR Scanner (Phone)

1. Connect phone to **same WiFi network** as PC
2. Open phone browser and go to:
   ```
   http://YOUR_PC_HOSTNAME/iot_storage/scan.php
   ```
3. Allow camera access
4. Scan box QR codes generated from the dashboard

---

## API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/insert_data.php` | POST | Receive sensor data from ESP32 |
| `/api/scan_qr.php` | POST | Receive QR scan from phone |

### insert_data.php Parameters:
```
berat   = weight in grams
jumlah  = estimated item count
jarak   = distance in cm
status  = TERISI / KOSONG
rfid    = scanned card UID (or "-")
```

### scan_qr.php Parameters:
```
box_id  = scanned QR code content (e.g. BOX001)
```

---

## Wiring Reference

| Component | Pin | ESP32 GPIO |
|-----------|-----|------------|
| HX711 DOUT | DT | GPIO 25 |
| HX711 SCK | SCK | GPIO 33 |
| HC-SR04 | TRIG | GPIO 16 |
| HC-SR04 | ECHO | GPIO 17 |
| RFID RC522 | SS | GPIO 18 |
| RFID RC522 | RST | GPIO 19 |
| LCD I2C | SDA | GPIO 21 |
| LCD I2C | SCL | GPIO 22 |
| LED Red | - | GPIO 32 |
| LED Yellow | - | GPIO 13 |
| LED Green | - | GPIO 14 |
| Buzzer | - | GPIO 15 |

---

## Stock Level Logic

| Item Count | LED Color | Status |
|------------|-----------|--------|
| 0 - 2 items | 🔴 Red | Low Stock |
| 3 - 5 items | 🟡 Yellow | Medium Stock |
| 6+ items | 🟢 Green | Full Stock |

---

## Failsafe Mechanism

If the ultrasonic sensor fails or returns an invalid reading, the system automatically switches to **weight-based detection** as backup:

```
If ultrasonic fails:
  → Distance estimated from item count × 5cm per item
  → Status determined by weight threshold (≥ 50g = TERISI)
```

This ensures the dashboard always displays meaningful data even when one sensor malfunctions.

---

## Sensor Accuracy

| Sensor | Tests | Average Error | Accuracy |
|--------|-------|---------------|----------|
| Load Cell (HX711) | 17 | 5.83% | 94.17% |
| Ultrasonic (HC-SR04) | 15 | 2.59% | 97.41% |

---

## Screenshots

> Dashboard Overview, Data Logs, QR Scanner, and LED Indicator photos can be added here.

---

## Future Improvements

- [ ] Refactor to full REST API architecture
- [ ] HTTPS support for secure data transmission
- [ ] Multi-rack support with unique rack identifiers
- [ ] Push notification / email alert on low stock
- [ ] Mobile app (Android/iOS) for better UX
- [ ] Predictive stock analytics using historical data
- [ ] Role-based access control (Admin, Operator, Viewer)
- [ ] Docker containerization for easier deployment

---

## License

This project is open-source and available under the [MIT License](LICENSE).

---

## Author

**Aru**
- GitHub: [@ariyuta](https://github.com/ariyuta)
- Project Type: Undergraduate Thesis — IoT System

---

> Built as an undergraduate thesis project. Designed to be an affordable, offline-capable inventory monitoring solution for small businesses and local warehouses.
