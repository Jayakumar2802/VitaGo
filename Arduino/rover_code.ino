#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <math.h>

// ---------- WIFI SETTINGS ----------
const char* ssid = "WiFi_NAME";
const char* password = "WiFi_PASSAWORD";
String serverURL = "http://"IP_ADDRESS"/rover/api.php?action=";

// ---------- MOTOR PINS (ESP32 DevKit Safe) ----------
const int L1 = 25;
const int L2 = 26;
const int R1 = 27;
const int R2 = 14;

// ---------- ULTRASONIC PINS ----------
const int trigL = 5;
const int echoL = 18;
const int trigR = 19;
const int echoR = 21;

// ---------- CONSTANTS ----------
const float stepDist = 5.0;
const float wheelbase = 57.5;  // Tuned for ~5° turn per step

// ---------- PATH ARRAYS ----------
float pathX[200];
float pathY[200];
int pathSize = 0;

// ---------- MOVEMENT HELPERS ----------
// Forward: both forward
void moveForward(){ 
  digitalWrite(L1,HIGH); digitalWrite(L2,LOW); 
  digitalWrite(R1,LOW); digitalWrite(R2,HIGH);  
}
// Backward: both backward
void moveBackward(){ 
  digitalWrite(L1,LOW); digitalWrite(L2,HIGH); 
  digitalWrite(R1,HIGH); digitalWrite(R2,LOW);  
}
// Left arc turn: left forward, right stop
void turnLeft(){ 
  digitalWrite(L1,HIGH); digitalWrite(L2,LOW); 
  digitalWrite(R1,LOW); digitalWrite(R2,LOW);  
}
// Right arc turn: left stop, right forward
void turnRight(){ 
  digitalWrite(L1,LOW); digitalWrite(L2,LOW); 
  digitalWrite(R1,LOW); digitalWrite(R2,HIGH);  
}

void stopMotors(){ 
  digitalWrite(L1,LOW); digitalWrite(L2,LOW); 
  digitalWrite(R1,LOW); digitalWrite(R2,LOW); 
}

// ---------- POSITION VARIABLES ----------
float roverX = 50;
float roverY = 50;
float roverAngle = 90;  // Initial facing north (up, -Y)
bool autoMode = false;
String currentCommand = "stop";
float serverTurnA = 0;
float serverTurnT = 0;
float serverMoveT = 0;
String lastExecutedReturn = "";

// ---------- WAYPOINT STRUCT ----------
struct WP { float x; float y; int pathIdx; };
WP waypoints[50];
int totalWP = 0;
int currentWPIndex = 0;

// ---------- ULTRASONIC ----------
long readDistance(int trig, int echo){
  digitalWrite(trig, LOW); delayMicroseconds(2);
  digitalWrite(trig, HIGH); delayMicroseconds(10);
  digitalWrite(trig, LOW);
  long t = pulseIn(echo, HIGH, 20000);
  return (t * 0.034) / 2;  // cm
}


bool obstacleDetected() {
  long left = readDistance(trigL, echoL);
  long right = readDistance(trigR, echoR);

  Serial.print("Left sensor: ");
  Serial.print(left);
  Serial.print(" cm | Right sensor: ");
  Serial.print(right);
  Serial.println(" cm");

  bool obs = (left > 0 && left < 15) ||
             (right > 0 && right < 15);

  if (obs) {
    Serial.println("Obstacle Detected!");
  }

  return obs;
}


// ---------- SEND POSITION ----------
void sendRoverPosition(bool obs){
  HTTPClient http;
  String url = serverURL 
        + "update_rover&x=" + String(roverX)
        + "&y=" + String(roverY)
        + "&angle=" + String(roverAngle)
        + "&obstacle=" + (obs ? "1" : "0");
  if(http.begin(url)){
    int code = http.GET();
    if(code==200){
      Serial.println("Rover position sent successfully.");
      Serial.print("Position -> X: "); Serial.print(roverX);
      Serial.print(" | Y: "); Serial.print(roverY);
      Serial.print(" | Angle: "); Serial.println(roverAngle);
    } else {
      Serial.println("Error sending rover position: " + String(code));
    }
    http.end();
  } else {
    Serial.println("Cannot connect to server!");
  }
}

