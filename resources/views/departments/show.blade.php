@extends('layouts.master')

@section('title', $department->name . ' - Manage Members')

@section('content')

    <!-- Department Header -->
    <div style="margin-bottom: 2rem;">
        <a href="{{ route('departments.index') }}" class="btn btn-secondary"
            style="margin-bottom:1rem; display:inline-flex; align-items:center;">
            <i class="ri-arrow-left-line"></i> Back to Departments
        </a>

        <div style="display:flex; justify-content:space-between; align-items:flex-end;">
            <div>
                <h2 style="font-size:1.5rem; font-weight:600; color:var(--text-white);">{{ $department->name }}</h2>
                <p style="color:var(--text-muted); margin:0;">
                    Manage members for this department.
                </p>
            </div>

            <button class="btn btn-primary" onclick="openAddModal()">
                <i class="ri-user-add-line"></i> Add Members
            </button>
        </div>
    </div>

    <!-- Members List -->
    <div class="card">
        <div class="header">
            <h3>Current Members ({{ $department->users->count() }})</h3>
        </div>

        <div class="table-container">
            <table class="table-hover">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($department->users as $user)
                        <tr>
                            <td>{{ $user->user_id_on_device }}</td>
                            <td style="font-weight:600;">{{ $user->name }}</td>
                            <td>{{ $user->role }}</td>
                            <td>{{ $user->created_at->format('d M Y') }}</td>
                            <td>
                                <form
                                    action="{{ route('departments.users.remove', ['id' => $department->id, 'userId' => $user->id]) }}"
                                    method="POST" onsubmit="return confirm('Remove user from department?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        Remove
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align:center; padding:2rem; color:var(--text-muted);">
                                No users assigned to this department yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Users Modal -->
    <div id="addUsersModal" class="modal-overlay"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
        <div class="modal-card"
            style="background:#1e293b; padding:2rem; border-radius:12px; max-width:500px; width:90%; position:relative;">
            <button onclick="closeAddModal()"
                style="position:absolute; top:1rem; right:1rem; background:none; border:none; color:white; font-size:1.5rem; cursor:pointer;">&times;</button>

            <h3>Add Users to {{ $department->name }}</h3>
            <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1rem;">
                Select users from the list below to add them to this department.
            </p>

            <form action="{{ route('departments.users.add', $department->id) }}" method="POST">
                @csrf
                <div
                    style="max-height:300px; overflow-y:auto; margin-bottom:1.5rem; border:1px solid rgba(255,255,255,0.1); border-radius:8px; padding:0.5rem;">
                    @forelse($availableUsers as $user)
                        <div class="user-item"
                            style="display:flex; align-items:center; padding:0.5rem; border-bottom:1px solid rgba(255,255,255,0.05);">
                            <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" id="user_{{ $user->id }}"
                                style="margin-right:10px; width:18px; height:18px;">
                            <label for="user_{{ $user->id }}" style="cursor:pointer; flex:1;">
                                <div style="font-weight:600;">{{ $user->name }}</div>
                                <div style="font-size:0.8rem; color:var(--text-muted);">ID: {{ $user->user_id_on_device }}</div>
                            </label>
                        </div>
                    @empty
                        <div style="padding:1rem; text-align:center; color:var(--text-muted);">
                            No available users found (all users might already be assigned).
                        </div>
                    @endforelse
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" {{ $availableUsers->isEmpty() ? 'disabled' : '' }}>Add
                        Selected Users</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        function openAddModal() {
            document.getElementById('addUsersModal').style.display = 'flex';
        }
        function closeAddModal() {
            document.getElementById('addUsersModal').style.display = 'none';
        }

        // Close on click outside
        window.onclick = function (event) {
            if (event.target == document.getElementById('addUsersModal')) {
                closeAddModal();
            }
        }
    </script>
@endpush