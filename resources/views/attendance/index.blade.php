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

    <!-- Sync Modal -->
    <div id="syncModal" class="modal-overlay"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center; backdrop-filter: blur(5px);">
        <div class="glass-panel" style="width:400px; max-width:90%; position:relative;">
            <div
                style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:1rem;">
                <h3 style="margin:0;">{{ __('messages.select_device') }}</h3>
                <button onclick="closeSyncModal()"
                    style="background:none; border:none; color:var(--text-muted); font-size:1.5rem; cursor:pointer;"><i
                        class="ri-close-line"></i></button>
            </div>

            <div style="margin-bottom:1rem; display:flex; gap:10px;">
                <div style="flex:1;">
                    <label
                        style="display:block; font-size:0.8rem; color:var(--text-muted); margin-bottom:4px;">{{ __('messages.start_date') }}</label>
                    <input type="date" id="sync_start_date" value="{{ date('Y-m-d', strtotime('-1 month')) }}"
                        class="form-control" style="margin:0; padding:0.5rem;">
                </div>
                <div style="flex:1;">
                    <label
                        style="display:block; font-size:0.8rem; color:var(--text-muted); margin-bottom:4px;">{{ __('messages.end_date') }}</label>
                    <input type="date" id="sync_end_date" value="{{ date('Y-m-d') }}" class="form-control"
                        style="margin:0; padding:0.5rem;">
                </div>
            </div>

            <div style="display:flex; flex-direction:column; gap:0.8rem; max-height:400px; overflow-y:auto;">
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

            const originalContent = btn.innerHTML;
            // Disable all buttons to prevent multiple clicks
            document.querySelectorAll('.device-sync-btn').forEach(b => b.disabled = true);

            btn.innerHTML = `<div style="display:flex; justify-content:center; width:100%;"><span class="loading-spinner"></span></div>`;
            btn.classList.remove('btn-secondary');
            btn.classList.add('btn-primary');

            fetch(`/devices/${id}/sync-attendance`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    start_date: startDate,
                    end_date: endDate
                })
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        // Reload page after short delay to show new data
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showToast(data.message, 'error');
                        // Reset button
                        btn.innerHTML = originalContent;
                        btn.classList.add('btn-secondary');
                        btn.classList.remove('btn-primary');
                        document.querySelectorAll('.device-sync-btn').forEach(b => b.disabled = false);
                    }
                })
                .catch(e => {
                    console.error(e);
                    showToast("{{ __('messages.error') }}", 'error');
                    btn.innerHTML = originalContent;
                    btn.classList.add('btn-secondary');
                    btn.classList.remove('btn-primary');
                    document.querySelectorAll('.device-sync-btn').forEach(b => b.disabled = false);
                });
        }
    </script>

@endsection