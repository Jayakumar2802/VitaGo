# VitaGo – Autonomos Smart Car System for Patient Monitoring

## 📌 Project Overview

VitaGo is an Autonomos smart car system designed to support patient monitoring, especially for elderly people who may require assistance. It combines autonomous rover navigation, camera-based fall detection, ECG signal monitoring, obstacle detection, and a web-based dashboard.

The system integrates embedded hardware, artificial intelligence, and web technologies to support remote patient monitoring.

## 🎯 Objectives

- Develop a smart rover with autonomous navigation capabilities.
- Detect potential falls using a ngrok application.
- Monitor heart electrical activity using the AD8232 ECG module.
- Detect obstacles using ultrasonic sensors.
- Display monitoring data through a web dashboard.
- Enable communication between the ESP32 devices and the backend over Wi-Fi.

## ✨ Key Features

- **Autonomous Navigation:** Supports movement between designated points.
- **Fall Detection:** Uses a ngrok application to detect.
- **ECG Monitoring:** Acquires ECG signals using the AD8232 module.
- **Obstacle Detection:** Uses ultrasonic sensors to identify nearby obstacles.
- **Live ECG Visualization:** Displays ECG waveform data on the dashboard.
- **Web-Based Monitoring:** Uses a PHP backend and MySQL database.
- **Wireless Communication:** Uses ESP32 Wi-Fi connectivity to communicate with the server.

## 🛠️ Technologies Used

### Hardware

- ESP32 development boards
- ESP32-CAM
- AD8232 ECG module
- ECG electrodes and lead wires
- Ultrasonic sensors
- L298N motor driver
- DC motors
- MPU6050 accelerometer and gyroscope
- NRF wireless module, if used in the final circuit
- TP4056 charging module
- 5V boost converter
- Battery and connecting wires

### Software

- C/C++ for ESP32 firmware
- PHP for backend APIs
- HTML, CSS and JavaScript
- React and Bootstrap, where used
- MySQL database
- Chart.js for ECG waveform visualization
- Arduino IDE
- Visual Studio Code
- XAMPP
- Git and GitHub

## 🏗️ System Architecture

VitaGo consists of three main subsystems.

1. **Rover Control Unit:** An ESP32 controls the motors through the L298N driver and uses ultrasonic sensors for obstacle detection and navigation.
2. **Patient Monitoring Unit:** A separate ESP32 reads the analog ECG signal from the AD8232 module and sends readings to the backend. The ESP32-CAM supports camera-based fall detection.
3. **Web Dashboard:** A web interface communicates with PHP APIs and displays available monitoring information, including ECG waveforms and rover status.

The precise communication between the boards depends on the firmware and communication modules used in the final implementation.

## 🔌 Hardware Connections

### 1. ESP32 Rover Control Board

This ESP32 controls rover movement and obstacle detection.

#### Ultrasonic Sensors

The following is an example connection table for two HC-SR04 ultrasonic sensors. Replace the GPIO numbers with those defined in your firmware.

| HC-SR04 pin | ESP32 connection |
|---|---|
| VCC | 5V |
| GND | GND |
| TRIG (left sensor) | Configured left-trigger GPIO |
| ECHO (left sensor) | Configured left-echo GPIO through a voltage divider |
| TRIG (right sensor) | Configured right-trigger GPIO |
| ECHO (right sensor) | Configured right-echo GPIO through a voltage divider |

**Important:** The HC-SR04 ECHO pin typically outputs 5V. Use a voltage divider or suitable level shifter before connecting it to an ESP32 GPIO.

#### L298N Motor Driver

| L298N pin | Connection |
|---|---|
| IN1 | ESP32 motor-control GPIO |
| IN2 | ESP32 motor-control GPIO |
| IN3 | ESP32 motor-control GPIO |
| IN4 | ESP32 motor-control GPIO |
| ENA | PWM-capable GPIO, if speed control is used |
| ENB | PWM-capable GPIO, if speed control is used |
| OUT1 and OUT2 | Motor A |
| OUT3 and OUT4 | Motor B |
| Motor supply | Suitable external motor battery |
| GND | Common ground with ESP32 |

Use the GPIO assignments from the actual rover firmware. Do not connect motors directly to ESP32 GPIO pins.

### 2. Separate ESP32 ECG Monitoring Board

The AD8232 ECG module measures electrical activity from the heart and provides an analog output.

#### AD8232 to ESP32

| AD8232 pin | ESP32 connection |
|---|---|
| 3.3V | ESP32 3V3 |
| GND | ESP32 GND |
| OUTPUT | GPIO 34 (example analog input) |
| LO+ | GPIO 32 (optional lead-off detection) |
| LO− | GPIO 33 (optional lead-off detection) |
| SDN | According to module configuration; check the module documentation | or | ESP32 3V3|

The GPIO numbers are examples, not verified assignments from the current firmware.

#### ECG Electrode Connections

- Connect the electrode lead wires to the AD8232 module's designated electrode connector.
- Place the electrodes according to the module's documented RA, LA and RL placement instructions.
- Ensure that the electrodes and wires are correctly attached before acquiring data.

