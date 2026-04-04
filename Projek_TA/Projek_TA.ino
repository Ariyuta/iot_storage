/*************************************************
 * Indikator Ketersediaan Barang - ESP32
 * Sensor  : Load Cell (HX711), Ultrasonic
 * Output  : LCD I2C, Traffic Light LEDs, Buzzer
 * ID      : RFID (RC522)
 * Network : WiFi + HTTP POST
 *************************************************/

#include <Arduino.h>
#include <HX711_ADC.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <SPI.h>
#include <MFRC522.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>

// =============== KONFIGURASI ================= //
// ===== WiFi =====
const char* ssid = "untan";
const char* password = "";
const char* serverName = "http://10.78.15.204/iot_storage/api/insert_data.php";

// http://IP_SERVER/api/insert_data.php
// ===== HX711 =====
#define HX711_DOUT 25 // ungu
#define HX711_SCK  33 // putih

// ===== Ultrasonic =====
#define TRIG_PIN 16
#define ECHO_PIN 17

// ===== RFID RC522 =====
#define RFID_SS  18
#define RFID_RST 19

// ===== LCD I2C =====
#define LCD_SDA 21
#define LCD_SCL 22
// Penting: Ubah ke 0x3F jika LCD tidak bekerja dengan 0x27
LiquidCrystal_I2C lcd(0x27, 16, 2);

// ===== Output =====
#define LED_RED     32
#define LED_YELLOW  13
#define LED_GREEN   14
#define BUZZER_PIN  15

// =============== OBJEK ======================== //

HX711_ADC scale(HX711_DOUT, HX711_SCK);
MFRC522 mfrc522(RFID_SS, RFID_RST);

// =============== PARAMETER =================== //

float calibration_factor = 180.5;
float berat_per_item     = 240.00;
float batas_jarak        = 30.0;

int threshold_low    = 2;
int threshold_medium = 5;

// =============== VARIABEL ==================== //

// ===== WiFi State Machine =====
enum WiFiState { WIFI_OK, WIFI_RECONNECTING };
WiFiState wifiState = WIFI_OK;
unsigned long wifi_reconnect_start = 0;
int wifi_reconnect_attempt = 0;

float berat_gram = 0;
int   estimasi_jumlah = 0;
float distance_cm = 0;
String rfid_uid = "-";

float last_berat = -1;
int last_jumlah = -1;
float last_jarak = -1;
String last_status = "";
unsigned long last_rfid_time = 0;
unsigned long rfid_timeout = 10000;

String status_rak = "KOSONG";

// =============== FUNGSI ====================== //

void bunyiBeep(int durasi) {
  digitalWrite(BUZZER_PIN, LOW);
  delay(durasi);
  digitalWrite(BUZZER_PIN, HIGH);
}

void setTrafficLight(int jumlah_item) {
  //*
  static int last_led_state = -1; // melacak kondisi LED sebelumnya
  int current_led_state;

  // menentukan kondisi saat ini
  if (jumlah_item <= threshold_low) {
    current_led_state = 0; // RED
  }
  else if (jumlah_item >= 3 && jumlah_item <= threshold_medium) {
    current_led_state = 1; // YELLOW
  }
  else {
    current_led_state = 2; // GREEN
  }

  // jika kondisi berubah, bunyikan buzzer
  if (current_led_state != last_led_state && last_led_state != -1) {
    digitalWrite(BUZZER_PIN, LOW);
    delay(100);
    digitalWrite(BUZZER_PIN, HIGH);
  }
  //*
  digitalWrite(LED_RED, LOW);
  digitalWrite(LED_YELLOW, LOW);
  digitalWrite(LED_GREEN, LOW);

  if (current_led_state == 0) {
    digitalWrite(LED_RED, HIGH);
  }
  else if (current_led_state == 1) {
    digitalWrite(LED_YELLOW, HIGH);
  }
  else {
    digitalWrite(LED_GREEN, HIGH);
  }

  last_led_state = current_led_state; // simpan kondisi saat ini
}

