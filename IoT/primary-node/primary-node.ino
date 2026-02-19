#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <U8g2lib.h>
#include <TinyGPSPlus.h>

// WIFI
const char* ssid = "YOUR_WIFI";
const char* password = "YOUR_PASS";

// SERVER
const char* triggerURL = "https://farm.tonystark.in/trigger_call.php";

// OLED
U8G2_SSD1306_128X64_NONAME_F_HW_I2C u8g2(U8G2_R0);

// SIM900A UART2
HardwareSerial sim900(2);

// GPS UART1
HardwareSerial gpsSerial(1);
TinyGPSPlus gps;

// VARIABLES
int fakeSignal = 75;
unsigned long lastUpdate = 0;
const unsigned long interval = 3000;

void triggerCall() {
  sim900.println("AT");
  delay(1000);
  sim900.println("ATD+91XXXXXXXXXX;");  // replace number
  delay(20000);
  sim900.println("ATH");
}

void resetTrigger(WiFiClientSecure &client) {
  HTTPClient https;
  https.begin(client, "https://farm.tonystark.in/trigger_call.php?set=0");
  https.GET();
  https.end();
}

void setup() {
  Serial.begin(115200);

  sim900.begin(9600, SERIAL_8N1, 16, 17);   // RX, TX
  gpsSerial.begin(9600, SERIAL_8N1, 18, 19);

  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
  }

  u8g2.begin();
}

void loop() {

  // GPS read (dummy not required but initialized)
  while (gpsSerial.available()) {
    gps.encode(gpsSerial.read());
  }

  if (millis() - lastUpdate > interval) {
    lastUpdate = millis();

    fakeSignal = random(60, 99);

    if (WiFi.status() == WL_CONNECTED) {
      WiFiClientSecure client;
      client.setInsecure();

      HTTPClient https;
      https.begin(client, triggerURL);
      int httpCode = https.GET();
      String payload = https.getString();
      https.end();

      if (payload == "1") {
        triggerCall();
        delay(2000);
        resetTrigger(client);
      }
    }

    // OLED DISPLAY
    u8g2.clearBuffer();
    u8g2.setFont(u8g2_font_ncenB08_tr);

    u8g2.setCursor(0, 15);
    u8g2.print("Nodes: 1");

    u8g2.setCursor(0, 30);
    u8g2.print("Signal: ");
    u8g2.print(fakeSignal);
    u8g2.print("%");

    u8g2.setCursor(0, 45);
    u8g2.print("WiFi: ");
    if (WiFi.status() == WL_CONNECTED)
      u8g2.print("OK");
    else
      u8g2.print("NO");

    u8g2.setCursor(0, 60);
    u8g2.print("Server: Active");

    u8g2.sendBuffer();
  }
}