// ---------- UPDATE POSITION FROM WHEEL DELTAS ----------
void updatePosition(float deltaL, float deltaR){
  float d = (deltaL + deltaR) / 2.0;
  float delta_theta = (deltaL - deltaR) / wheelbase;  // Positive for left (ccw)
  float avg_rad = roverAngle * PI / 180.0 + delta_theta / 2.0;
  roverX += d * cos(avg_rad);
  roverY -= d * sin(avg_rad);  // Negative for screen Y (up decreases Y)
  roverAngle += delta_theta * 180.0 / PI;
  if(roverAngle < 0) roverAngle += 360;
  if(roverAngle >= 360) roverAngle -= 360;
}

// ---------- FETCH SERVER ----------
void fetchServer(){
  HTTPClient http;
  static bool prevAutoMode = false;
  static bool prevReset = false;
  if(http.begin(serverURL + "get")){
    int code = http.GET();
    if(code==200){
      String payload = http.getString();
      DynamicJsonDocument doc(8192);  // Increased for path
      DeserializationError err = deserializeJson(doc,payload);
      if(err){
        Serial.println("JSON parse error: " + String(err.c_str()));
        http.end();
        return;
      }
      currentCommand = doc["command"] | "stop";
      serverTurnA = doc["turnA"] | 0.0f;
      serverTurnT = doc["turnT"] | 0.0f;
      serverMoveT = doc["moveT"] | 0.0f;
      Serial.println("Command received: " + currentCommand);

      // Check for reset flag
      bool resetFlag = doc["reset"] | false;
      if(resetFlag && !prevReset){
        roverX = 50;
        roverY = 50;
        roverAngle = 90;
        autoMode = false;
        currentCommand = "stop";
        currentWPIndex = 0;
        totalWP = 0;
        pathSize = 0;
        Serial.println("Reset position to center!");
        sendRoverPosition(false);
      }
      prevReset = resetFlag;

      // Parse waypoints
      totalWP = 0;
      if(doc["waypoints"].is<JsonArray>()){
        JsonArray arr = doc["waypoints"];
        totalWP = min(50, (int)arr.size());
        for(int i=0;i<totalWP;i++){
          waypoints[i].x = arr[i]["x"] | 50.0f;
          waypoints[i].y = arr[i]["y"] | 50.0f;
          waypoints[i].pathIdx = arr[i]["pathIndex"] | -1;
        }
      }

      // Parse path
      pathSize = 0;
      if(doc["path"].is<JsonArray>()){
        JsonArray parr = doc["path"];
        int ps = (int)parr.size();
        pathSize = (ps > 200 ? 200 : ps);
        for(int i=0; i<pathSize; i++){
          pathX[i] = parr[i]["x"] | 50.0f;
          pathY[i] = parr[i]["y"] | 50.0f;
        }
      }

      bool serverAutoMode = doc["goto"] | false;
      // Only sync currentWP when auto mode is first enabled.
      // Prevents a stale server value resetting the locally advancing waypoint.
      if(serverAutoMode && !prevAutoMode) {
        currentWPIndex = doc["currentWP"] | 0;
        Serial.println("Autonomous mode started. Waypoints: " + String(totalWP));
      }
      autoMode = serverAutoMode;
      prevAutoMode = serverAutoMode;
    } else {
      Serial.println("Server GET failed: " + String(code));
    }
    http.end();
  } else {
    Serial.println("Cannot connect to server!");
  }
}

// ---------- MANUAL MOVE ----------
void manualMove(){
  if(obstacleDetected()){ 
    stopMotors(); 
    sendRoverPosition(true); 
    delay(150); 
    return; 
  }

  float deltaL = 0;
  float deltaR = 0;
  if(currentCommand=="forward"){ 
    deltaL = stepDist; deltaR = stepDist; 
    moveForward(); 
  }
  else if(currentCommand=="back"){ 
    deltaL = -stepDist; deltaR = -stepDist; 
    moveBackward(); 
  }
  else if(currentCommand=="left"){ 
    deltaL = stepDist; deltaR = 0; 
    turnLeft(); 
  }
  else if(currentCommand=="right"){ 
    deltaL = 0; deltaR = stepDist; 
    turnRight(); 
  }
  else{ 
    stopMotors(); 
    sendRoverPosition(false);
    delay(150);
    return;
  }

  updatePosition(deltaL, deltaR);

  Serial.print("Manual Move: "); Serial.println(currentCommand);
  sendRoverPosition(obstacleDetected());
  delay(150);
}

