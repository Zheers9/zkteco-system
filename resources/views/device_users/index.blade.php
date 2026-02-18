@extends('layouts.master')

@section('title', 'Device Users')

@section('content')
    <div class="content-wrapper">
        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Device Users</h2>
                    <p class="card-subtitle">All users synced from devices</p>
                </div>
                <button class="btn btn-primary" onclick="syncAllUsers()">
                    <i class="ri-refresh-line"></i> Sync Users from Devices
                </button>
            </div>

            <!-- Search Bar -->
            <div style="padding: 0 1.5rem; margin-bottom: 1.5rem;">
                <form action="{{ route('device-users.index') }}" method="GET"
                    style="display:flex; gap:0.5rem; max-width:500px;">
                    <div style="flex:1; position:relative;">
                        <i class="ri-search-line"
                            style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-muted);"></i>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Search by name or user ID..." class="form-control"
                            style="padding-left:2.5rem; width:100%;">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-search-line"></i> Search
                    </button>
                    @if(request('search'))
                        <a href="{{ route('device-users.index') }}" class="btn btn-secondary">
                            <i class="ri-close-line"></i>
                        </a>
                    @endif
                </form>
            </div>

            <div class="table-container" style="margin-top: 1.5rem;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Device</th>
                            <th>User ID on Device</th>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Card Number</th>
                            <th>Synced At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td><span class="badge badge-primary">{{ $user->id }}</span></td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:0.5rem;">
                                        <i class="ri-router-line" style="color:var(--primary)"></i>
                                        <span>{{ $user->device->name }}</span>
                                    </div>
                                </td>
                                <td><strong>{{ $user->user_id_on_device }}</strong></td>
                                <td>{{ $user->name ?? 'N/A' }}</td>
                                <td>
                                    @if($user->role)
                                        <span class="badge badge-{{ $user->role == 'Admin' ? 'danger' : 'info' }}">
                                            {{ $user->role }}
                                        </span>
                                    @else
                                        <span class="badge badge-secondary">User</span>
                                    @endif
                                </td>
                                <td>{{ $user->card_number ?? 'N/A' }}</td>
                                <td>
                                    <small style="color:var(--text-muted)">
                                        {{ $user->updated_at->diffForHumans() }}
                                    </small>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align:center; padding:2rem;">
                                    <i class="ri-user-unfollow-line"
                                        style="font-size:3rem; color:var(--text-muted); opacity:0.5;"></i>
                                    <p style="color:var(--text-muted); margin-top:1rem;">No device users found. Sync users from
                                        devices first.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>


            @if($users->hasPages())
                <div style="margin-top:2rem;">
                    {{ $users->links('pagination.custom') }}
                </div>
            @endif
        </div>
    </div>

    <script>
        function syncAllUsers() {
            if (!confirm('This will sync users from all active devices. Continue?')) {
                return;
            }

            showToast('Syncing users from all devices...', 'success');

            // You can implement a batch sync endpoint or redirect to devices page
            window.location.href = "{{ route('devices.index') }}";
        }
    </script>
@endsection