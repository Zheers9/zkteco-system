@extends('layouts.master')

@section('title', 'Device Users')

@section('content')

    <div style="margin-bottom: 2rem;">
        <a href="{{ route('devices.show', $device->id) }}" class="btn btn-secondary"
            style="margin-bottom:1rem; display:inline-flex; align-items:center;">
            <i class="ri-arrow-left-line"></i> Back to Device
        </a>

        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h2 style="font-size:1.5rem; font-weight:600;">{{ $device->name }} - Users</h2>
            <button class="btn btn-primary" onclick="syncData('users', this)">
                <i class="ri-download-cloud-line"></i> Sync Users
            </button>
        </div>
    </div>

    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID (Device)</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Card No.</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $user->user_id_on_device }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->role }}</td>
                            <td>{{ $user->card_number }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align:center;">No users synced yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:1rem;">
            {{ $users->links('pagination.custom') }}
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        function syncData(type, btn) {
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="loading-spinner" style="width:15px; height:15px;"></span> Syncing...';
            btn.disabled = true;

            fetch(`/devices/{{ $device->id }}/sync-${type}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showToast('Error: ' + data.message, 'error');
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