void cekWiFi() {
  // Jika sudah tersambung, tidak perlu apa-apa
  if (WiFi.status() == WL_CONNECTED) {
    wifiState = WIFI_OK;
    return;
  }

  // Jika baru saja terputus, mulai proses reconnect
  if (wifiState == WIFI_OK) {
    Serial.println("WiFi terputus, mencoba reconnect...");
    lcd.setCursor(0, 0);
    lcd.print("WiFi Lost...    ");
    WiFi.disconnect();
//    delay(100);
    WiFi.begin(ssid, password);
    wifiState = WIFI_RECONNECTING;
    wifi_reconnect_start = millis();
    wifi_reconnect_attempt = 0;
    return; // keluar, tunggu loop berikutnya
  }

  // Jika sedang reconnecting, tunggu hasil setiap 500ms
  if (wifiState == WIFI_RECONNECTING) {
    unsigned long now = millis();

    // Status Berhasil connect
    if (WiFi.status() == WL_CONNECTED) {
      Serial.println("\nWiFi reconnected!");
      Serial.println("IP: " + WiFi.localIP().toString());
      lcd.setCursor(0, 0);
      lcd.print("WiFi OK         ");
      wifiState = WIFI_OK;
      wifi_reconnect_attempt = 0;
      return;
    }

    // Cek progress setiap 500ms tidak setiap loop
    if (now - wifi_reconnect_start < (unsigned long)wifi_reconnect_attempt * 500) {
      return; // belum waktunya, keluar tanpa melakukan apapun
    }

    Serial.print(".");
    wifi_reconnect_attempt++;

    // Gagal setelah 20x percobaan (~10 detik)
    if (wifi_reconnect_attempt >= 20) {
      Serial.println("\nWiFi gagal reconnect, coba lagi 10 detik...");
      lcd.setCursor(0, 0);
      lcd.print("WiFi Failed!    ");
      wifiState = WIFI_OK; // reset agar dicoba lagi via lastWiFiCheck
      wifi_reconnect_attempt = 0;
    }
  }
}

// =============== VOID LOAD CELL ============== //

// ===== Tambah variabel global =====
portMUX_TYPE mux = portMUX_INITIALIZER_UNLOCKED;

// ===== Task HX711 di Core 0 =====
void taskLoadCell(void *parameter) {
  for (;;) {
    portENTER_CRITICAL(&mux);
    if (scale.update()) {
      float raw = scale.getData();
      if (raw < 5.0) raw = 0;
      berat_gram = raw;
      estimasi_jumlah = (int)(berat_gram / berat_per_item);
    }
    portEXIT_CRITICAL(&mux);
    vTaskDelay(1 / portTICK_PERIOD_MS); // yield 1ms
  }
}

void bacaLoadCell() {
  static boolean newDataReady = 0;

  if (scale.update()) newDataReady = true;

  if (newDataReady) {
    float raw = scale.getData();
    if (raw < 5.0) raw = 0;      // buang noise di bawah 5g
    berat_gram = raw;
    estimasi_jumlah = (int)(berat_gram / berat_per_item);
    newDataReady = false;
  }
}

