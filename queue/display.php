<?php
// queue/display.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';

// Allowed roles: admin, staff, bhw (standard access required to view monitor)
require_role(['admin', 'staff', 'bhw']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Public Queue Monitor - Sinalhan Health Center</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #072227 0%, #06101e 100%);
            --primary-color: #0D7377;
            --primary-light: #14C38E;
            --accent-color: #F9D923;
            --text-light: #F8F9FA;
            --glass-bg: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.08);
            --glass-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-gradient);
            color: var(--text-light);
            min-height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            margin: 0;
            padding: 20px;
        }

        /* Glassmorphism card utility */
        .glass-panel {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            box-shadow: var(--glass-shadow);
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            margin-bottom: 20px;
        }

        .header-logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-logo i {
            font-size: 32px;
            color: var(--primary-light);
            animation: pulse 2s infinite;
        }

        .header-logo h1 {
            font-size: 24px;
            font-weight: 800;
            margin: 0;
            letter-spacing: 1px;
            background: linear-gradient(to right, #ffffff, #a3b8cc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header-logo span {
            font-size: 12px;
            color: var(--primary-light);
            text-transform: uppercase;
            letter-spacing: 2px;
            display: block;
        }

        .clock-container {
            text-align: right;
        }

        #clock-time {
            font-size: 28px;
            font-weight: 800;
            color: #ffffff;
            margin: 0;
        }

        #clock-date {
            font-size: 14px;
            color: #a3b8cc;
            margin: 0;
            letter-spacing: 1px;
        }

        .main-layout {
            flex-grow: 1;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            height: calc(100vh - 140px);
        }

        /* Now Serving Section */
        .serving-container {
            display: flex;
            flex-direction: column;
            padding: 30px;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .serving-label {
            font-size: 18px;
            font-weight: 600;
            letter-spacing: 4px;
            text-transform: uppercase;
            color: var(--primary-light);
            margin-bottom: 20px;
        }

        .serving-card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
            width: 100%;
            height: 100%;
            align-content: center;
            justify-items: center;
            overflow-y: auto;
        }

        .serving-card {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid var(--primary-light);
            border-radius: 25px;
            padding: 35px 25px;
            text-align: center;
            box-shadow: 0 0 30px rgba(20, 195, 142, 0.15);
            width: 100%;
            max-width: 450px;
            animation: cardEntrance 0.6s ease-out;
            position: relative;
        }

        .serving-number {
            font-size: 90px;
            font-weight: 800;
            color: var(--accent-color);
            line-height: 1;
            margin-bottom: 15px;
            letter-spacing: 2px;
            text-shadow: 0 0 20px rgba(249, 217, 35, 0.3);
            animation: flashText 1.5s infinite alternate;
        }

        .serving-name {
            font-size: 24px;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 5px;
        }

        .serving-service {
            font-size: 16px;
            color: var(--primary-light);
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 600;
        }

        /* Sidebar Up Next */
        .sidebar-container {
            display: flex;
            flex-direction: column;
            padding: 25px;
        }

        .sidebar-label {
            font-size: 18px;
            font-weight: 600;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #a3b8cc;
            border-bottom: 1px solid var(--glass-border);
            padding-bottom: 12px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sidebar-label span {
            font-size: 12px;
            background: rgba(255, 255, 255, 0.1);
            padding: 3px 8px;
            border-radius: 10px;
            color: #ffffff;
        }

        .waiting-list {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 15px;
            overflow-y: auto;
        }

        .waiting-item {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--glass-border);
            border-radius: 15px;
            transition: all 0.3s ease;
        }

        .waiting-item:hover {
            background: rgba(255, 255, 255, 0.05);
            transform: translateX(5px);
        }

        .waiting-number {
            font-size: 24px;
            font-weight: 800;
            color: var(--accent-color);
            background: rgba(249, 217, 35, 0.1);
            padding: 5px 12px;
            border-radius: 10px;
            min-width: 70px;
            text-align: center;
        }

        .waiting-details {
            flex-grow: 1;
        }

        .waiting-name {
            font-size: 16px;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 2px;
        }

        .waiting-service {
            font-size: 12px;
            color: #a3b8cc;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .no-records {
            text-align: center;
            padding: 50px 20px;
            color: #a3b8cc;
            font-style: italic;
        }

        /* Animations */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        @keyframes cardEntrance {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes flashText {
            0% { opacity: 0.85; }
            100% { opacity: 1; text-shadow: 0 0 25px rgba(249, 217, 35, 0.6); }
        }

        /* Voice unlock banner style */
        .audio-banner {
            background: #e74c3c;
            color: white;
            text-align: center;
            padding: 8px;
            font-size: 14px;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>

    <!-- Audio Autoplay Policy unlocking overlay -->
    <div id="audio-overlay" class="audio-banner">
        <span><i class="bi bi-volume-up-fill animate-pulse"></i> Public announcer is disabled. Click here to enable voice alerts.</span>
        <button class="btn btn-sm btn-light py-0 px-2 fw-bold" onclick="unlockAudio()">Enable</button>
    </div>

    <!-- Screen Header -->
    <header class="glass-panel">
        <div class="header-logo">
            <i class="bi bi-hospital"></i>
            <div>
                <h1>SINALHAN HEALTH CENTER</h1>
                <span>Public Queuing Monitor System</span>
            </div>
        </div>
        <div class="clock-container">
            <div id="clock-time">12:00:00 PM</div>
            <div id="clock-date">Friday, May 29, 2026</div>
        </div>
    </header>

    <!-- Main Grid -->
    <div class="main-layout">
        
        <!-- Left Side: Now Serving -->
        <div class="serving-container glass-panel">
            <div class="serving-label"><i class="bi bi-broadcast text-danger animate-pulse me-1"></i> NOW SERVING</div>
            
            <div class="serving-card-grid" id="serving-grid">
                <!-- Dynamic serving cards go here -->
                <div class="no-records">
                    <i class="bi bi-hourglass fs-1 d-block mb-3"></i>
                    <h4>Waiting for staff calls...</h4>
                    <p class="small text-secondary">Queue tickets will display here once called by attending clinic staff.</p>
                </div>
            </div>
        </div>

        <!-- Right Side: Up Next -->
        <div class="sidebar-container glass-panel">
            <div class="sidebar-label">
                <span>UP NEXT</span>
                <span id="waiting-count">0 waiting</span>
            </div>
            
            <div class="waiting-list" id="waiting-list-container">
                <!-- Dynamic upcoming tickets go here -->
                <div class="no-records">
                    <p class="small">Queue is currently empty.</p>
                </div>
            </div>
        </div>

    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let lastAnnouncedNumber = null;
        let audioUnlocked = false;

        // Unlock audio block on touch/click
        function unlockAudio() {
            audioUnlocked = true;
            document.getElementById('audio-overlay').style.display = 'none';
            // Play test chime
            playChime();
        }

        // Play Web Audio frequency sweep (Double-Beep)
        function playChime() {
            if (!audioUnlocked) return;
            try {
                let audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                let osc = audioCtx.createOscillator();
                let gain = audioCtx.createGain();
                
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                
                osc.type = 'sine';
                osc.frequency.setValueAtTime(587.33, audioCtx.currentTime); // D5
                osc.frequency.setValueAtTime(880, audioCtx.currentTime + 0.12); // A5
                
                gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.35);
                
                osc.start(audioCtx.currentTime);
                osc.stop(audioCtx.currentTime + 0.35);
            } catch (e) {
                console.warn('Web Audio failure:', e);
            }
        }

        // Announce Voice Synthesizer
        function speakTicket(number, service) {
            if (!audioUnlocked) return;
            if ('speechSynthesis' in window) {
                // Cancel existing announcement if any
                window.speechSynthesis.cancel();
                
                let text = "Ticket number " + parseInt(number) + ". Please proceed to " + service;
                let utterance = new SpeechSynthesisUtterance(text);
                utterance.rate = 0.85;
                utterance.pitch = 1.0;
                window.speechSynthesis.speak(utterance);
            }
        }

        // Live Clock Updates
        function updateClock() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
            const dateStr = now.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
            
            document.getElementById('clock-time').textContent = timeStr;
            document.getElementById('clock-date').textContent = dateStr;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Render Active Queue Data
        function renderQueueData(response) {
            if (response.success) {
                // 1. Render Now Serving Grid
                let servingHtml = '';
                if (response.serving.length > 0) {
                    response.serving.forEach(item => {
                        servingHtml += `
                            <div class="serving-card">
                                <div class="serving-number">#${item.number}</div>
                                <div class="serving-name">${item.patient_name}</div>
                                <div class="serving-service">${item.service_name}</div>
                            </div>
                        `;
                    });
                    
                    // Check if a new ticket has been called
                    let topTicket = response.serving[0];
                    if (topTicket.number !== lastAnnouncedNumber) {
                        lastAnnouncedNumber = topTicket.number;
                        playChime();
                        // Announce after chime delay
                        setTimeout(function() {
                            speakTicket(topTicket.number, topTicket.service_name);
                        }, 500);
                    }
                } else {
                    servingHtml = `
                        <div class="no-records">
                            <i class="bi bi-hourglass fs-1 d-block mb-3"></i>
                            <h4>Waiting for staff calls...</h4>
                            <p class="small text-secondary">Queue tickets will display here once called by attending clinic staff.</p>
                        </div>
                    `;
                    lastAnnouncedNumber = null;
                }
                document.getElementById('serving-grid').innerHTML = servingHtml;

                // 2. Render Upcoming list
                let waitingHtml = '';
                document.getElementById('waiting-count').textContent = response.waiting.length + " waiting";
                
                if (response.waiting.length > 0) {
                    response.waiting.forEach(item => {
                        waitingHtml += `
                            <div class="waiting-item">
                                <div class="waiting-number">#${item.number}</div>
                                <div class="waiting-details">
                                    <div class="waiting-name">${item.patient_name}</div>
                                    <div class="waiting-service">${item.service_name}</div>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    waitingHtml = `
                        <div class="no-records">
                            <p class="small">Queue is currently empty.</p>
                        </div>
                    `;
                }
                document.getElementById('waiting-list-container').innerHTML = waitingHtml;
            }
        }

        // Establish Server-Sent Events (SSE) connection
        let sseSource = null;

        function startSSE() {
            if (typeof(EventSource) !== "undefined") {
                console.log("Starting Server-Sent Events for Queue stream...");
                sseSource = new EventSource('../ajax/queue_sse.php');
                
                sseSource.onmessage = function(event) {
                    try {
                        const data = JSON.parse(event.data);
                        renderQueueData(data);
                    } catch (e) {
                        console.error("Failed to parse SSE data:", e);
                    }
                };

                sseSource.onerror = function(err) {
                    console.error("SSE stream connection error. Retrying in 5 seconds...");
                    sseSource.close();
                    setTimeout(startSSE, 5000);
                };
            } else {
                console.warn("Browser does not support EventSource. Falling back to AJAX polling.");
                // Fallback: AJAX Polling
                function pollQueue() {
                    $.ajax({
                        url: '../ajax/active_queue.php',
                        method: 'GET',
                        dataType: 'json',
                        success: renderQueueData,
                        error: function(xhr, status, error) {
                            console.error("Queue monitor poll failure: ", error);
                        }
                    });
                }
                setInterval(pollQueue, 5000);
                pollQueue();
            }
        }

        startSSE();
    </script>
</body>
</html>
