@extends('layouts.master')

@section('title', 'Sync All Devices')

@section('content')

    <div style="max-width:900px; margin:0 auto;">

        {{-- Header --}}
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem;">
            <div>
                <h2 style="margin:0; font-size:1.6rem; font-weight:700;">
                    <i class="ri-refresh-line" style="color:var(--primary);"></i> Sync All Devices
                </h2>
                <p style="margin:4px 0 0; color:var(--text-muted); font-size:0.9rem;">
                    Ping all devices, then sync attendance in parallel
                </p>
            </div>
            <a href="{{ route('dashboard') }}" class="btn btn-secondary"
                style="display:inline-flex; align-items:center; gap:6px;">
                <i class="ri-arrow-left-line"></i> Dashboard
            </a>
        </div>

        {{-- Date Range + Controls --}}
        <div class="card" style="margin-bottom:1.5rem;">
            <div style="display:flex; gap:1rem; align-items:flex-end; flex-wrap:wrap;">
                <div>
                    <label style="display:block; font-size:0.8rem; color:var(--text-muted); margin-bottom:4px;">Start
                        Date</label>
                    <input type="date" id="start_date" class="form-control"
                        value="{{ date('Y-m-d', strtotime('-1 month')) }}" style="margin:0; padding:0.5rem; width:160px;">
                </div>
                <div>
                    <label style="display:block; font-size:0.8rem; color:var(--text-muted); margin-bottom:4px;">End
                        Date</label>
                    <input type="date" id="end_date" class="form-control" value="{{ date('Y-m-d') }}"
                        style="margin:0; padding:0.5rem; width:160px;">
                </div>
                <div style="flex:1;"></div>
                <button id="btn-ping" onclick="startPing()" class="btn btn-secondary"
                    style="display:inline-flex; align-items:center; gap:6px; height:38px;">
                    <i class="ri-wifi-line"></i> 1. Ping All
                </button>
                <button id="btn-sync" onclick="startSyncAll()" class="btn btn-primary" disabled
                    style="display:inline-flex; align-items:center; gap:6px; height:38px; opacity:0.5;">
                    <i class="ri-refresh-line"></i> 2. Sync All Online
                </button>
            </div>

            {{-- Overall Status Bar --}}
            <div id="overall-status"
                style="display:none; margin-top:1.2rem; padding:0.8rem 1rem; border-radius:8px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                    <span id="overall-label" style="font-size:0.9rem; font-weight:600;">Pinging...</span>
                    <span id="overall-count" style="font-size:0.85rem; color:var(--text-muted);"></span>
                </div>
                <div style="background:rgba(0,0,0,0.2); border-radius:4px; height:6px; overflow:hidden;">
                    <div id="overall-bar" style="width:0%; height:100%; background:var(--primary); transition:width 0.4s;">
                    </div>
                </div>
            </div>
        </div>

        {{-- Device Cards --}}
        <div id="device-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(260px, 1fr)); gap:1rem;">
            @foreach($devices as $device)
                <div class="device-card card" id="card-{{ $device->id }}" data-id="{{ $device->id }}"
                    data-name="{{ $device->name }}" data-ip="{{ $device->ip }}"
                    style="position:relative; overflow:hidden; transition:all 0.3s;">

                    {{-- Status Glow Strip --}}
                    <div class="card-strip" id="strip-{{ $device->id }}"
                        style="position:absolute; top:0; left:0; right:0; height:3px; background:rgba(255,255,255,0.1); transition:background 0.4s;">
                    </div>

                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:0.8rem;">
                        <div>
                            <div style="font-weight:700; font-size:1rem;">{{ $device->name }}</div>
                            <div style="font-size:0.8rem; color:var(--text-muted);">{{ $device->ip }}:{{ $device->port }}</div>
                        </div>
                        <div id="ping-badge-{{ $device->id }}"
                            style="font-size:0.7rem; padding:3px 8px; border-radius:20px; background:rgba(255,255,255,0.08); color:var(--text-muted); white-space:nowrap;">
                            Waiting...
                        </div>
                    </div>

                    {{-- Progress Bar --}}
                    <div
                        style="background:rgba(0,0,0,0.2); border-radius:4px; height:5px; overflow:hidden; margin-bottom:0.5rem;">
                        <div id="bar-{{ $device->id }}"
                            style="width:0%; height:100%; background:var(--primary); transition:width 0.4s;"></div>
                    </div>

                    {{-- Status Message --}}
                    <div id="msg-{{ $device->id }}"
                        style="font-size:0.78rem; color:var(--text-muted); min-height:1.2em; transition:color 0.3s;">
                        @if($device->last_synced_at)
                            Last sync: {{ $device->last_synced_at->diffForHumans() }}
                        @else
                            Never synced
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

    </div>

