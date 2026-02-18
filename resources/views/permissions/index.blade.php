@extends('layouts.master')

@section('title', 'Permissions / Leaves')

@section('content')

    <div class="row" style="gap:2rem;">
        <!-- Grant Permission Form -->
        <div class="col-md-4">
            <div class="card">
                <h3>Grant Permission</h3>
                <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1.5rem;">
                    Mark a user as excused for a specific date. They will be counted as "Permission" instead of "Absent".
                </p>

                <form action="{{ route('permissions.store') }}" method="POST">
                    @csrf

                    <div class="form-group mb-3">
                        <label>Select User</label>
                        <select name="user_id_on_device" class="form-control" required style="width:100%;">
                            <option value="">-- Choose User --</option>
                            @foreach($users as $id => $name)
                                <option value="{{ $id }}">{{ $name }} (ID: {{ $id }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label>Date</label>
                        <input type="date" name="date" class="form-control" required value="{{ date('Y-m-d') }}">
                    </div>

                    <div class="form-group mb-3">
                        <label>Reason (Optional)</label>
                        <textarea name="reason" class="form-control" rows="3"
                            placeholder="e.g. Sick Leave, Annual Leave"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ri-add-circle-line"></i> Grant Permission
                    </button>
                </form>
            </div>
        </div>

        <!-- Permissions List -->
        <div class="col-md-7" style="flex:1;">
            <div class="card">
                <div class="header" style="justify-content:space-between; display:flex; margin-bottom:1rem;">
                    <h3>Recent Permissions</h3>
                </div>

                <div class="table-container">
                    <table class="table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>User</th>
                                <th>Reason</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($permissions as $perm)
                                <tr>
                                    <td>{{ $perm->date->format('Y-m-d') }}</td>
                                    <td>
                                        <div style="font-weight:600;">
                                            {{ $perm->user->name ?? 'Unknown (' . $perm->user_id_on_device . ')' }}</div>
                                    </td>
                                    <td>{{ $perm->reason ?? '-' }}</td>
                                    <td>
                                        <form action="{{ route('permissions.destroy', $perm->id) }}" method="POST"
                                            onsubmit="return confirm('Are you sure?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" style="text-align:center; padding:2rem; color:var(--text-muted);">
                                        No permissions records found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div style="margin-top:1rem;">
                        {{ $permissions->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
    <style>
        .form-control {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            border-radius: 8px;
            padding: 0.75rem;
        }

        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            background: rgba(255, 255, 255, 0.1);
        }

        option {
            background: #1e293b;
            color: white;
        }
    </style>
@endpush