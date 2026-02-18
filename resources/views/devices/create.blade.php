@extends('layouts.master')

@section('title', 'Add New Device')

@section('content')

    <div style="max-width: 600px;">
        <div class="card">
            <form action="{{ route('devices.store') }}" method="POST">
                @csrf

                <div style="margin-bottom: 1.5rem;">
                    <label style="display:block; margin-bottom:0.5rem; color:var(--text-muted);">Device Name</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Main Entrance" required>
                </div>

                <div style="display:grid; grid-template-columns: 2fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                    <div>
                        <label style="display:block; margin-bottom:0.5rem; color:var(--text-muted);">IP Address</label>
                        <input type="text" name="ip" class="form-control" placeholder="192.168.1.201" required>
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:0.5rem; color:var(--text-muted);">Port</label>
                        <input type="number" name="port" class="form-control" value="4370" required>
                    </div>
                </div>

                <div style="margin-bottom: 2rem;">
                    <label style="display:block; margin-bottom:0.5rem; color:var(--text-muted);">Location</label>
                    <input type="text" name="location" class="form-control" placeholder="Building A, Floor 1">
                </div>

                <div style="display:flex; justify-content: space-between; align-items:center;">
                    <a href="{{ route('devices.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Device</button>
                </div>
            </form>
        </div>
    </div>

    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                @foreach ($errors->all() as $error)
                    showToast("{{ $error }}", 'error');
                @endforeach
                });
        </script>
    @endif

@endsection