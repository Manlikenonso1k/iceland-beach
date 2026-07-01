<x-filament-panels::page>
    <style>
        .scanner-container {
            max-width: 600px;
            margin: 0 auto;
        }
        #qr-reader {
            width: 100%;
            border-radius: 12px;
            overflow: hidden;
            border: 2px solid #e2e8f0;
        }
        #qr-reader video {
            border-radius: 12px;
        }
        .scan-controls {
            display: flex;
            gap: 12px;
            margin: 16px 0;
        }
        .scan-btn {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .scan-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .btn-start {
            background: #227190;
            color: #fff;
        }
        .btn-start:hover:not(:disabled) { background: #1a5870; }
        .btn-stop {
            background: #ef4444;
            color: #fff;
        }
        .btn-stop:hover:not(:disabled) { background: #dc2626; }

        /* Result display */
        .result-card {
            margin-top: 20px;
            border-radius: 12px;
            padding: 24px;
            color: #fff;
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .result-ok { background: linear-gradient(135deg, #15803d 0%, #166534 100%); }
        .result-already { background: linear-gradient(135deg, #d97706 0%, #92400e 100%); }
        .result-unpaid { background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); }
        .result-error { background: linear-gradient(135deg, #6b7280 0%, #374151 100%); }

        .result-icon { font-size: 48px; text-align: center; margin-bottom: 12px; }
        .result-headline {
            font-size: 24px;
            font-weight: 800;
            text-align: center;
            margin-bottom: 8px;
        }
        .result-sub {
            text-align: center;
            opacity: 0.9;
            margin-bottom: 16px;
            font-size: 14px;
        }
        .result-details {
            background: rgba(255,255,255,0.15);
            border-radius: 8px;
            padding: 14px 18px;
            font-size: 14px;
            line-height: 2;
        }
        .result-details strong {
            display: inline-block;
            min-width: 90px;
            opacity: 0.75;
        }
        .scan-count-badge {
            display: block;
            text-align: center;
            margin-top: 12px;
            padding: 6px 16px;
            border-radius: 100px;
            font-weight: 700;
            font-size: 13px;
        }
        .scan-low { background: rgba(255,255,255,0.2); }
        .scan-warn { background: #fbbf24; color: #78350f; }
        .scan-danger { background: #ef4444; color: #fff; }

        .fraud-alert {
            margin-top: 12px;
            padding: 10px 16px;
            background: rgba(239,68,68,0.3);
            border: 2px solid rgba(239,68,68,0.6);
            border-radius: 8px;
            font-weight: 700;
            font-size: 13px;
            text-align: center;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        /* Recent scans log */
        .scan-log {
            margin-top: 24px;
        }
        .scan-log h3 {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 10px;
            color: #64748b;
        }
        .scan-log-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 6px;
            font-size: 13px;
            background: #fff;
        }
        .scan-log-item .log-status {
            font-size: 20px;
            flex-shrink: 0;
        }
        .scan-log-item .log-name { font-weight: 600; }
        .scan-log-item .log-time {
            margin-left: auto;
            color: #94a3b8;
            font-size: 12px;
        }

        .dark .scan-log-item {
            background: rgba(255,255,255,0.05);
            border-color: rgba(255,255,255,0.1);
        }
        .dark .scan-log h3 { color: #94a3b8; }

        .camera-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 300px;
            background: #f1f5f9;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            color: #94a3b8;
            font-size: 14px;
            gap: 10px;
        }
        .dark .camera-placeholder {
            background: rgba(255,255,255,0.05);
            border-color: rgba(255,255,255,0.15);
        }
        .camera-placeholder .cam-icon { font-size: 48px; }
    </style>

    <div class="scanner-container">
        {{-- Camera area --}}
        <div id="camera-placeholder" class="camera-placeholder">
            <span class="cam-icon">📷</span>
            <span>Press "Start Scanner" to activate camera</span>
        </div>
        <div id="qr-reader" style="display:none;"></div>

        {{-- Controls --}}
        <div class="scan-controls">
            <button class="scan-btn btn-start" id="btn-start" onclick="startScanner()">
                📷 Start Scanner
            </button>
            <button class="scan-btn btn-stop" id="btn-stop" onclick="stopScanner()" disabled>
                ⏹ Stop Scanner
            </button>
        </div>

        {{-- Result display --}}
        <div id="scan-result"></div>

        {{-- Recent scans log --}}
        <div class="scan-log">
            <h3>Recent Scans This Session</h3>
            <div id="scan-log-list">
                <p style="color:#94a3b8;font-size:13px;">No scans yet.</p>
            </div>
        </div>
    </div>

    {{-- html5-qrcode library --}}
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

    <script>
        let html5QrCode = null;
        let isScanning = false;
        let scanLog = [];
        let lastScannedCode = '';
        let lastScanTime = 0;

        function startScanner() {
            document.getElementById('camera-placeholder').style.display = 'none';
            document.getElementById('qr-reader').style.display = 'block';
            document.getElementById('btn-start').disabled = true;
            document.getElementById('btn-stop').disabled = false;

            html5QrCode = new Html5Qrcode("qr-reader");

            html5QrCode.start(
                { facingMode: "environment" },
                {
                    fps: 10,
                    qrbox: { width: 250, height: 250 },
                    aspectRatio: 1.0,
                },
                onScanSuccess,
                onScanFailure
            ).catch(err => {
                console.error("Camera error:", err);
                alert("Could not access camera. Please ensure camera permission is granted and you're on HTTPS.");
                resetButtons();
            });

            isScanning = true;
        }

        function stopScanner() {
            if (html5QrCode && isScanning) {
                html5QrCode.stop().then(() => {
                    html5QrCode.clear();
                    resetButtons();
                }).catch(err => {
                    console.error("Stop error:", err);
                    resetButtons();
                });
            }
            isScanning = false;
        }

        function resetButtons() {
            document.getElementById('btn-start').disabled = false;
            document.getElementById('btn-stop').disabled = true;
            document.getElementById('qr-reader').style.display = 'none';
            document.getElementById('camera-placeholder').style.display = 'flex';
        }

        function onScanSuccess(decodedText) {
            // Debounce: ignore same code scanned within 3 seconds
            const now = Date.now();
            if (decodedText === lastScannedCode && (now - lastScanTime) < 3000) {
                return;
            }
            lastScannedCode = decodedText;
            lastScanTime = now;

            // Extract UUID from the verify URL
            const match = decodedText.match(/\/ticket\/verify\/([a-f0-9\-]{36})/i);
            if (!match) {
                showResult('error', '🚫', 'INVALID QR CODE', 'This QR code is not a valid ticket.', {});
                return;
            }

            const uuid = match[1];
            verifyTicket(uuid, decodedText);
        }

        function onScanFailure(error) {
            // Ignore — this fires on every frame that doesn't have a QR code
        }

        async function verifyTicket(uuid, fullUrl) {
            const resultDiv = document.getElementById('scan-result');
            resultDiv.innerHTML = '<div style="text-align:center;padding:20px;color:#64748b;">⏳ Verifying...</div>';

            try {
                // Use the full scanned URL but make it an AJAX request
                const verifyUrl = fullUrl.includes('/ticket/verify/')
                    ? fullUrl
                    : `/ticket/verify/${uuid}`;

                const response = await fetch(verifyUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();
                const t = data.ticket || {};
                const scanCount = data.scan_count || 0;

                let icon, headline, sub, cardClass;

                switch (data.status) {
                    case 'ok':
                        icon = '✅';
                        headline = 'VALID — Welcome!';
                        sub = 'Guest checked in successfully.';
                        cardClass = 'result-ok';
                        break;
                    case 'already':
                        icon = '⚠️';
                        headline = 'ALREADY CHECKED IN';
                        sub = 'This ticket has already been used. Do not admit.';
                        cardClass = 'result-already';
                        break;
                    case 'unpaid':
                        icon = '❌';
                        headline = 'INVALID — Not Paid';
                        sub = 'This ticket has not been paid. Entry denied.';
                        cardClass = 'result-unpaid';
                        break;
                    case 'error':
                        icon = '🚨';
                        headline = 'SYSTEM ERROR';
                        sub = data.message || 'An error occurred on the server.';
                        cardClass = 'result-error';
                        break;
                    default:
                        icon = '🚫';
                        headline = 'UNKNOWN STATUS';
                        sub = data.message || 'Could not verify this ticket.';
                        cardClass = 'result-error';
                }

                let scanBadgeClass = scanCount <= 1 ? 'scan-low' : (scanCount <= 2 ? 'scan-warn' : 'scan-danger');

                let html = `
                    <div class="result-card ${cardClass}">
                        <div class="result-icon">${icon}</div>
                        <div class="result-headline">${headline}</div>
                        <div class="result-sub">${sub}</div>
                        <div class="result-details">
                            <div><strong>Name</strong> ${t.name || 'N/A'}</div>
                            <div><strong>Type</strong> ${t.ticket_type || 'N/A'}</div>
                            <div><strong>Qty</strong> ${t.quantity || 'N/A'}</div>
                            <div><strong>Ref</strong> ${t.order_ref || 'N/A'}</div>
                            ${t.checked_in_at ? `<div><strong>Checked in</strong> ${t.checked_in_at}</div>` : ''}
                        </div>
                        <div class="scan-count-badge ${scanBadgeClass}">
                            🔍 Scanned ${scanCount} ${scanCount === 1 ? 'time' : 'times'}
                        </div>
                        ${scanCount > 2 ? `
                        <div class="fraud-alert">
                            🚨 FRAUD WARNING: This QR code has been scanned ${scanCount} times!
                            Possible ticket sharing detected.
                        </div>
                        ` : ''}
                    </div>
                `;

                resultDiv.innerHTML = html;

                // Add to scan log
                addToLog(icon, t.name || 'Unknown', data.status, scanCount);

            } catch (err) {
                console.error('Verify error:', err);
                showResult('error', '🚫', 'VERIFICATION FAILED', 'Could not reach the server. Check your connection.', {});
            }
        }

        function showResult(status, icon, headline, sub, ticket) {
            const cardClass = `result-${status}`;
            document.getElementById('scan-result').innerHTML = `
                <div class="result-card ${cardClass}">
                    <div class="result-icon">${icon}</div>
                    <div class="result-headline">${headline}</div>
                    <div class="result-sub">${sub}</div>
                </div>
            `;
        }

        function addToLog(icon, name, status, scanCount) {
            const now = new Date();
            const time = now.toLocaleTimeString('en-NG', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

            scanLog.unshift({ icon, name, status, scanCount, time });
            if (scanLog.length > 20) scanLog.pop();

            const logList = document.getElementById('scan-log-list');
            logList.innerHTML = scanLog.map(item => `
                <div class="scan-log-item">
                    <span class="log-status">${item.icon}</span>
                    <span class="log-name">${item.name}</span>
                    <span style="color:${item.scanCount > 2 ? '#ef4444' : '#64748b'};font-weight:${item.scanCount > 2 ? '700' : '400'}">
                        (${item.scanCount}x scanned)
                    </span>
                    <span class="log-time">${item.time}</span>
                </div>
            `).join('');
        }
    </script>
</x-filament-panels::page>
