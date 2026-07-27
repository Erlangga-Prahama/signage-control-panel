<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $device->nama }} — Signage Player</title>
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/laravel-echo/1.17.1/echo.iife.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; background: #0c4a6e; overflow: hidden; font-family: -apple-system, 'Inter', sans-serif; }
        #stage { position: relative; width: 100vw; height: 100vh; }
        .slide { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
                 opacity: 0; transition: opacity 0.6s ease; background: #0c4a6e; }
        .slide.active { opacity: 1; }
        .slide img, .slide video { width: 100%; height: 100%; object-fit: contain; }
        .slide iframe { width: 100%; height: 100%; border: 0; }
        .slide .text-slide { color: #fff; font-size: 4vw; text-align: center; padding: 6vw; line-height: 1.4; }
        #empty { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #9ec3db; gap: 10px; }
        #empty .dot { width: 10px; height: 10px; border-radius: 50%; background: #3DDC84; }
        #hud { position: fixed; bottom: 14px; right: 16px; font-family: 'JetBrains Mono', monospace; font-size: 11px;
               color: #9ec3db; display: flex; align-items: center; gap: 8px; z-index: 10; }
        #hud .dot { width: 7px; height: 7px; border-radius: 50%; background: #E5484D; }
        #hud.online .dot { background: #3DDC84; }
        #overlay { position: fixed; inset: 0; background: rgba(6,32,49,0.92); display: none; align-items: center;
                   justify-content: center; flex-direction: column; gap: 14px; color: #E8EDF2; z-index: 20;
                   font-family: 'JetBrains Mono', monospace; text-align: center; }
        #overlay.show { display: flex; }
        #overlay .spin { width: 34px; height: 34px; border: 3px solid #155e83; border-top-color: #14b8a6;
                          border-radius: 50%; animation: spin 0.9s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        #badge { position: fixed; top: 14px; left: 16px; font-family: 'JetBrains Mono', monospace; font-size: 11px;
                  color: #9ec3db; z-index: 10; }
    </style>
</head>
<body>
    <div id="stage"></div>
    <div id="empty" style="display:none">
        <div class="dot"></div>
        <p>Menunggu konten untuk "{{ $device->nama }}"…</p>
    </div>
    <div id="badge">{{ $device->nama }} · {{ $device->lokasi ?? 'no location' }}</div>
    <div id="hud"><span class="dot"></span><span id="hud-text">connecting…</span></div>
    <div id="overlay">
        <div class="spin"></div>
        <p>Menghubungkan ulang ke server…</p>
    </div>

    <script>
    (function () {
        const DEVICE_KEY = @json($device->device_key);
        const HEARTBEAT_MS = 5000;

        const stage = document.getElementById('stage');
        const empty = document.getElementById('empty');
        const hud = document.getElementById('hud');
        const hudText = document.getElementById('hud-text');
        const overlay = document.getElementById('overlay');

        let playlistItems = [];
        let currentIndex = 0;
        let rotateTimer = null;
        let overrideContent = null; // pushed content overriding the playlist
        let echo = null;
        let heartbeatFailStreak = 0;

        function setConnected(ok) {
            hud.classList.toggle('online', ok);
            hudText.textContent = ok ? 'live' : 'reconnecting…';
            overlay.classList.toggle('show', !ok && heartbeatFailStreak >= 2);
        }

        function renderSlide(content) {
            stage.innerHTML = '';
            empty.style.display = 'none';

            const el = document.createElement('div');
            el.className = 'slide active';

            if (content.tipe === 'image') {
                el.innerHTML = `<img src="${content.url}">`;
            } else if (content.tipe === 'video') {
                el.innerHTML = `<video src="${content.url}" autoplay muted playsinline></video>`;
            } else if (content.tipe === 'url') {
                el.innerHTML = `<iframe src="${content.url}"></iframe>`;
            } else {
                const p = document.createElement('p');
                p.className = 'text-slide';
                p.textContent = content.payload || '';
                el.appendChild(p);
            }

            stage.appendChild(el);
        }

        function showEmpty() {
            stage.innerHTML = '';
            empty.style.display = 'flex';
        }

        function playCurrent() {
            clearTimeout(rotateTimer);

            if (overrideContent) {
                renderSlide(overrideContent);
                return; // override holds the screen until cleared
            }

            if (playlistItems.length === 0) {
                showEmpty();
                return;
            }

            const item = playlistItems[currentIndex % playlistItems.length];
            renderSlide(item.content);

            rotateTimer = setTimeout(() => {
                currentIndex = (currentIndex + 1) % playlistItems.length;
                playCurrent();
            }, (item.durasi_detik || 10) * 1000);
        }

        async function fetchPlayer() {
            const res = await fetch('/api/device/player', { headers: { 'X-Device-Key': DEVICE_KEY, 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('player fetch failed: ' + res.status);
            const data = await res.json();

            playlistItems = data.playlist ? data.playlist.items : [];
            currentIndex = 0;
            playCurrent();
            return data;
        }

        async function heartbeat() {
            try {
                const res = await fetch('/api/device/heartbeat', { method: 'POST', headers: { 'X-Device-Key': DEVICE_KEY, 'Accept': 'application/json' } });
                if (!res.ok) throw new Error('heartbeat failed: ' + res.status);
                heartbeatFailStreak = 0;
                setConnected(true);
            } catch (e) {
                heartbeatFailStreak++;
                setConnected(false);
            }
        }

        function connectRealtime(reverb, deviceId) {
            try {
                echo = new Echo({
                    broadcaster: 'reverb',
                    key: reverb.key,
                    wsHost: reverb.host,
                    wsPort: reverb.port,
                    wssPort: reverb.port,
                    forceTLS: reverb.scheme === 'https',
                    enabledTransports: ['ws', 'wss'],
                });

                echo.connector.pusher.connection.bind('connected', () => setConnected(true));
                echo.connector.pusher.connection.bind('disconnected', () => setConnected(false));
                echo.connector.pusher.connection.bind('unavailable', () => setConnected(false));

                echo.channel('device.' + deviceId).listen('.command.dispatched', handleCommand);
            } catch (e) {
                console.error('Realtime connection failed, falling back to heartbeat-only mode', e);
            }
        }

        async function handleCommand(payload) {
            if (payload.command === 'push_content' && payload.content) {
                overrideContent = payload.content;
                playCurrent();
            } else if (payload.command === 'clear_override') {
                overrideContent = null;
                playCurrent();
            } else if (payload.command === 'refresh') {
                window.location.reload();
                return;
            } else if (payload.command === 'reboot') {
                // Electron client intercepts this to actually relaunch; in-browser we just reload.
                window.location.reload();
                return;
            }

            try {
                await fetch(`/api/device/commands/${payload.id}/ack`, {
                    method: 'POST',
                    headers: { 'X-Device-Key': DEVICE_KEY, 'Accept': 'application/json' },
                });
            } catch (e) { /* will be retried implicitly next command */ }
        }

        async function boot() {
            let data;
            try {
                data = await fetchPlayer();
            } catch (e) {
                showEmpty();
                setConnected(false);
            }

            heartbeat();
            setInterval(heartbeat, HEARTBEAT_MS);

            // Reconnection handling: if heartbeats keep failing, keep retrying
            // the initial player fetch too (covers server restarts / network drops).
            setInterval(async () => {
                if (heartbeatFailStreak >= 2) {
                    try {
                        await fetchPlayer();
                    } catch (e) { /* still down, will retry again next tick */ }
                }
            }, 8000);

            if (data && data.reverb && data.reverb.key) {
                connectRealtime(data.reverb, data.device.id);
            }
        }

        boot();
    })();
    </script>
</body>
</html>