#### ECG Signal Processing

1. The electrodes detect electrical activity from the heart.
2. The AD8232 conditions the ECG signal and provides an analog output.
3. The ESP32 samples the signal using its ADC.
4. The ESP32 sends ECG readings to the backend.
5. The dashboard plots the readings using Chart.js.
6. Heart rate in BPM can be estimated from detected ECG R-peaks when suitable signal processing is implemented.

**Note:** ECG signal values alone are not equivalent to BPM. Accurate heart-rate estimation requires suitable peak detection and signal processing.

### 3. ESP32-CAM for Fall Detection

The ESP32-CAM supports camera-based monitoring.

| Component | Connection |
|---|---|
| Camera module | Connect using the board's built-in camera interface |
| Power | Stable supply matching board requirements |
| GND | Common ground where required |
| Wi-Fi | Built-in ESP32 wireless connectivity |

For an AI-Thinker ESP32-CAM, use the official camera pin mapping and avoid GPIOs reserved for the camera, flash, or boot functions. The exact wiring depends on the camera board variant and firmware.

### 4. MPU6050 Sensor (If Used in the Rover)

| MPU6050 pin | ESP32 connection |
|---|---|
| VCC | Supply voltage supported by the module |
| GND | GND |
| SDA | Configured I²C SDA GPIO |
| SCL | Configured I²C SCL GPIO |

The MPU6050 measures acceleration and angular velocity. Its data can support motion analysis and rover orientation tracking.

### 5. Power Supply

- Use a battery suitable for the motors and their current requirements.
- Use a regulated supply for the ESP32 and sensor modules.
- Use the TP4056 only with a compatible single-cell lithium battery and the correct charging configuration.
- Use a suitable boost converter only within its rated input and output limits.
- Ensure the supply voltage matches each module's specifications.
- Keep motor power wiring away from sensitive ECG signal wiring where possible to reduce electrical noise.

**Electrical safety:** Do not connect a person wearing ECG electrodes to circuits connected to mains-powered equipment unless appropriate medical electrical isolation is provided. This project is an educational prototype, not a certified medical device.

## 💻 Software Installation and Setup

### Prerequisites

Install the following software:

- Arduino IDE
- Visual Studio Code
- XAMPP
- MySQL or the MySQL service bundled with XAMPP
- Git

### Step 1: Clone the Repository

```bash
git clone https://github.com/Jayakumar2802/VitaGo.git
cd VitaGo
```

### Step 2: Set Up the PHP Backend

1. Copy the project folder into `C:\xampp\htdocs\` if it is not already there.
2. Start Apache and MySQL using the XAMPP Control Panel.
3. Open phpMyAdmin.
4. Import the project's SQL file if one is included.
5. Update the database connection settings in the PHP configuration.
6. Ensure that the frontend and ESP32 firmware use the correct backend URL.

Example local URL, depending on your folder structure:

```text
http://localhost/rover/
```

### Step 3: Configure the ESP32 Boards

1. Open the appropriate firmware in Arduino IDE.
2. Install the required board support and libraries.
3. Configure the Wi-Fi credentials.
4. Configure the PHP API endpoint.
5. Confirm the GPIO assignments against the wiring.
6. Select the correct board and COM port.
7. Upload the firmware to the appropriate ESP32 board.

### Step 4: Run the Dashboard

1. Start Apache and MySQL in XAMPP.
2. Open the dashboard using the correct local URL.
3. Check that the backend API responds correctly.
4. Verify ECG readings, rover status, and monitoring data.

For remote access, configure networking securely and avoid exposing the development server or sensitive patient data publicly.

## 📁 Project Structure

The following is an example structure. Adjust it to match the actual files in the repository.

```text
VitaGo/
├── rover/
│   ├── index.php
│   ├── patient.php
│   ├── api.php
│   ├── api_monitor.php
│   ├── css/
│   ├── js/
│   └── images/
├── README.md
└── .gitignore
```

## 🔒 Security Considerations

- Never commit Wi-Fi passwords, database passwords, API keys or access tokens.
- Do not publish identifiable patient information or private ECG recordings.
- Restrict access to monitoring endpoints.
- Use authentication and secure transport for remote access.
- Keep configuration secrets outside version-controlled files.

## 🔮 Future Enhancements

- Add emergency alerts for suspected falls.
- Improve autonomous navigation and obstacle avoidance.
- Add ECG peak detection and heart-rate trend analysis.
- Store monitoring history securely.
- Add patient reports and authenticated user access.
- Evaluate detection performance under additional real-world conditions.

## ⚠️ Disclaimer

VitaGo is an educational and research prototype. Its fall-detection and ECG outputs are not a substitute for professional medical equipment, clinical assessment, or emergency services. The system has not been established as a certified medical device.

## 👨‍💻 Author

**Jayakumar Balikai**

GitHub: [@Jayakumar2802](https://github.com/Jayakumar2802)

Project Repository: [VitaGo](https://github.com/Jayakumar2802/VitaGo)

## 📄 License

No license has been specified. Contact the author for permission before redistributing or reusing this project beyond the permissions granted by the repository.
