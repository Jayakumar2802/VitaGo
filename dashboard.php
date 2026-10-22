<!DOCTYPE html>
<html>
<head>
    <title>Super Dashboard - SLAM & TeleHealth</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { 
            --neon: #00d4ff; 
            --purp: #bb86fc; 
            --red: #ff2d55; 
            --bg: #0f172a; 
            --card: #1e293b;
            --accent: #38bdf8; 
            --danger: #ef4444; 
            --success: #22c55e;
            --warning: #f59e0b;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: linear-gradient(135deg, #0a0b10 0%, #1a1f3a 100%);
            color: white; 
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .main-wrapper {
            display: flex;
            flex-direction: column;
            flex: 1;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }
        .upper-section {
            flex: 1;
            overflow-y: auto;
            padding: 20px 0;
        }
        .dashboard-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: 1fr auto;
            gap: 20px;
            height: 100%;
            position: relative;
        }
        .panel {
            background: rgba(30, 41, 59, 0.9);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .ecg-panel {
            grid-column: 1 / -1;
            height: 250px;
        }
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .panel-title {
            font-size: 22px;
            font-weight: 600;
            color: var(--neon);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .status-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #666;
        }
        .status-dot.active { background: var(--success); }
        .status-dot.warning { background: var(--warning); }
        .status-dot.danger { background: var(--danger); }
        
        /* SLAM Panel */
        .slam-layout {
            flex: 1;
            position: relative;
            min-height: 300px;
        }
        .slam-map-container {
            flex: 1;
            position: relative;
            height: 100%;
        }
        .slam-map-container canvas {
            width: 100%;
            height: 100%;
            display: block;
        }
        .control-overlay {
            position: absolute;
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.6);
            padding: 20px;
            border-radius: 15px;
            z-index: 20;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .joystick-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            width: 180px;
            height: 180px;
        }
        .joystick-row {
            display: flex;
            justify-content: center;
            gap: 10px;
            width: 100%;
        }
        .control-btn {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            border: 1px solid var(--neon);
            color: white;
            padding: 14px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0, 212, 255, 0.1);
            width: 60px;
            height: 60px;
        }
        .control-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 212, 255, 0.2);
        }
        .control-btn:active {
            background: var(--neon);
            color: #000;
            transform: translateY(0);
        }
        .control-btn.stop {
            background: linear-gradient(135deg, #7f1d1d 0%, #991b1b 100%);
            border-color: var(--red);
        }
        .control-btn.stop:hover {
            box-shadow: 0 6px 20px rgba(255, 45, 85, 0.2);
        }
        .control-btn.stop:active {
            background: var(--red);
            color: white;
        }
        .grid-empty {
            width: 60px;
            height: 60px;
        }
        .lower-section {
            flex-shrink: 0;
            padding: 0 20px 20px;
            background: transparent;
        }
        .slam-controls {
            background: rgba(30, 41, 59, 0.9);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            backdrop-filter: blur(10px);
        }
        .action-buttons-slam {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .action-btn {
            background: linear-gradient(135deg, #581c87 0%, #7c3aed 100%);
            border: none;
            color: white;
            padding: 14px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(187, 134, 252, 0.2);
            white-space: nowrap;
        }
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(187, 134, 252, 0.3);
        }
        .action-btn:active {
            transform: translateY(0);
        }
        .waypoint-list {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            padding: 12px;
            width: 100%;
            max-width: 400px;
            overflow-y: auto;
            max-height: 200px;
        }
        .waypoint-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            margin-bottom: 8px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 6px;
            border-left: 3px solid var(--purp);
        }
        .waypoint-name {
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .nav-btn {
            background: var(--purp);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }
        .nav-btn:hover {
            background: #9333ea;
        }
        .status-bar {
            position: absolute;
            bottom: 10px;
            left: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.7);
            padding: 8px 12px;
            border-radius: 6px;
            font-family: monospace;
            color: var(--neon);
            font-size: 12px;
            display: flex;
            justify-content: space-between;
            z-index: 5;
        }
        
        .obstacle-alert {
            display: none;
            position: absolute;
            top: 12px;
            left: 12px;
            right: 12px;
            z-index: 30;
            padding: 12px 16px;
            border: 2px solid #ff2d55;
            border-radius: 10px;
            background: rgba(127, 29, 29, 0.96);
            color: #fff;
            font-weight: 800;
            text-align: center;
            box-shadow: 0 0 18px rgba(255, 45, 85, 0.45);
        }
        .obstacle-alert.visible { display: block; }

        /* TeleHealth Panel */
        .telehealth-layout {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
        }
        .telehealth-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            flex: 1;
            width: 100%;
        }
        .video-box {
            background: var(--card);
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            border: 2px solid #334155;
            height: 350px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .video-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(0, 0, 0, 0.7);
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .fall-alert {
            display: none;
            background: linear-gradient(135deg, #7f1d1d 0%, #991b1b 100%);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            animation: pulse 1.5s infinite;
            box-shadow: 0 4px 20px rgba(239, 68, 68, 0.3);
            width: 100%;
        }
        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .action-btn-tele {
            background: var(--accent);
            color: #000;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .action-btn-tele:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(56, 189, 248, 0.3);
        }
        .action-btn-tele:active {
            transform: translateY(0);
        }
        .servo-btn {
            background: var(--danger);
            color: white;
        }
        .close-servo-btn {
            background: var(--success);
            color: white;
        }
        .debug-panel {
            width: 100%;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 8px;
            padding: 10px;
            margin-top: 10px;
            font-size: 10px;
            color: #aaa;
            max-height: 150px;
            overflow-y: auto;
            white-space: pre-wrap;
        }
        .ecg-container {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        /* Animations */
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        /* Responsive adjustments */
        @media (max-width: 1200px) {
            .dashboard-container {
                grid-template-columns: 1fr;
                grid-template-rows: auto auto auto;
            }
            .ecg-panel {
                grid-column: 1;
            }
            .upper-section {
                padding: 10px 0;
            }
        }
        @media (max-width: 768px) {
            .joystick-container {
                width: 140px;
                height: 140px;
            }
            .control-btn {
                padding: 12px;
                font-size: 14px;
                width: 50px;
                height: 50px;
            }
            .grid-empty {
                width: 50px;
                height: 50px;
            }
            .video-box {
                height: 250px;
            }
            .slam-map-container {
                min-height: 250px;
            }
            .action-buttons-slam {
                flex-direction: column;
                align-items: center;
            }
            .slam-controls {
                padding: 15px;
            }
            .control-overlay {
                padding: 15px;
            }
            .telehealth-grid {
                grid-template-columns: 1fr;
            }
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            .action-btn-tele {
                width: 200px;
            }
            .ecg-panel {
                height: 200px;
            }
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <div class="upper-section">
            <div class="dashboard-container">
                <!-- SLAM Panel -->
                <div class="panel">
                    <div class="panel-header">
                        <h2 class="panel-title">
                            🤖 SLAM Navigator
                        </h2>
                        <div class="status-indicator">
                            <span class="status-dot active"></span>
                            <span>System Active</span>
                        </div>
                    </div>
                    
                    <div class="slam-layout">
                        <div class="slam-map-container">
                            <canvas id="map"></canvas>
                            <div id="obstacle-alert" class="obstacle-alert" role="alert" aria-live="assertive">
                                🚨 OBSTACLE DETECTED — ROVER STOPPED
                            </div>
                            <div class="status-bar">
                                <span>🛰️ POS: 0,0</span>
                                <span>🧭 HDG: 0°</span>
                                <span>📊 Obstacles: 0</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- TeleHealth Panel -->
                <div class="panel">
                    <div class="panel-header">
                        <h2 class="panel-title">
                            🩺 TeleHealth Monitor
                        </h2>
                        <div class="status-indicator">
                            <span class="status-dot" id="tele-status"></span>
                            <span id="tele-status-text">System Offline</span>
                        </div>
                    </div>
                    
                    <div id="fall-alert" class="fall-alert">
                        🚨 PATIENT FALL DETECTED - IMMEDIATE ACTION REQUIRED
                    </div>
                    
                    <div class="telehealth-layout">
                        <div class="telehealth-grid">
                            <div class="video-box">
                                <video id="localVideo" autoplay muted playsinline></video>
                                <div class="video-badge">
                                    👨‍⚕️ Doctor Feed
                                </div>
                            </div>
                            <div class="video-box">
                                <video id="remoteVideo" autoplay playsinline></video>
                                <div class="video-badge" style="color: var(--accent)">
                                    🏥 Patient Live Stream
                                </div>
                            </div>
                        </div>
                        
                        <div class="action-buttons">
                            <button class="action-btn-tele" onclick="initDoctor()">
                                📞 ENABLE CAMERA & LISTEN
                            </button>
                            <button class="action-btn-tele" onclick="clearSignalState()" style="background: var(--warning); color: white; font-size: 12px; padding: 8px 16px;">
                                🔄 CLEAR SIGNAL STATE
                            </button>
                            <button class="action-btn-tele servo-btn" onclick="activateServo()">
                                ⚙️ ACTIVATE SERVO (Emergency Aid)
                            </button>
                            <button class="action-btn-tele close-servo-btn" onclick="closeServo()">
                                🔒 CLOSE SERVO
                            </button>
                            <button class="action-btn-tele" onclick="emergencyStop()" style="background: var(--danger);">
                                🚨 EMERGENCY STOP
                            </button>
                        </div>

                        <div id="debug-signal" class="debug-panel">Signal Debug: Waiting for poll...</div>
                    </div>
                </div>

                <!-- ECG Panel -->
                <div class="panel ecg-panel">
                    <div class="panel-header">
                        <h2 class="panel-title">
                            📈 ECG Monitor
                        </h2>
                        <div class="status-indicator">
                            <span class="status-dot" id="ecg-dot"></span>
                            <span id="ecg-status-text">Offline</span>
                        </div>
                    </div>
                    <div class="ecg-container">
                        <canvas id="ecgChart"></canvas>
                    </div>
                </div>

                <div class="control-overlay">
                    <div class="joystick-container">
                        <div class="joystick-row">
                            <div class="grid-empty"></div>
                            <button class="control-btn" onmousedown="cmd('forward')" onmouseup="cmd('stop')">▲</button>
                            <div class="grid-empty"></div>
                        </div>
                        <div class="joystick-row">
                            <button class="control-btn" onmousedown="cmd('left')" onmouseup="cmd('stop')">◀</button>
                            <button class="control-btn stop" onclick="cmd('stop')">■</button>
                            <button class="control-btn" onmousedown="cmd('right')" onmouseup="cmd('stop')">▶</button>
                        </div>
                        <div class="joystick-row">
                            <div class="grid-empty"></div>
                            <button class="control-btn" onmousedown="cmd('back')" onmouseup="cmd('stop')">▼</button>
                            <div class="grid-empty"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="lower-section">
            <div class="slam-controls">
                <div class="action-buttons-slam">
                    <button class="action-btn" onclick="saveRoom()">
                        📍 MARK LOCATION
                    </button>
                    
                    <button class="action-btn" onclick="purgeCoreMemory()" style="background: linear-gradient(135deg, #374151 0%, #4b5563 100%);">
                        🗑️ PURGE MEMORY
                    </button>
                </div>
                
                <div class="waypoint-list" id="wp-list"></div>
            </div>
        </div>
    </div>

    <script>
        // SLAM System (fixed)
        let rx = 300, ry = 225, ra = 0;
        let path = JSON.parse(localStorage.getItem('p')) || [{x:300, y:225, a:0}];
        let rooms = JSON.parse(localStorage.getItem('w')) || [];
        let obs = [];
        let activeCmd = 'stop', lastT = performance.now(), radarSize = 0;
        const logicalW = 600;
        const logicalH = 450;

        const pxPerMs = 0.022, degPerMs = 0.07; 
        const canvas = document.getElementById('map'), ctx = canvas.getContext('2d');

        let navigationInProgress = false;
        async function apiRequest(query = '') {
            const url = 'api.php' + (query ? '?' + query : '') + (query ? '&' : '?') + '_=' + Date.now();
            const response = await fetch(url, { cache: 'no-store' });
            if (!response.ok) throw new Error(`API request failed (${response.status})`);
            return response.json();
        }
        async function cmd(c) {
            activeCmd = c;
            try { await apiRequest('command=' + encodeURIComponent(c)); }
            catch (err) { console.error('Command request failed:', err); activeCmd = 'stop'; }
        }

        function resizeMap() {
            const dpr = window.devicePixelRatio || 1;
            const displayW = canvas.clientWidth;
            const displayH = canvas.clientHeight;
            if (displayW === 0 || displayH === 0) return; // Not rendered yet
            canvas.width = displayW * dpr;
            canvas.height = displayH * dpr;
            const sx = displayW / logicalW;
            const sy = displayH / logicalH;
            ctx.setTransform(dpr * sx, 0, 0, dpr * sy, 0, 0);
        }

        // MAIN UPDATE LOOP
        setInterval(() => {
            let dt = performance.now() - lastT;
            lastT = performance.now();
            if (activeCmd !== 'stop') {
                if (activeCmd === 'forward') { rx += Math.sin(ra*Math.PI/180)*(dt*pxPerMs); ry -= Math.cos(ra*Math.PI/180)*(dt*pxPerMs); }
                if (activeCmd === 'back') { rx -= Math.sin(ra*Math.PI/180)*(dt*pxPerMs); ry += Math.cos(ra*Math.PI/180)*(dt*pxPerMs); }
                if (activeCmd === 'left') ra -= (dt*degPerMs);
                if (activeCmd === 'right') ra += (dt*degPerMs);
                path.push({x:rx, y:ry, a:ra});
            }
            // Animate radar pulse
            radarSize = (radarSize + 2) % 60;
            resizeMap(); // Ensure scaled before draw
            draw();
            document.querySelector('.status-bar').innerHTML = `
                <span>🛰️ POS: ${Math.round(rx)},${Math.round(ry)}</span>
                <span>🧭 HDG: ${Math.round(ra)}°</span>
                <span>📊 Obstacles: ${obs.length}</span>
            `;
        }, 30);

        // OBSTACLE POLLING: accept the explicit ESP32 flag even when distance fields are missing.
        let lastObstacleState = false;
        setInterval(async () => {
            try {
                const response = await fetch('api.php?_=' + Date.now(), { cache: 'no-store' });
                if (!response.ok) throw new Error(`API error: ${response.status}`);
                const data = await response.json();
                console.log('Obstacle data received:', data);

                const left = Number(data.distL);
                const right = Number(data.distR);
                const explicitFlag = Number(data.obstacle) === 1;
                // A distance of 0 is treated as an invalid/no-echo reading, not a confirmed obstacle.
                const closeLeft = Number.isFinite(left) && left > 0 && left < 20;
                const closeRight = Number.isFinite(right) && right > 0 && right < 20;
                const obstacleDetected = explicitFlag || closeLeft || closeRight;

                const alertBox = document.getElementById('obstacle-alert');
                if (alertBox) {
                    alertBox.classList.toggle('visible', obstacleDetected);
                    if (obstacleDetected) {
                        const readings = [];
                        if (closeLeft) readings.push(`Left ${left.toFixed(0)} cm`);
                        if (closeRight) readings.push(`Right ${right.toFixed(0)} cm`);
                        alertBox.textContent = '🚨 OBSTACLE DETECTED — ROVER STOPPED' +
                            (readings.length ? ` (${readings.join(', ')})` : '');
                    }
                }

                // Add one map marker when an obstacle first appears; don't flood the map every poll.
                if (obstacleDetected && !lastObstacleState) {
                    const ox = rx + Math.sin(ra * Math.PI / 180) * 25;
                    const oy = ry - Math.cos(ra * Math.PI / 180) * 25;
                    obs.push({ x: ox, y: oy, t: Date.now() });
                    if (obs.length > 50) obs.shift();
                }
                lastObstacleState = obstacleDetected;
            } catch (error) {
                console.error('Obstacle polling error:', error);
            }
        }, 250);

        async function navigate(targetIdx) {
            if (navigationInProgress) return;
            if (!Array.isArray(path) || !path[targetIdx]) {
                alert('Navigation target is not available. Record a path first.');
                return;
            }
            navigationInProgress = true;
            try {
                const target = path[targetIdx];
                const currentIdx = findClosestPoint();
                let sequence = (targetIdx < currentIdx)
                    ? path.slice(targetIdx, currentIdx + 1).reverse()
                    : path.slice(currentIdx, targetIdx + 1);
                let navPoints = sequence.filter((_, i) => i % 15 === 0);
                if (!navPoints.length || navPoints[navPoints.length - 1] !== target) navPoints.push(target);

                for (const p of navPoints) {
                    const dx = p.x - rx, dy = p.y - ry;
                    const dist = Math.hypot(dx, dy);
                    if (dist < 8) continue;
                    const targetAngle = Math.atan2(dx, -dy) * 180 / Math.PI;
                    let turnA = targetAngle - ra;
                    if (turnA > 180) turnA -= 360;
                    if (turnA < -180) turnA += 360;
                    const turnT = Math.min(10000, Math.abs(turnA) / degPerMs);
                    const moveT = Math.min(15000, dist / pxPerMs);
                    const query = `command=return&turnA=${encodeURIComponent(turnA)}&turnT=${encodeURIComponent(turnT)}&moveT=${encodeURIComponent(moveT)}`;
                    await apiRequest(query);

                    // Wait for the ESP32 to complete the command, but never wait forever.
                    const deadline = Date.now() + 30000;
                    let completed = false;
                    while (Date.now() < deadline) {
                        await new Promise(resolve => setTimeout(resolve, 250));
                        const data = await apiRequest();
                        if (data.command === 'stop') { completed = true; break; }
                        if (data.command === 'emergency') throw new Error('Emergency stop is active.');
                    }
                    if (!completed) throw new Error('Rover did not confirm completion within 30 seconds. Check ESP32 Wi-Fi/API connection.');
                    // This remains a dashboard estimate, not measured physical position.
                    ra = targetAngle;
                    rx = p.x;
                    ry = p.y;
                }
            } catch (err) {
                console.error('Navigation failed:', err);
                alert('Navigation stopped: ' + err.message);
                try { await apiRequest('command=stop'); } catch (_) {}
                activeCmd = 'stop';
            } finally {
                navigationInProgress = false;
            }
        }

        function findClosestPoint() {
            let closest = 0, minD = 9999;
            path.forEach((p, i) => {
                let d = Math.sqrt((p.x-rx)**2 + (p.y-ry)**2);
                if(d < minD) { minD = d; closest = i; }
            });
            return closest;
        }

        function saveRoom() {
            let n = prompt("Room Name:"); if(!n) return;
            rooms.push({ name: n.toUpperCase(), hIdx: path.length-1, x:rx, y:ry });
            localStorage.setItem('w', JSON.stringify(rooms));
            localStorage.setItem('p', JSON.stringify(path));
            renderUI();
        }

        function purgeCoreMemory() {
            if(confirm("Are you sure you want to purge all core memory?")) {
                localStorage.clear();
                location.reload();
            }
        }

        function renderUI() {
            document.getElementById('wp-list').innerHTML = rooms.map(r => `
                <div class="waypoint-item">
                    <span class="waypoint-name">📍 ${r.name}</span>
                    <button class="nav-btn" onclick="navigate(${r.hIdx})">NAVIGATE</button>
                </div>
            `).join('');
        }

        function draw() {
            ctx.clearRect(0,0, logicalW, logicalH);
            
            // Path
            ctx.strokeStyle = "rgba(0, 212, 255, 0.1)"; 
            ctx.setLineDash([5,5]);
            ctx.beginPath(); 
            path.forEach((p,i) => i==0 ? ctx.moveTo(p.x,p.y) : ctx.lineTo(p.x,p.y)); 
            ctx.stroke();
            ctx.setLineDash([]);
            
            // Obstacles - IMPROVED: Better rendering with error handling and fallback
            obs.forEach(o => {
                try {
                    ctx.save();
                    ctx.fillStyle = "rgba(255, 45, 85, 0.8)";
                    ctx.fillRect(o.x - 10, o.y - 10, 20, 20);
                    
                    // Try to render emoji, but fallback to square if it fails
                    try {
                        ctx.font = "20px Arial";
                        ctx.textAlign = "center";
                        ctx.textBaseline = "middle";
                        ctx.fillText("🧱", o.x, o.y);
                    } catch (e) {
                        console.warn('Emoji rendering failed, using square fallback');
                    }
                    ctx.restore();
                } catch (e) {
                    console.warn('Error rendering obstacle:', e);
                }
            });
            
            // Waypoints
            rooms.forEach(r => { 
                ctx.fillStyle="#bb86fc";
                ctx.font = "16px Arial";
                ctx.fillText("📍", r.x-8, r.y+5);
                ctx.font = "12px Arial";
                ctx.fillText(r.name, r.x+12, r.y+4);
            });
            
            // Rover + Radar
            ctx.save(); 
            ctx.translate(rx,ry); 
            ctx.rotate(ra*Math.PI/180);
            ctx.strokeStyle = "rgba(0, 212, 255, " + (1 - radarSize/60) + ")";
            ctx.beginPath(); 
            ctx.arc(0,0, radarSize, 0, 7); 
            ctx.stroke();
            ctx.fillStyle="#00d4ff"; 
            ctx.beginPath(); 
            ctx.moveTo(0,-18); 
            ctx.lineTo(12,12); 
            ctx.lineTo(-12,12); 
            ctx.closePath(); 
            ctx.fill();
            ctx.restore();
        }

        // Initialize SLAM
        window.addEventListener('resize', resizeMap);
        setTimeout(() => { resizeMap(); renderUI(); draw(); }, 100); // Delay for layout

        // TeleHealth System
        let pc;
        let localStream;
        let processedCandCount = 0;
        let pollInterval;
        const config = { iceServers: [{ urls: "stun:stun.l.google.com:19302" }] };
        const POLL_INTERVAL = 500;
        const localVideo = document.getElementById("localVideo");
        const remoteVideo = document.getElementById("remoteVideo");
        const fallAlert = document.getElementById("fall-alert");
        const debugSignal = document.getElementById("debug-signal");

        // Audio context for beep sound
        let audioContext;
        let oscillator;
        let gainNode;

        function playBeep() {
            try {
                // Create audio context if it doesn't exist
                if (!audioContext) {
                    audioContext = new (window.AudioContext || window.webkitAudioContext)();
                }
                
                // Stop any existing sound
                if (oscillator) {
                    oscillator.stop();
                    oscillator = null;
                }

                // Create oscillator for beep
                oscillator = audioContext.createOscillator();
                gainNode = audioContext.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                // Set beep parameters
                oscillator.frequency.value = 800; // Frequency in Hz
                oscillator.type = 'sine'; // Sine wave for smooth beep
                
                gainNode.gain.setValueAtTime(0.3, audioContext.currentTime); // Volume
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 1); // Fade out
                
                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 1); // Stop after 1 second
                
            } catch (e) {
                console.error('Beep sound error:', e);
                // Fallback: try to use simple alert if audio fails
                if (typeof window.speechSynthesis !== 'undefined') {
                    const utterance = new SpeechSynthesisUtterance('Fall detected!');
                    window.speechSynthesis.speak(utterance);
                }
            }
        }

        async function postToSignal(data) {
            try {
                const res = await fetch("signal.php", { method: "POST", body: JSON.stringify(data) });
                const resData = await res.json();
                console.log("Signal POST response:", resData, "for data:", data);
                return resData;
            } catch (e) {
                console.error("Signal POST error:", e, "for data:", data);
                throw e;
            }
        }

        async function initDoctor() {
            updateTeleStatus("Initializing...", "warning");
            debugSignal.innerText = "Initializing...";

            // Stop existing poll and close PC if active
            if (pollInterval) {
                clearInterval(pollInterval);
                pollInterval = null;
            }
            if (pc) {
                pc.close();
                pc = null;
            }
            processedCandCount = 0;
            remoteVideo.srcObject = null;
            fallAlert.style.display = "none";

            // Get or reuse local stream
            if (!localStream) {
                try {
                    localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
                    localVideo.srcObject = localStream;
                } catch (err) {
                    updateTeleStatus("Camera Permission Denied", "danger");
                    debugSignal.innerText = "Camera error: " + err.message;
                    console.error(err);
                    return;
                }
            } else {
                localVideo.srcObject = localStream;
            }

            updateTeleStatus("Camera Active - Waiting for Patient...", "warning");
            debugSignal.innerText = "Polling started - check console for details";

            pc = null;

            // Start continuous polling with immediate first call
            pollSignal();
            pollInterval = setInterval(pollSignal, POLL_INTERVAL);
        }

        async function pollSignal() {
            try {
                const res = await fetch("signal.php");
                if (!res.ok) {
                    throw new Error(`HTTP ${res.status}: ${res.statusText}`);
                }
                const data = await res.json();
                console.log("Dashboard poll data:", data);
                debugSignal.innerText = `Last Signal: offer=${!!data.offer}, answer=${!!data.answer}, fall=${data.fall}, pCands=${data.pCand?.length || 0}, dCands=${data.dCand?.length || 0}`;

                // Handle fall detection
                if (data.fall === true) {
                    fallAlert.style.display = "block";
                    updateTeleStatus("FALL DETECTED!", "danger");
                    playBeep(); // Play beep sound when fall is detected
                } else {
                    fallAlert.style.display = "none";
                }

                // Handle disconnection
                if (data.offer === null || data.offer === undefined) {
                    if (pc && pc.signalingState !== 'closed') {
                        pc.close();
                        pc = null;
                        processedCandCount = 0;
                        remoteVideo.srcObject = null;
                        updateTeleStatus("Patient Disconnected - Waiting...", "warning");
                    }
                    return;
                }

                // Auto-connect if offer received and no active PC
                if (data.offer && (!pc || pc.signalingState === 'closed' || pc.signalingState === 'failed')) {
                    console.log("Offer detected, initializing connection");
                    await initFromOffer(data.offer);
                }

                // Add new patient ICE candidates if PC active
                if (pc && pc.signalingState !== 'closed' && pc.signalingState !== 'failed' && data.pCand && data.pCand.length > processedCandCount) {
                    const newCount = data.pCand.length;
                    for (let i = processedCandCount; i < newCount; i++) {
                        try {
                            await pc.addIceCandidate(new RTCIceCandidate(data.pCand[i]));
                            console.log("Added patient ICE candidate");
                        } catch (e) {
                            console.warn("ICE add failed:", e);
                        }
                    }
                    processedCandCount = newCount;
                }

            } catch (e) {
                console.error("Polling error:", e);
                debugSignal.innerText = "Poll error: " + e.message;
                updateTeleStatus("Signal Poll Failed", "danger");
            }
        }

        async function initFromOffer(offer) {
            try {
                console.log("Starting initFromOffer with offer type:", offer.type);
                pc = new RTCPeerConnection(config);

                // Add existing local tracks
                if (localStream) {
                    localStream.getTracks().forEach(track => pc.addTrack(track, localStream));
                }

                // Handle remote stream
                pc.ontrack = e => {
                    remoteVideo.srcObject = e.streams[0];
                    updateTeleStatus("Live Stream Active", "success");
                    console.log("Remote stream received");
                };

                // Send doctor ICE candidates
                pc.onicecandidate = e => {
                    if (e.candidate) {
                        postToSignal({ type: "candidate", from: "doctor", candidate: e.candidate });
                    }
                };

                // Set remote description, create/send answer
                await pc.setRemoteDescription(new RTCSessionDescription(offer));
                console.log("Remote desc set");
                const answer = await pc.createAnswer();
                await pc.setLocalDescription(answer);
                console.log("Local desc set");
                await postToSignal({ type: "answer", sdp: answer });
                console.log("Answer sent");

                updateTeleStatus("Handshaking...", "warning");

            } catch (e) {
                console.error("Connection init failed:", e);
                if (pc) {
                    pc.close();
                    pc = null;
                }
                updateTeleStatus("Connection Failed - Check Console", "danger");
                debugSignal.innerText += "\nInit error: " + e.message;
            }
        }

        async function clearSignalState() {
            try {
                const res = await postToSignal({ type: 'clear' });
                updateTeleStatus("Signal State Cleared", "warning");
                debugSignal.innerText = "State cleared - response: " + JSON.stringify(res);
                // Trigger a poll to update
                pollSignal();
            } catch (e) {
                console.error("Clear state error:", e);
                debugSignal.innerText = "Clear error: " + e.message;
            }
        }

        function updateTeleStatus(text, color) {
            document.getElementById("tele-status-text").innerText = text;
            const statusDot = document.getElementById("tele-status");
            statusDot.className = "status-dot";
            if (color === "success") statusDot.classList.add("active");
            else if (color === "warning") statusDot.classList.add("warning");
            else if (color === "danger") statusDot.classList.add("danger");
        }

        async function emergencyStop() {
            fallAlert.style.display = "block";
            updateTeleStatus("Emergency Stop Activated", "danger");
            playBeep(); // Play beep for emergency stop too
            try { await apiRequest('command=stop'); } catch (e) { console.error('Stop command failed:', e); }
            if (pc) {
                pc.close();
                pc = null;
            }
            if (pollInterval) {
                clearInterval(pollInterval);
                pollInterval = null;
            }
            if (localStream) {
                localStream.getTracks().forEach(track => track.stop());
                localStream = null;
            }
            localVideo.srcObject = null;
            remoteVideo.srcObject = null;
            debugSignal.innerText = "Emergency stop - reset";
        }

        async function activateServo() {
            // The current ESP32 sketch does not implement activate_servo yet.
            updateTeleStatus('Servo activation is not implemented in the current ESP32 firmware', 'warning');
            alert('The dashboard/API can send commands, but the current ESP32 sketch has no activate_servo handler. Add that firmware handler before using the servo button.');
        }

        async function closeServo() {
            // The current ESP32 sketch does not implement close_servo yet.
            updateTeleStatus('Servo control is not implemented in the current ESP32 firmware', 'warning');
            alert('The current ESP32 sketch has no close_servo handler. Add the correct servo pin/control code before using this button.');
        }

        // ECG Graph System
        let ecgChart;
        let ecgPollInterval;

        function updateECGStatus(isOnline) {
            document.getElementById("ecg-status-text").innerText = isOnline ? "Live" : "Offline";
            const dot = document.getElementById("ecg-dot");
            dot.className = "status-dot";
            if (isOnline) {
                dot.classList.add("active");
            } else {
                dot.classList.add("danger");
            }
        }

        function setFlatGraph() {
            const flatLabels = Array.from({length: 50}, (_, i) => i);
            const flatData = Array(50).fill(0);
            ecgChart.data.labels = flatLabels;
            ecgChart.data.datasets[0].data = flatData;
            ecgChart.update('none');
        }

        function initECG() {
            const ecgCtx = document.getElementById('ecgChart').getContext('2d');
            ecgChart = new Chart(ecgCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'ECG Signal',
                        data: [],
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 4096,
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    animation: {
                        duration: 0
                    }
                }
            });

            // Start polling for ECG data every 250ms (adjust as needed for smoothness)
            ecgPollInterval = setInterval(updateECG, 250);
            updateECG(); // Initial fetch
        }

        function updateECG() {
            fetch('api_monitor.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data && data.length > 0) {
                        const latestReceived = data[data.length - 1].received_at * 1000; // to ms
                        const now = Date.now();
                        const isRecent = (now - latestReceived) < 2000; // 2 seconds threshold
                        updateECGStatus(isRecent);
                        if (isRecent) {
                            // Plot last 100 points for recent data
                            const recentData = data.slice(-100);
                            const labels = recentData.map((_, index) => index);
                            const values = recentData.map(d => d.leads_off ? 3000 : d.ecg);
                            ecgChart.data.labels = labels;
                            ecgChart.data.datasets[0].data = values;
                        } else {
                            setFlatGraph();
                        }
                        ecgChart.update('none');
                    } else {
                        updateECGStatus(false);
                        setFlatGraph();
                    }
                })
                .catch(error => {
                    console.error('ECG data fetch error:', error);
                    updateECGStatus(false);
                    setFlatGraph();
                });
        }

        // Initialize ECG on page load
        initECG();
    </script>
</body>
</html>