// ---------- AUTO MOVE ----------
void autoMove(){
  if(currentWPIndex>=totalWP){ 
    stopMotors(); 
    autoMode=false; 
    currentCommand = "stop";  // Reset command
    Serial.println("Reached all waypoints."); 
    sendRoverPosition(false);
    delay(200); 
    return; 
  }
  WP t = waypoints[currentWPIndex];
  float dx = t.x - roverX;
  float dy = t.y - roverY;
  float dist = sqrt(dx*dx + dy*dy);
  if(dist<4){ 
    currentWPIndex++; 
    stopMotors(); 
    sendRoverPosition(false); 
    Serial.println("Waypoint " + String(currentWPIndex-1) + " reached."); 
    delay(200); 
    return; 
  }
  // Basic reactive obstacle avoidance: steer toward the side with more clearance.
  long leftDistance = readDistance(trigL, echoL);
  long rightDistance = readDistance(trigR, echoR);
  bool leftBlocked = (leftDistance > 0 && leftDistance < 20);
  bool rightBlocked = (rightDistance > 0 && rightDistance < 20);

  if(leftBlocked || rightBlocked) {
    Serial.println("Obstacle detected: attempting a short avoidance maneuver.");
    if(leftBlocked && rightBlocked) {
      moveBackward();
      updatePosition(-stepDist, -stepDist);
      delay(250);
    } else if(leftBlocked) {
      turnRight();
      updatePosition(0, stepDist);
      delay(250);
    } else {
      turnLeft();
      updatePosition(stepDist, 0);
      delay(250);
    }
    stopMotors();
    sendRoverPosition(true);
    delay(100);
    return;
  }

  int target_idx = t.pathIdx;
  if(target_idx < 0 || pathSize == 0){
    // Direct navigation (fallback for free waypoints)
    float targetAngle = atan2(roverY - t.y, t.x - roverX) * 180.0 / PI;
    if (targetAngle < 0) targetAngle += 360;
    float angleDiff = targetAngle - roverAngle;
    while(angleDiff > 180) angleDiff -= 360;
    while(angleDiff < -180) angleDiff += 360;

    float deltaL = 0;
    float deltaR = 0;
    if(fabs(angleDiff) > 8.0){
      if(angleDiff > 0){
        deltaL = stepDist; deltaR = 0;
        turnLeft();
      } else {
        deltaL = 0; deltaR = stepDist;
        turnRight();
      }
    } else {
      deltaL = stepDist; deltaR = stepDist;
      moveForward();
    }
    updatePosition(deltaL, deltaR);
  } else {
    // Path following
    // Find current path index (closest point)
    int curr_idx = -1;
    float min_dist = 99999.0f;
    for(int i = 0; i < pathSize; i++){
      float pdx = pathX[i] - roverX;
      float pdy = pathY[i] - roverY;
      float pdist = sqrt(pdx * pdx + pdy * pdy);
      if(pdist < min_dist){
        min_dist = pdist;
        curr_idx = i;
      }
    }
    if(target_idx < 0 || target_idx >= pathSize) {
      Serial.println("Invalid waypoint pathIndex; using direct navigation.");
      float targetAngle = atan2(roverY - t.y, t.x - roverX) * 180.0 / PI;
      if(targetAngle < 0) targetAngle += 360;
      float angleDiff = targetAngle - roverAngle;
      while(angleDiff > 180) angleDiff -= 360;
      while(angleDiff < -180) angleDiff += 360;
      if(fabs(angleDiff) > 8.0) {
        if(angleDiff > 0) { turnLeft(); updatePosition(stepDist, 0); }
        else { turnRight(); updatePosition(0, stepDist); }
      } else {
        moveForward(); updatePosition(stepDist, stepDist);
      }
      sendRoverPosition(false);
      delay(150);
      return;
    }

    if(curr_idx == -1) {
      stopMotors();
      delay(150);
      return;
    }

    // Next point on path, clamped to this waypoint's path index.
    int next_idx = curr_idx + 1;
    if(next_idx > target_idx) next_idx = target_idx;
    float nx = pathX[next_idx];
    float ny = pathY[next_idx];

    // Compute angle to next
    float dx_next = nx - roverX;
    float dy_next = roverY - ny;  // Match atan2 convention
    float targetAngle = atan2(dy_next, dx_next) * 180.0 / PI;
    if (targetAngle < 0) targetAngle += 360;
    float angleDiff = targetAngle - roverAngle;
    while(angleDiff > 180) angleDiff -= 360;
    while(angleDiff < -180) angleDiff += 360;

    float deltaL = 0;
    float deltaR = 0;
    if(fabs(angleDiff) > 8.0){
      if(angleDiff > 0){
        deltaL = stepDist; deltaR = 0;
        turnLeft();
      } else {
        deltaL = 0; deltaR = stepDist;
        turnRight();
      }
    } else {
      deltaL = stepDist; deltaR = stepDist;
      moveForward();
    }
    updatePosition(deltaL, deltaR);
  }
  sendRoverPosition(false);
  delay(150);
}

