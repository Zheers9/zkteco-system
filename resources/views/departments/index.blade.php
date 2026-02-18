@extends('layouts.master')

@section('title', 'Departments')

@section('content')

    <div class="row">
        <!-- Add Department Form -->
        <div class="col-md-4">
            <div class="card">
                <h3>Create Department</h3>
                <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1.5rem;">
                    Add a new department to organize your users.
                </p>

                <form action="{{ route('departments.store') }}" method="POST">
                    @csrf
                    <div class="form-group mb-3">
                        <label>Department Name</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Sales, HR">
                    </div>

                    <div class="form-group mb-3">
                        <label>Description (Optional)</label>
                        <textarea name="description" class="form-control" rows="3"
                            placeholder="Short description..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ri-add-circle-line"></i> Create Department
                    </button>
                </form>
            </div>
        </div>

        <!-- Departments List -->
        <div class="col-md-8">
            <div class="card">
                <div class="header" style="justify-content:space-between; display:flex; margin-bottom:1rem;">
                    <h3>All Departments</h3>
                </div>

                <div class="table-container">
                    <table class="table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Users</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($departments as $dep)
                                <tr>
                                    <td style="font-weight:600;">{{ $dep->name }}</td>
                                    <td>{{ $dep->description ?? '-' }}</td>
                                    <td>
                                        <span class="badge badge-info">{{ $dep->users_count }} members</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('departments.show', $dep->id) }}" class="btn btn-sm btn-primary">
                                            <i class="ri-settings-4-line"></i> Manage Members
                                        </a>
                                        <form action="{{ route('departments.destroy', $dep->id) }}" method="POST"
                                            style="display:inline-block;" onsubmit="return confirm('Are you sure?');">
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
                                        No departments found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
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
    </style>
@endpush