@endsection

@push('styles')
    <style>
        .device-card {
            cursor: default;
        }

        .device-card.online {
            border-color: rgba(16, 185, 129, 0.4);
        }

        .device-card.offline {
            border-color: rgba(239, 68, 68, 0.3);
            opacity: 0.7;
        }

        .device-card.syncing {
            border-color: rgba(99, 102, 241, 0.5);
        }

        .device-card.done {
            border-color: rgba(16, 185, 129, 0.6);
        }

        .device-card.failed {
            border-color: rgba(239, 68, 68, 0.5);
        }

        #btn-sync:not([disabled]) {
            opacity: 1 !important;
        }

        @keyframes pulse-glow {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }

        .pulsing {
            animation: pulse-glow 1.2s infinite;
        }
    </style>
@endpush

@push('scripts')
    <script>
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;
        let onlineDeviceIds = [];
        let pollIntervals = {};

        // -------------------------------------------------------------------------
        // STEP 1: PING ALL
        // -------------------------------------------------------------------------
        function startPing() {
            const btn = document.getElementById('btn-ping');
            btn.disabled = true;
            btn.innerHTML = '<span class="loading-spinner" style="width:14px;height:14px;"></span> Pinging...';

            // Reset all cards to "pinging" state
            document.querySelectorAll('.device-card').forEach(card => {
                const id = card.dataset.id;
                setCardState(id, 'pinging', '⏳ Pinging...', 0, 'rgba(255,255,255,0.1)');
                document.getElementById(`ping-badge-${id}`).style.background = 'rgba(255,255,255,0.08)';
                document.getElementById(`ping-badge-${id}`).style.color = 'var(--text-muted)';
                document.getElementById(`ping-badge-${id}`).innerText = 'Pinging...';
            });

            showOverall('Pinging all devices...', '');

            fetch('{{ route("devices.ping-all") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' }
            })
                .then(r => r.json())
                .then(data => {
                    onlineDeviceIds = [];
                    const results = data.results;
                    let onlineCount = 0;
                    let total = Object.keys(results).length;

                    Object.entries(results).forEach(([id, result]) => {
                        const card = document.getElementById(`card-${id}`);
                        const badge = document.getElementById(`ping-badge-${id}`);

                        if (result.online) {
                            onlineCount++;
                            onlineDeviceIds.push(parseInt(id));
                            card.classList.add('online');
                            card.classList.remove('offline');
                            badge.innerText = '🟢 Online';
                            badge.style.background = 'rgba(16,185,129,0.2)';
                            badge.style.color = '#10b981';
                            document.getElementById(`strip-${id}`).style.background = '#10b981';
                            setMsg(id, 'Ready to sync', '#10b981');
                        } else {
                            card.classList.add('offline');
                            card.classList.remove('online');
                            badge.innerText = '🔴 Offline';
                            badge.style.background = 'rgba(239,68,68,0.2)';
                            badge.style.color = '#ef4444';
                            document.getElementById(`strip-${id}`).style.background = '#ef4444';
                            setMsg(id, result.message, '#ef4444');
                        }
                    });

                    showOverall(`Ping complete: ${onlineCount}/${total} devices online`, `${onlineCount} online`, onlineCount / total * 100);

                    btn.disabled = false;
                    btn.innerHTML = '<i class="ri-wifi-line"></i> Re-Ping All';

                    // Enable sync button if at least one device is online
                    const syncBtn = document.getElementById('btn-sync');
                    if (onlineCount > 0) {
                        syncBtn.disabled = false;
                        syncBtn.style.opacity = '1';
                    }
                })
                .catch(e => {
                    console.error(e);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="ri-wifi-line"></i> 1. Ping All';
                    showOverall('Ping failed — network error', '', 0);
                });
        }

        // -------------------------------------------------------------------------
        // STEP 2: DISPATCH ALL SYNC JOBS IN PARALLEL
        // -------------------------------------------------------------------------
        function startSyncAll() {
            if (onlineDeviceIds.length === 0) {
                alert('No online devices to sync. Please ping first.');
                return;
            }

            const btn = document.getElementById('btn-sync');
            btn.disabled = true;
            btn.innerHTML = '<span class="loading-spinner" style="width:14px;height:14px;"></span> Dispatching...';

            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;

            // Set all online cards to "queued"
            onlineDeviceIds.forEach(id => {
                setCardState(id, 'syncing', '⏳ Queued...', 5, '#6366f1');
                document.getElementById(`strip-${id}`).style.background = '#6366f1';
                document.getElementById(`strip-${id}`).classList.add('pulsing');
            });

            showOverall('Dispatching sync jobs...', '');

            fetch('{{ route("devices.dispatch-all") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    device_ids: onlineDeviceIds,
                    start_date: startDate,
                    end_date: endDate
                })
            })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        alert('Dispatch failed: ' + data.message);
                        btn.disabled = false;
                        btn.innerHTML = '<i class="ri-refresh-line"></i> 2. Sync All Online';
                        return;
                    }

                    btn.innerHTML = '<i class="ri-loader-4-line"></i> Syncing...';
                    showOverall('Syncing in parallel...', `0/${onlineDeviceIds.length} done`);

                    // Start polling ALL devices simultaneously
                    let completedCount = 0;
                    onlineDeviceIds.forEach(id => startPolling(id, () => {
                        completedCount++;
                        showOverall(
                            completedCount === onlineDeviceIds.length ? '✅ All syncs complete!' : 'Syncing in parallel...',
                            `${completedCount}/${onlineDeviceIds.length} done`,
                            (completedCount / onlineDeviceIds.length) * 100
                        );

                        if (completedCount === onlineDeviceIds.length) {
                            btn.disabled = false;
                            btn.innerHTML = '<i class="ri-refresh-line"></i> Sync Again';
                        }
                    }));
                })
                .catch(e => {
                    console.error(e);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="ri-refresh-line"></i> 2. Sync All Online';
                });
        }

        // -------------------------------------------------------------------------
        // POLLING: Check progress for a single device
        // -------------------------------------------------------------------------
        function startPolling(deviceId, onComplete) {
            // Clear any existing interval for this device
            if (pollIntervals[deviceId]) clearInterval(pollIntervals[deviceId]);

            pollIntervals[deviceId] = setInterval(() => {
                fetch(`/devices/${deviceId}/sync-progress`)
                    .then(r => r.json())
                    .then(data => {
                        const pct = data.progress || 0;
                        const msg = data.message || '';

                        setBar(deviceId, pct);
                        setMsg(deviceId, msg);

                        if (data.status === 'completed') {
                            clearInterval(pollIntervals[deviceId]);
                            setBar(deviceId, 100);
                            document.getElementById(`strip-${deviceId}`).style.background = '#10b981';
                            document.getElementById(`strip-${deviceId}`).classList.remove('pulsing');
                            document.getElementById(`card-${deviceId}`).classList.remove('syncing');
                            document.getElementById(`card-${deviceId}`).classList.add('done');
                            setMsg(deviceId, '✅ ' + msg, '#10b981');
                            onComplete();

                        } else if (data.status === 'failed') {
                            clearInterval(pollIntervals[deviceId]);
                            document.getElementById(`strip-${deviceId}`).style.background = '#ef4444';
                            document.getElementById(`strip-${deviceId}`).classList.remove('pulsing');
                            document.getElementById(`card-${deviceId}`).classList.remove('syncing');
                            document.getElementById(`card-${deviceId}`).classList.add('failed');
                            setMsg(deviceId, '❌ ' + msg, '#ef4444');
                            onComplete();
                        }
                    })
                    .catch(() => { }); // ignore transient network errors
            }, 2000); // poll every 2 seconds
        }

        // -------------------------------------------------------------------------
        // HELPERS
        // -------------------------------------------------------------------------
        function setBar(id, pct) {
            const bar = document.getElementById(`bar-${id}`);
            if (bar) bar.style.width = pct + '%';
        }

        function setMsg(id, msg, color) {
            const el = document.getElementById(`msg-${id}`);
            if (el) {
                el.innerText = msg;
                if (color) el.style.color = color;
            }
        }

        function setCardState(id, cls, msg, pct, barColor) {
            const card = document.getElementById(`card-${id}`);
            if (card) {
                card.className = 'device-card card ' + cls;
            }
            setMsg(id, msg);
            setBar(id, pct);
            const bar = document.getElementById(`bar-${id}`);
            if (bar && barColor) bar.style.background = barColor;
        }

        function showOverall(label, count, pct) {
            const el = document.getElementById('overall-status');
            el.style.display = 'block';
            document.getElementById('overall-label').innerText = label;
            document.getElementById('overall-count').innerText = count || '';
            if (pct !== undefined) {
                document.getElementById('overall-bar').style.width = pct + '%';
            }
        }
    </script>
@endpush