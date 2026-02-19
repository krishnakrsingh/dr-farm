#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <DHT.h>
#include <Adafruit_GFX.h>
#include <Adafruit_ILI9341.h>

#define DHTPIN 4
#define DHTTYPE DHT11
DHT dht(DHTPIN, DHTTYPE);

// ILI9341 Pins
#define TFT_CS   15
#define TFT_DC   2
#define TFT_MOSI 23
#define TFT_SCK  18
#define TFT_MISO 19

Adafruit_ILI9341 tft = Adafruit_ILI9341(TFT_CS, TFT_DC);

const char* ssid = "raj";
const char* password = "12345678";

void setup() {
  Serial.begin(115200);

  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
  }

  dht.begin();
  tft.begin();
  tft.fillScreen(ILI9341_BLACK);
}

void loop() {

  float temp = dht.readTemperature();
  float hum  = dht.readHumidity();
  int mq7  = analogRead(35);
  int mq3  = analogRead(32);
  int rain = analogRead(34);

  // Display
  tft.fillScreen(ILI9341_BLACK);
  tft.setCursor(10,10);
  tft.setTextColor(ILI9341_WHITE);
  tft.setTextSize(2);

  tft.print("Temp: "); tft.println(temp);
  tft.print("Hum: ");  tft.println(hum);
  tft.print("MQ7: ");  tft.println(mq7);
  tft.print("MQ3: ");  tft.println(mq3);
  tft.print("Rain: "); tft.println(rain);

  if(WiFi.status()==WL_CONNECTED)
    tft.println("Connected");
  else
    tft.println("No WiFi");

  // Send to server
  if(WiFi.status()==WL_CONNECTED){
    WiFiClientSecure client;
    client.setInsecure();
    HTTPClient https;

    https.begin(client, "https://farm.tonystark.in/sensor_upload.php");
    https.addHeader("Content-Type","application/x-www-form-urlencoded");

    String data = "node_id=NODE_1&temperature="+String(temp)+
                  "&humidity="+String(hum)+
                  "&mq7="+String(mq7)+
                  "&mq3="+String(mq3)+
                  "&rain="+String(rain);

    https.POST(data);
    https.end();
  }

  delay(5000);
}