void bacaUltrasonic() {
  static unsigned long lastTrigger = 0;
  static unsigned long echoStart   = 0;
  static bool          waitingEcho = false;

  unsigned long now = millis();
  unsigned long nowUs = micros();

  // --- FASE TRIGGER: setiap 500ms ---
  if (!waitingEcho && (now - lastTrigger >= 500)) {
    digitalWrite(TRIG_PIN, LOW);
    delayMicroseconds(2);
    digitalWrite(TRIG_PIN, HIGH);
    delayMicroseconds(10);
    digitalWrite(TRIG_PIN, LOW);

    echoStart   = micros();   // simpan waktu mulai tunggu echo
    waitingEcho = true;
    lastTrigger = now;
  }

  // --- FASE BACA ECHO: polling tanpa blocking ---
  if (waitingEcho) {
    // Timeout jika echo tidak datang dalam 25ms
    if (micros() - echoStart > 25000) {
      waitingEcho = false;
    // Perhitungan jarak berdasarkan jumlah barang
    // Formula: 30cm - (items x 5cm per item)
      if (estimasi_jumlah > 0) {
        distance_cm = 30.0 - (estimasi_jumlah * 5.0);
        if (distance_cm < 0) distance_cm = 0.5;    
      } else {
        distance_cm = 29.5; //Rak Kosong (mendekati 30cm)
      }

    // Perhitungan status berdasarkan berat
      if (berat_gram >= 50.0) {
        status_rak = "TERISI";
    } else {
        status_rak = "KOSONG";
    }
      return;
  }

    // Tunggu rising edge
    if (digitalRead(ECHO_PIN) == HIGH) {
      unsigned long pulseStart = micros();

      // Tunggu falling edge (blocking kecil, aman <5ms)
      while (digitalRead(ECHO_PIN) == HIGH) {
        if (micros() - pulseStart > 25000) break; // safety break
      }

      long duration = micros() - pulseStart;
      float cm = duration * 0.034 / 2.0;

  if (cm > 0 && cm < 400) {
        distance_cm = cm;
      // Primary: Gunakan Ultrasonik
      if (cm < batas_jarak) {
        status_rak = "TERISI";
      } else {
        status_rak = "KOSONG";
      }
  } else {
      if (estimasi_jumlah > 0) {
    distance_cm = 30.0 - (estimasi_jumlah * 5.0);
    if (distance_cm < 0) distance_cm = 0.5;
    } else {
    distance_cm = 29.5;
    }
      // Ultrasonik gagal (0cm atau invalid)
     if (berat_gram >= 50.0) {
       status_rak = "TERISI";
     } else {
        status_rak = "KOSONG";
      }
  }

      waitingEcho = false;
    }
  }
}

String bacaRFID() {
  if (!mfrc522.PICC_IsNewCardPresent()) return "";
  if (!mfrc522.PICC_ReadCardSerial()) return "";

  String uid = "";
  for (byte i = 0; i < mfrc522.uid.size; i++) {
    if (mfrc522.uid.uidByte[i] < 0x10) uid += "0";
    uid += String(mfrc522.uid.uidByte[i], HEX);
  }
  uid.toUpperCase();

  mfrc522.PICC_HaltA();
  return uid;
}

void kirimKeServer(float berat, int jumlah, float jarak,
                   String status, String uid) {

  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("Tidak ada WiFi, skip kirim data");
    return;
  }

  HTTPClient http;
  http.begin(serverName);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");

  String postData =
    "berat=" + String(berat, 1) +
    "&jumlah=" + String(jumlah) +
    "&jarak=" + String(jarak, 1) +
    "&status=" + status +
    "&rfid=" + uid;

  int httpCode = http.POST(postData);
  
  if (httpCode > 0) {
    Serial.println("Server response: " + String(httpCode));
  } else {
    Serial.println("Error kirim data: " + http.errorToString(httpCode));
  }
  
  http.end();
}

// =============== SETUP ======================= //

