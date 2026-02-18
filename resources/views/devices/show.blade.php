@extends('layouts.master')

@section('title', $device->name)

@section('content')

    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom: 2rem;">
        <div>
            <div style="color:var(--text-muted); margin-bottom:0.5rem;">
                <i class="ri-map-pin-line"></i> {{ $device->location }} | {{ $device->ip }}:{{ $device->port }}
            </div>
            <div style="display:flex; gap:0.5rem; align-items:center;">
                Status:
                <span class="badge {{ $device->status ? 'badge-success' : 'badge-danger' }}">
                    {{ $device->status ? 'Online' : 'Offline' }}
                </span>
                <span style="font-size:0.8rem; color:var(--text-muted);">Last connected:
                    {{ $device->last_connected_at ? $device->last_connected_at->diffForHumans() : 'Never' }}</span>
            </div>
        </div>

        <div style="display:flex; gap:1rem;">
            <button class="btn btn-primary" onclick="testConnection(this)">
                <i class="ri-refresh-line"></i> Test Connection
            </button>
            <form action="{{ route('devices.destroy', $device->id) }}" method="POST"
                onsubmit="return confirm('Are you sure?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="ri-delete-bin-line"></i> Delete
                </button>
            </form>
        </div>
    </div>

    <div class="card-grid">
        <!-- Users Card -->
        <a href="{{ route('devices.users', $device->id) }}" style="text-decoration:none;">
            <div class="card" style="height:100%;">
                <div style="font-size:3rem; color:var(--primary); margin-bottom:1rem;">
                    <i class="ri-group-line"></i>
                </div>
                <div class="stat-value">{{ $usersCount }}</div>
                <div class="stat-label">Users</div>
                <div style="margin-top:1rem; color:var(--text-muted); font-size:0.9rem;">
                    Click to manage users and sync from device.
                </div>
            </div>
        </a>

        <!-- Attendance Card -->
        <a href="{{ route('devices.attendance', $device->id) }}" style="text-decoration:none;">
            <div class="card" style="height:100%;">
                <div style="font-size:3rem; color:var(--secondary); margin-bottom:1rem;">
                    <i class="ri-calendar-check-line"></i>
                </div>
                <div class="stat-value">{{ $logsCount }}</div>
                <div class="stat-label">Attendance Records</div>
                <div style="margin-top:1rem; color:var(--text-muted); font-size:0.9rem;">
                    Click to view logs and sync attendance.
                </div>
            </div>
        </a>
    </div>

    <div class="card">
        <h3 style="margin-bottom:1.5rem;">Recent Activity</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentLogs as $log)
                        <tr>
                            <td>{{ $log->user_id_on_device }}</td>
                            <td>{{ $log->timestamp }}</td>
                            <td>{{ $log->status }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align:center;">No recent activity.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        function testConnection(btn) {
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="loading-spinner" style="width:15px; height:15px;"></span> Connecting...';
            btn.disabled = true;

            fetch(`/devices/{{ $device->id }}/connect`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(e => {
                    showToast('Network Error', 'error');
                    console.error(e);
                })
                .finally(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
        }
    </script>
@endpush