#include <WiFi.h>
#include <HTTPClient.h>
#include "esp_task_wdt.h"

const char* ssid = "WIFI_NAME";
const char* password = "WIFI_PASSAWORD";
const char* serverUrl = "http://"IP_ADDRESS"/rover/api_monitor.php";

const int sensorPin = 34;   
const int loPlus = 14;     
const int loMinus = 12;    


void setup() {
  Serial.begin(115200);
  pinMode(sensorPin, INPUT); 
  pinMode(loPlus, INPUT);
  pinMode(loMinus, INPUT);
  analogSetAttenuation(ADC_11db); 

  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) { delay(500); Serial.print("."); }
  
  esp_task_wdt_config_t twdt_config = {.timeout_ms = 8000, .idle_core_mask = (1 << portNUM_PROCESSORS) - 1, .trigger_panic = true};
  esp_task_wdt_init(&twdt_config);
  esp_task_wdt_add(NULL); 
}

void loop() {
  esp_task_wdt_reset();

  int lp = digitalRead(loPlus);
  int lm = digitalRead(loMinus);
  
  // leadsOff is 1 if disconnected, 0 if connected
  int leadsOff = (lp == 1 || lm == 1) ? 1 : 0;
  int ecgValue = analogRead(sensorPin);

  // NEW LOGIC: 
  // Connected (leadsOff == 0) -> Spike to 200
  // Disconnected (leadsOff == 1) -> Drop to 0
  int leadsVisual = (leadsOff == 0) ? 200 : 0;

  // Serial Plotter output
  // Line 1: The ECG Wave
  // Line 2: The Connection Status (200 = OK, 0 = OFF)
  Serial.print("ECG:"); Serial.print(ecgValue);
  Serial.print(",");
  Serial.print("ConnectionStatus:"); Serial.println(leadsVisual);

  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(serverUrl);
    http.addHeader("Content-Type", "application/json");

    // We still send the standard 1/0 to the API for the database
    String payload = "{\"ecg\":" + String(ecgValue) + ", \"leads_off\":" + String(leadsOff) + "}";
    http.POST(payload);
    http.end();
  }
  
  delay(100); 
}
