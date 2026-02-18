@extends('layouts.master')

@section('title', 'Device Attendance')

@section('content')

    <div style="margin-bottom: 2rem;">
        <a href="{{ route('devices.show', $device->id) }}" class="btn btn-secondary"
            style="margin-bottom:1rem; display:inline-flex; align-items:center;">
            <i class="ri-arrow-left-line"></i> Back to Device
        </a>

        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h2 style="font-size:1.5rem; font-weight:600;">{{ $device->name }} - Attendance Logs</h2>
            <div style="display:flex; gap:0.5rem; align-items:center;">
                <input type="date" id="sync_start" class="form-control" placeholder="Start Date">
                <input type="date" id="sync_end" class="form-control" placeholder="End Date">
                <button class="btn btn-primary" onclick="syncData('attendance', this)">
                    <i class="ri-history-line"></i> Sync Attendance
                </button>
            </div>
        </div>

        <!-- Progress Bar Container -->
        <div id="sync-progress-container" style="display:none; margin-top:1rem;">
            <div style="background:#e9ecef; border-radius:4px; overflow:hidden; height:20px; margin-bottom:5px;">
                <div id="sync-progress-bar"
                    style="width:0%; height:100%; background:var(--primary); transition:width 0.3s;"></div>
            </div>
            <div id="sync-progress-text" style="text-align:center; font-size:0.85rem; color:var(--text-muted);">
                Initializing...</div>
        </div>
    </div>

    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>UID</th>
                        <th>User ID</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Type</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->uid }}</td>
                            <td>{{ $log->user_id_on_device }}</td>
                            <td>{{ $log->timestamp }}</td>
                            <td>{{ $log->status }}</td>
                            <td>{{ $log->type }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align:center;">No logs synced yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:1rem;">
            {{ $logs->links('pagination.custom') }}
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        function syncData(type, btn) {
            const start = document.getElementById('sync_start').value;
            const end = document.getElementById('sync_end').value;

            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="loading-spinner" style="width:15px; height:15px;"></span> request...';
            btn.disabled = true;

            // Show progress container
            const progressContainer = document.getElementById('sync-progress-container');
            const progressBar = document.getElementById('sync-progress-bar');
            const progressText = document.getElementById('sync-progress-text');

            progressContainer.style.display = 'block';
            progressBar.style.width = '0%';
            progressText.innerText = 'Requesting sync...';

            let url = `/devices/{{ $device->id }}/sync-${type}?async=1`;
            if (start) url += `&start_date=${start}`;
            if (end) url += `&end_date=${end}`;

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        btn.innerHTML = '<span class="loading-spinner" style="width:15px; height:15px;"></span> Syncing...';

                        // Start Polling
                        const pollInterval = setInterval(() => {
                            fetch(`/devices/{{ $device->id }}/sync-progress`)
                                .then(r => r.json())
                                .then(progress => {
                                    console.log(progress);

                                    // Update UI
                                    if (progress.status === 'processing' || progress.status === 'fetching') {
                                        progressBar.style.width = progress.progress + '%';
                                        progressText.innerText = progress.message;
                                    } else if (progress.status === 'connecting') {
                                        progressBar.style.width = '5%';
                                        progressText.innerText = progress.message;
                                    } else if (progress.status === 'completed') {
                                        clearInterval(pollInterval);
                                        progressBar.style.width = '100%';
                                        progressBar.style.backgroundColor = 'var(--success)';
                                        progressText.innerText = progress.message;

                                        alert(progress.message); // Show final stats
                                        setTimeout(() => location.reload(), 2000);
                                    } else if (progress.status === 'failed') {
                                        clearInterval(pollInterval);
                                        progressBar.style.backgroundColor = 'var(--danger)';
                                        progressText.innerText = progress.message;
                                        alert('Sync Failed: ' + progress.message);
                                        btn.disabled = false;
                                        btn.innerHTML = originalText;
                                    }
                                })
                                .catch(e => console.error(e));
                        }, 1000);

                    } else {
                        alert('Error: ' + data.message);
                        progressContainer.style.display = 'none';
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    }
                })
                .catch(e => {
                    alert('Network Error');
                    console.error(e);
                    progressContainer.style.display = 'none';
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
        }
    </script>
@endpush