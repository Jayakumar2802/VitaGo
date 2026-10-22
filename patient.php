<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Patient Phone</title>
    <style>
        body { font-family: sans-serif; background: #111; color: white; text-align: center; }
        .video-container { position: relative; width: 100%; max-width: 500px; margin: auto; background: #000; border-radius: 12px; overflow: hidden; }
        video { width: 100%; height: auto; display: block; }
        .remote-video { position: absolute; top: 10px; right: 10px; width: 30%; border: 2px solid #2563eb; border-radius: 8px; background: #222; }
        .btn { background: #2563eb; color: white; padding: 15px 30px; border: none; border-radius: 30px; margin: 20px; font-weight: bold; cursor: pointer; }
        .status-box { background: #222; padding: 15px; margin: 10px; border-radius: 8px; display: none; }
        .hidden { display: none !important; }
        .simulate-btn { background: #ef4444; margin: 10px; }
        .debug-panel {
            background: rgba(0, 0, 0, 0.5);
            border-radius: 8px;
            padding: 10px;
            margin: 10px;
            font-size: 10px;
            color: #aaa;
            max-height: 150px;
            overflow-y: auto;
            white-space: pre-wrap;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Patient View</h2>
        <button id="startBtn" class="btn" onclick="init()">Connect to Doctor</button>
        
        <div class="video-container">
            <video id="local" autoplay muted playsinline></video>
            <video id="remote" class="remote-video" autoplay playsinline></video>
        </div>
        
        <div id="status">Ready</div>
        <div id="analysis" class="status-box">
            Posture: <span id="posture">-</span> | Risk: <span id="fallRisk">-</span>
        </div>
        
        <button id="simulateFallBtn" class="btn simulate-btn" onclick="simulateFall()">🚨 Simulate Fall Detection</button>
        
        <div class="controls">
            <button id="stopBtn" class="btn hidden" style="background:#ef4444" onclick="endSession()">❌ End Session</button>
            <button id="clearBtn" class="btn" style="background: #f59e0b; margin: 10px;" onclick="clearSignalState()">🔄 Clear Signal State</button>
        </div>

        <div id="debug-signal" class="debug-panel">Signal Debug: Ready</div>
    </div>

    <script>
    let pc, localStream, pollInterval;
    let processedCandCount = 0;
    const config = { iceServers: [{ urls: "stun:stun.l.google.com:19302" }] };
    const POLL_INTERVAL = 500;
    const statusEl = document.getElementById('status');
    const debugSignal = document.getElementById("debug-signal");

    async function postToSignal(data) {
        try {
            const response = await fetch("signal.php", { method: "POST", body: JSON.stringify(data) });
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            const resData = await response.json();
            console.log("Signal POST response:", resData, "for data:", data);
            return resData;
        } catch (e) {
            console.error("Signal POST error:", e, "for data:", data);
            throw e;
        }
    }

    async function pollPatient() {
        if (!pc) return;

        try {
            const res = await fetch("signal.php");
            if (!res.ok) {
                throw new Error(`HTTP ${res.status}`);
            }
            const data = await res.json();
            console.log("Patient poll data:", data);
            debugSignal.innerText = `Last Signal: offer=${!!data.offer}, answer=${!!data.answer}, fall=${data.fall}, dCands=${data.dCand?.length || 0}, pCands=${data.pCand?.length || 0}`;

            // If doctor sends answer
            if (data.answer && !pc.remoteDescription) {
                await pc.setRemoteDescription(new RTCSessionDescription(data.answer));
                updateStatus("Connected", "#22c55e");
                console.log("Answer received, connection established");
            }

            // If doctor sends ICE candidates
            if (data.dCand && data.dCand.length > processedCandCount) {
                for (let i = processedCandCount; i < data.dCand.length; i++) {
                    try {
                        await pc.addIceCandidate(new RTCIceCandidate(data.dCand[i]));
                        console.log("Added doctor ICE candidate");
                    } catch(e) { 
                        console.warn("ICE error", e); 
                    }
                }
                processedCandCount = data.dCand.length;
            }
        } catch (e) {
            console.error("Patient polling error:", e);
            debugSignal.innerText = "Poll error: " + e.message;
        }
    }

    async function init() {
        // UI Updates
        document.getElementById('startBtn').classList.add('hidden');
        document.getElementById('stopBtn').classList.remove('hidden');
        document.getElementById('analysis').style.display = 'block';
        updateStatus("Connecting...", "orange");
        debugSignal.innerText = "Connecting...";

        try {
            // 1. Get Media
            localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
            document.getElementById('local').srcObject = localStream;
            
            // 2. Create New PeerConnection
            pc = new RTCPeerConnection(config);
            
            // 3. Add tracks
            localStream.getTracks().forEach(t => pc.addTrack(t, localStream));

            // 4. Handle incoming stream
            pc.ontrack = e => { 
                document.getElementById('remote').srcObject = e.streams[0]; 
                console.log("Doctor stream received");
            };

            // 5. Handle ICE candidates
            pc.onicecandidate = e => { 
                if (e.candidate) {
                    postToSignal({ type: 'candidate', from: 'patient', candidate: e.candidate });
                } 
            };

            // 6. Create and send Offer
            const offer = await pc.createOffer();
            await pc.setLocalDescription(offer);
            const offerResData = await postToSignal({ type: 'offer', sdp: offer });
            console.log("Offer sent, starting poll");
            debugSignal.innerText += "\nOffer sent OK";

            // 7. Polling - immediate + interval
            processedCandCount = 0;
            pollPatient(); // Immediate
            pollInterval = setInterval(pollPatient, POLL_INTERVAL);

            // Timeout for connection
            setTimeout(() => {
                if (!pc.remoteDescription && pc.signalingState === 'stable') {
                    updateStatus("Connection Timeout - Try Again", "red");
                    debugSignal.innerText += "\nTimeout - no answer received";
                }
            }, 10000);

        } catch (e) { 
            updateStatus("Error: " + e.message, "red"); 
            debugSignal.innerText = "Init error: " + e.message;
            endSession();
        }
    }

    async function simulateFall() {
        try {
            const fallResData = await postToSignal({ type: 'fall', value: true });
            console.log("Fall sent");
            document.getElementById('fallRisk').innerText = 'HIGH';
            document.getElementById('posture').innerText = 'FALLEN';
            updateStatus("Fall Detected! Alert Sent", "red");
            // Auto-reset after 5s for demo
            setTimeout(async () => {
                await postToSignal({ type: 'fall', value: false });
                document.getElementById('fallRisk').innerText = 'LOW';
                document.getElementById('posture').innerText = 'NORMAL';
                updateStatus("Fall Resolved", "#22c55e");
            }, 5000);
        } catch (e) {
            console.error("Fall sim error:", e);
            debugSignal.innerText += "\nFall error: " + e.message;
        }
    }

    async function endSession() {
        if (pollInterval) clearInterval(pollInterval);
        pollInterval = null;
        
        if (pc) {
            pc.close();
            pc = null;
        }

        if (localStream) {
            localStream.getTracks().forEach(t => t.stop());
            document.getElementById('local').srcObject = null;
            document.getElementById('remote').srcObject = null;
            localStream = null;
        }

        try {
            const clearResData = await postToSignal({ type: 'clear' });
            console.log("Session cleared");
            debugSignal.innerText = "Session ended & cleared";
        } catch (e) {
            console.error("Clear error:", e);
            debugSignal.innerText += "\nClear error: " + e.message;
        }

        // Reset UI
        document.getElementById('startBtn').classList.remove('hidden');
        document.getElementById('stopBtn').classList.add('hidden');
        document.getElementById('analysis').style.display = 'none';
        document.getElementById('posture').innerText = '-';
        document.getElementById('fallRisk').innerText = '-';
        updateStatus("Session Ended", "gray");
    }

    async function clearSignalState() {
        try {
            const resData = await postToSignal({ type: 'clear' });
            updateStatus("Signal Cleared", "orange");
            debugSignal.innerText = "Signal cleared manually";
            if (pollInterval) pollPatient(); // Poll to update
        } catch (e) {
            console.error("Manual clear error:", e);
            debugSignal.innerText = "Manual clear error: " + e.message;
        }
    }

    function updateStatus(txt, col) { 
        statusEl.innerText = txt; 
        statusEl.style.color = col; 
    }
    </script>
</body>
</html>