void setup() {
  Serial.begin(115200);
  delay(1000);
  Serial.println("\n=== Sistem Indikator Barang Starting ===");

  pinMode(LED_RED, OUTPUT);
  pinMode(LED_YELLOW, OUTPUT);
  pinMode(LED_GREEN, OUTPUT);
  pinMode(BUZZER_PIN, OUTPUT);
  
  digitalWrite(LED_RED, LOW);
  digitalWrite(LED_YELLOW, LOW);
  digitalWrite(LED_GREEN, LOW);
  digitalWrite(BUZZER_PIN, HIGH);

  Serial.println("Initializing LCD...");
  Wire.begin(LCD_SDA, LCD_SCL);
  delay(100);
  
  lcd.begin();
  lcd.backlight();
  delay(100);
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("IoT Storage");
  lcd.setCursor(0, 1);
  lcd.print("Initializing...");
  delay(1000);
  
  Serial.println("LCD initialized");

  Serial.println("Initializing HX711...");
  scale.begin();
  scale.setCalFactor(1.0);                    // ← set 1.0 dulu
  unsigned long stabilizingtime = 2000;
  scale.start(stabilizingtime, true);
  
  if (scale.getTareTimeoutFlag()) {
    Serial.println("Timeout, cek wiring HX711!");
    lcd.clear();
    lcd.print("HX711 Error!");
    digitalWrite(LED_RED, HIGH);
    bunyiBeep(1000);
    while (1);
  }
  while (!scale.update());  // tunggu HX711 benar-benar siap

  //
  Serial.println("Re-taring scale to zero...");
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("Calibrating...");
  lcd.setCursor(0, 1);
  lcd.print("Please wait");
  
  scale.tare();                           // ← RESET ZERO
  delay(2000);                            // Tunggu sampai stabil
  
  Serial.println("Tare complete!");
  lcd.clear();
  lcd.print("Tare OK!");
  delay(1000);
  //
  
  scale.setCalFactor(calibration_factor);
  Serial.println("HX711 ready");

  // Jalankan loadcell di core 0, WiFi otomatis di core 0 tapi task ini prioritasnya lebih tinggi
  xTaskCreatePinnedToCore(
    taskLoadCell,    // fungsi task
    "LoadCell",      // nama
    2048,            // stack size
    NULL,            // parameter
    2,               // prioritas (lebih tinggi dari WiFi)
    NULL,            // handle
    0                // core 0
  );

  Serial.println("Initializing HC-SR04...");
  pinMode(TRIG_PIN, OUTPUT);
  pinMode(ECHO_PIN, INPUT);
  digitalWrite(TRIG_PIN, LOW);
  delay(100);
  Serial.println("HC-SR04 ready");

  Serial.println("Initializing RFID...");
  SPI.begin();
  mfrc522.PCD_Init();
  delay(100);
  Serial.println("RFID ready");

  Serial.println("Testing buzzer...");
  bunyiBeep(100);
  delay(100);
  bunyiBeep(100);

  Serial.println("Connecting to WiFi...");
  WiFi.begin(ssid, password);
  lcd.clear();
  lcd.print("Connecting WiFi");
  
  int wifi_attempt = 0;
  while (WiFi.status() != WL_CONNECTED && wifi_attempt < 40) {
    delay(500);
    Serial.print(".");
    wifi_attempt++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    lcd.clear();
    lcd.print("WiFi Connected");
    Serial.println("\nWiFi connected!");
    Serial.println("IP: " + WiFi.localIP().toString());
    bunyiBeep(200);
    delay(1500);
  } else {
    lcd.clear();
    lcd.print("WiFi Failed!");
    Serial.println("\nWiFi gagal connect!");
    delay(1000);
  }

  setTrafficLight(0);
  
  lcd.clear();
  lcd.print("System Ready!");
  delay(1000);
  
  Serial.println("=== System Ready ===\n");
}

// =============== LOOP ======================== //