// ---------- DASHBOARD RETURN COMMAND ----------
void setServerCommandStop() {
  HTTPClient http;
  String url = "http://"IP_ADDRESS"/rover/api.php?command=stop";
  if (http.begin(url)) {
    http.GET();
    http.end();
  }
  currentCommand = "stop";
}

// Run a timed dashboard turn/move command once. The dashboard expects the
// server command to return to "stop" when the movement has finished.
void executeReturnCommand() {
  Serial.println("Executing dashboard return command");

  // Turn direction is based on signed turnA; turnT is milliseconds.
  unsigned long turnMs = (unsigned long)constrain((long)serverTurnT, 0L, 10000L);
  unsigned long moveMs = (unsigned long)constrain((long)serverMoveT, 0L, 15000L);

  if (turnMs > 0 && fabs(serverTurnA) > 1.0f) {
    if (serverTurnA > 0) turnLeft();
    else turnRight();

    unsigned long start = millis();
    while (millis() - start < turnMs) {
      // Stop turning if a close obstacle is detected.
      long l = readDistance(trigL, echoL);
      long r = readDistance(trigR, echoR);
      if ((l > 0 && l < 15) || (r > 0 && r < 15)) {
        stopMotors();
        Serial.println("Return command stopped: obstacle during turn");
        setServerCommandStop();
        sendRoverPosition(true);
        return;
      }
      delay(20);
    }
    stopMotors();
    // Approximate heading update; actual angle depends on motor speed and battery.
    roverAngle += (serverTurnA > 0 ? 1.0f : -1.0f) * turnMs * 0.07f;
    while (roverAngle < 0) roverAngle += 360;
    while (roverAngle >= 360) roverAngle -= 360;
  }

  // Move forward in short intervals so the ultrasonic sensors can stop it.
  unsigned long moved = 0;
  while (moved < moveMs) {
    long l = readDistance(trigL, echoL);
    long r = readDistance(trigR, echoR);
    if ((l > 0 && l < 15) || (r > 0 && r < 15)) {
      stopMotors();
      Serial.println("Return command stopped: obstacle during forward movement");
      setServerCommandStop();
      sendRoverPosition(true);
      return;
    }
    moveForward();
    delay(40);
    moved += 40;
  }

  stopMotors();
  // Position is an estimate only; without encoders/GPS it is not measured pose.
  float estimatedDistance = moveMs * 0.022f;
  float rad = roverAngle * PI / 180.0f;
  roverX += estimatedDistance * cos(rad);
  roverY -= estimatedDistance * sin(rad);
  sendRoverPosition(false);
  setServerCommandStop();
  currentCommand = "stop";
  Serial.println("Return command completed.");
}

// ---------- SETUP ----------
void setup(){
  Serial.begin(115200);
  pinMode(L1,OUTPUT); pinMode(L2,OUTPUT);
  pinMode(R1,OUTPUT); pinMode(R2,OUTPUT);
  pinMode(trigL,OUTPUT); pinMode(echoL,INPUT);
  pinMode(trigR,OUTPUT); pinMode(echoR,INPUT);

  Serial.println("Connecting to WiFi...");
  WiFi.begin(ssid,password);
  while(WiFi.status()!=WL_CONNECTED){ delay(500); Serial.print("."); }
  Serial.println("\nWiFi Connected! IP: " + WiFi.localIP().toString());
}

// ---------- LOOP ----------
void loop(){
  fetchServer();

  if (currentCommand == "return") {
    executeReturnCommand();
    delay(50);
    return;
  }

  if(autoMode) autoMove();
  else manualMove();
}
