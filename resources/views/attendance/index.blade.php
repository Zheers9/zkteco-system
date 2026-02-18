@extends('layouts.master')

@section('title', 'All Attendance Logs')

@section('content')

    <div class="card">
        <div class="header"
            style="margin-bottom:1.5rem; justify-content:space-between; display:flex; align-items:flex-end;">
            <h3 style="margin:0;">{{ __('messages.global_history') }}</h3>

            <div style="display:flex; gap:10px; align-items:flex-end;">
                <form action="{{ route('attendance.index') }}" method="GET"
                    style="display:flex; gap:10px; align-items:flex-end;">
                    <div>
                        <label
                            style="display:block; font-size:0.8rem; color:var(--text-muted); margin-bottom:4px;">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                            placeholder="Search..." style="margin:0; width:140px; padding:0.5rem;">
                    </div>
                    <div>
                        <label
                            style="display:block; font-size:0.8rem; color:var(--text-muted); margin-bottom:4px;">{{ __('messages.start_date') }}</label>
                        <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control"
                            style="margin:0; width:140px; padding:0.5rem;">
                    </div>
                    <div>
                        <label
                            style="display:block; font-size:0.8rem; color:var(--text-muted); margin-bottom:4px;">{{ __('messages.end_date') }}</label>
                        <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control"
                            style="margin:0; width:140px; padding:0.5rem;">
                    </div>
                    <button type="submit" class="btn btn-primary"
                        style="height:38px; display:inline-flex; align-items:center;">
                        <i class="ri-filter-3-line" style="margin-right:5px;"></i> {{ __('messages.filter') }}
                    </button>
                    @if(request('start_date') || request('end_date'))
                        <a href="{{ route('attendance.index') }}" class="btn btn-danger"
                            style="height:38px; display:inline-flex; align-items:center;">
                            <i class="ri-close-line"></i>
                        </a>
                    @endif
                </form>

                <button onclick="openSyncModal()" class="btn btn-primary"
                    style="height:38px; display:inline-flex; align-items:center; gap:5px;">
                    <i class="ri-refresh-line"></i> {{ __('messages.sync_attendance') }}
                </button>

                <a href="{{ route('attendance.export', request()->query()) }}" class="btn btn-success"
                    style="height:38px; display:inline-flex; align-items:center; gap:5px;">
                    <i class="ri-file-excel-line"></i> {{ __('messages.export') }}
                </a>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('messages.devices') }} ID</th>
                        <th>{{ __('messages.uid') }}</th>
                        <th>{{ __('messages.user_id') }}</th>
                        <th>{{ __('messages.time') }}</th>
                        <th>{{ __('messages.status') }}</th>
                        <th>{{ __('messages.type') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->device_id }}</td>
                            <td>{{ $log->uid }}</td>
                            <td>{{ $log->user_id_on_device }}</td>
                            <td>{{ $log->timestamp }}</td>
                            <td>
                                <span class="badge {{ $log->status == 1 ? 'badge-danger' : 'badge-success' }}">
                                    {{ $log->status }}
                                </span>
                            </td>
                            <td>{{ $log->type }}</td>
                        </tr>
                    @empty
                        <tr>
                        <tr>
                            <td colspan="6" style="text-align:center;">{{ __('messages.no_logs') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:2rem;">
            {{ $logs->links('pagination.custom') }}
        </div>
    </div>

    <div id="syncModal" class="modal-overlay"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; z-index:1000; align-items:center; justify-content:center; backdrop-filter: blur(5px);">
        <div class="glass-panel"
            style="width:400px; max-width:90%; position:relative; background:white; padding:20px; border-radius:8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div
                style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; border-bottom:1px solid #eee; padding-bottom:1rem;">
                <h3 style="margin:0;">{{ __('messages.select_device') }}</h3>
                <button type="button" onclick="closeSyncModal()"
                    style="background:none; border:none; color:var(--text-muted); font-size:1.5rem; cursor:pointer;"><i
                        class="ri-close-line"></i></button>
            </div>

            <div style="margin-bottom:1rem; display:flex; gap:10px;">
                <div style="flex:1;">
                    <label
                        style="display:block; font-size:0.8rem; color:var(--text-muted); margin-bottom:4px;">{{ __('messages.start_date') }}</label>
                    <input type="date" id="sync_start_date" value="{{ date('Y-m-d', strtotime('-1 month')) }}"
                        class="form-control" style="margin:0; padding:0.5rem; width:100%">
                </div>
                <div style="flex:1;">
                    <label
                        style="display:block; font-size:0.8rem; color:var(--text-muted); margin-bottom:4px;">{{ __('messages.end_date') }}</label>
                    <input type="date" id="sync_end_date" value="{{ date('Y-m-d') }}" class="form-control"
                        style="margin:0; padding:0.5rem; width:100%">
                </div>
            </div>

            <div id="sync-status"
                style="display:none; text-align:center; margin-bottom:10px; padding:10px; background:#f0f9ff; border-radius:4px; color:#0c4a6e;">
                <div id="sync-message">Initializing...</div>
                <div class="progress-bar"
                    style="width:100%; height:6px; background:#e0e0e0; border-radius:3px; margin-top:8px; overflow:hidden;">
                    <div id="sync-progress" style="width:0%; height:100%; background:#0ea5e9; transition: width 0.3s;">
                    </div>
                </div>
            </div>

            <div style="display:flex; flex-direction:column; gap:0.8rem; max-height:400px; overflow-y:auto;"
                id="device-list">
                @forelse($devices as $device)
                    <button class="btn btn-secondary device-sync-btn" onclick="syncDevice({{ $device->id }}, this)"
                        style="width:100%; text-align:left; display:flex; justify-content:space-between; align-items:center; padding:1rem;">
                        <div>
                            <div style="font-weight:600;">{{ $device->name }}</div>
                            <div style="font-size:0.8rem; color:var(--text-muted);">{{ $device->ip }}</div>
                        </div>
                        <i class="ri-arrow-right-s-line"></i>
                    </button>
                @empty
                    <p style="text-align:center; color:var(--text-muted);">{{ __('messages.no_devices') }}</p>
                @endforelse
            </div>
        </div>
    </div>

    <script>
        function openSyncModal() {
            document.getElementById('syncModal').style.display = 'flex';
        }

        function closeSyncModal() {
            document.getElementById('syncModal').style.display = 'none';
            // Reset UI if needed
            document.getElementById('sync-status').style.display = 'none';
            document.getElementById('device-list').style.display = 'flex';
        }

        // Close on click outside
        document.getElementById('syncModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeSyncModal();
            }
        });

        function syncDevice(id, btn) {
            const startDate = document.getElementById('sync_start_date').value;
            const endDate = document.getElementById('sync_end_date').value;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            // UI Update
            document.getElementById('device-list').style.display = 'none';
            const statusDiv = document.getElementById('sync-status');
            const msgDiv = document.getElementById('sync-message');
            const bar = document.getElementById('sync-progress');

            statusDiv.style.display = 'block';
            msgDiv.innerText = 'Starting sync request...';
            bar.style.width = '5%';

            fetch(`/devices/${id}/sync-attendance`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    start_date: startDate,
                    end_date: endDate,
                    async: true // Request async processing
                })
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.status === 'queued') {
                        msgDiv.innerText = data.message;
                        // Start Polling
                        pollStatus(id);
                    } else {
                        alert('Error: ' + (data.message || 'Unknown error'));
                        resetModal();
                    }
                })
                .catch(e => {
                    console.error(e);
                    alert("{{ __('messages.error') }}");
                    resetModal();
                });
        }

        function pollStatus(deviceId) {
            const msgDiv = document.getElementById('sync-message');
            const bar = document.getElementById('sync-progress');

            const interval = setInterval(() => {
                fetch(`/devices/${deviceId}/sync-progress`)
                    .then(r => r.json())
                    .then(data => {
                        // Update UI
                        if (data.message) msgDiv.innerText = data.message;
                        if (data.progress) bar.style.width = data.progress + '%';

                        if (data.status === 'completed') {
                            clearInterval(interval);
                            bar.style.background = '#10b981'; // Green
                            setTimeout(() => {
                                alert("Sync Completed Successfully! " + (data.new || 0) + " new records.");
                                window.location.reload();
                            }, 500);
                        } else if (data.status === 'failed') {
                            clearInterval(interval);
                            bar.style.background = '#ef4444'; // Red
                            alert("Sync Failed: " + data.message);
                            resetModal();
                        }
                    })
                    .catch(e => {
                        console.error("Polling error", e);
                        // Don't stop immediately on network glitch, maybe retry?
                    });
            }, 2000); // Poll every 2 seconds
        }

        function resetModal() {
            document.getElementById('sync-status').style.display = 'none';
            document.getElementById('device-list').style.display = 'flex';
            document.getElementById('sync-progress').style.width = '0%';
            document.getElementById('sync-progress').style.background = '#0ea5e9';
        }
    </script>

@endsection