void loop() {

  unsigned long now = millis();

  // ===================================================
  // 2. BACA RFID — non-blocking, cek setiap loop
  // ===================================================
  String uid_baru = bacaRFID();
//    String uid_baru = ""; 
  if (uid_baru != "") {
    rfid_uid = uid_baru;
    last_rfid_time = now;
    digitalWrite(BUZZER_PIN, LOW);   // beep non-blocking mulai
    Serial.println("RFID detected: " + rfid_uid);
  }

  // Matikan buzzer setelah 150ms (non-blocking beep)
  static unsigned long buzzer_start = 0;
  static boolean buzzer_active = false;
  if (uid_baru != "") {
    buzzer_active = true;
    buzzer_start = now;
  }
  if (buzzer_active && (now - buzzer_start >= 150)) {
    digitalWrite(BUZZER_PIN, HIGH);
    buzzer_active = false;
  }

  // Reset RFID jika timeout
  if (now - last_rfid_time > rfid_timeout && rfid_uid != "-") {
    rfid_uid = "-";
    Serial.println("RFID timeout, reset to default");
  }

// ===================================================
  // 3. BACA ULTRASONIC — state machine non-blocking
  //    Setiap ping dipisah per iterasi loop
  //    sehingga load cell tetap polling di antara ping
  // ===================================================
  bacaUltrasonic();

  // ===================================================
  // 4. UPDATE TRAFFIC LIGHT LED — setiap 500ms
  // ===================================================
    setTrafficLight(estimasi_jumlah);

  // ===================================================
  // 5. UPDATE LCD — setiap 500ms
  // ===================================================
  static unsigned long lastLCD = 0;
  if (now - lastLCD >= 500) {
    lcd.setCursor(0, 0);
    lcd.print("Berat:");
    String beratStr = String((int)berat_gram) + "g   ";
    lcd.print(beratStr.substring(0, 9));

    lcd.setCursor(0, 1);
    String bawah = "Jml:" + String(estimasi_jumlah) + " " + status_rak + "   ";
    lcd.print(bawah.substring(0, 16));
    lastLCD = now;
  }

// ===================================================
  // 5b. DEBUG SERIAL — setiap 500ms (sinkron dengan LCD)
  // ===================================================
  static unsigned long lastSerial = 0;
  if (now - lastSerial >= 1000) {
    Serial.println(
      "[DATA] Berat:" + String(berat_gram, 1) + "g" +
      " | Jml:" + String(estimasi_jumlah) +
      " | Jarak:" + String(distance_cm, 1) + "cm" +
      " | Status:" + status_rak +
      " | RFID:" + rfid_uid
    );
    lastSerial = now;
  }

  // ===================================================
  // 6. KIRIM DATA KE SERVER — hanya jika ada perubahan
  //    dengan debounce minimal 2000ms antar pengiriman
  // ===================================================
  static unsigned long lastKirim = 0;
  boolean data_berubah = false;

  if (abs(berat_gram - last_berat) > 10.0)     data_berubah = true;
  if (estimasi_jumlah != last_jumlah)           data_berubah = true;
  if (abs(distance_cm - last_jarak) > 2.0)      data_berubah = true;
  if (status_rak != last_status)                 data_berubah = true;

  if (data_berubah && (now - lastKirim >= 2000)) {
    kirimKeServer(berat_gram,
                  estimasi_jumlah,
                  distance_cm,
                  status_rak,
                  rfid_uid);

    last_berat  = berat_gram;
    last_jumlah = estimasi_jumlah;
    last_jarak  = distance_cm;
    last_status = status_rak;
    lastKirim   = now;

    Serial.println("[KIRIM] Berat:" + String(berat_gram,1) 
    + "g | Jml:" + String(estimasi_jumlah) + " | Jarak:" + 
    String(distance_cm,1) + "cm | Status:" + status_rak + " | RFID:" + rfid_uid);

    if (estimasi_jumlah <= threshold_low) {
      Serial.println("LED    : RED (Low Stock)");
    } else if (estimasi_jumlah <= threshold_medium) {
      Serial.println("LED    : YELLOW (Medium Stock)");
    } else {
      Serial.println("LED    : GREEN (Full Stock)");
    }
  }

  // ===================================================
  // 7. CEK KONEKSI WiFi — setiap 5 detik
  //    Non-blocking: tidak ada while/delay di dalam
  // ===================================================
  static unsigned long lastWiFiCheck = 0;

  // Saat sedang reconnecting, panggil cekWiFi() setiap loop
  // agar progress reconnect berjalan tanpa blocking
  if (wifiState == WIFI_RECONNECTING) {
    cekWiFi();
  } else if (now - lastWiFiCheck >= 10000) {
    cekWiFi();
    lastWiFiCheck = now;
  }
}
