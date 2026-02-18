@extends('layouts.master')

@section('title', __('messages.devices'))

@section('content')

    <div style="margin-bottom: 2rem; display: flex; justify-content: flex-end; gap: 1rem;">
        <a href="{{ route('devices.transfer.index') }}" class="btn btn-secondary">
            <i class="ri-exchange-line"></i> Transfer Data
        </a>
        <a href="{{ route('devices.create') }}" class="btn btn-primary">
            <i class="ri-add-line"></i> {{ __('messages.add_device') }}
        </a>
    </div>

    <div class="card-grid">
        @forelse($devices as $device)
            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:1rem;">
                    <div>
                        <h3 style="margin-bottom:0.25rem;">{{ $device->name }}</h3>
                        <div style="color:var(--text-muted); font-size:0.9rem;">
                            <i class="ri-map-pin-line"></i> {{ $device->location ?? __('messages.no_location') }}
                        </div>
                    </div>
                    <div class="status-indicator {{ $device->status ? 'text-success' : 'text-danger' }}">
                        <i class="ri-record-circle-line"
                            style="color: {{ $device->status ? 'var(--success)' : 'var(--danger)' }}"></i>
                    </div>
                </div>

                <div
                    style="background: rgba(0,0,0,0.2); padding: 0.8rem; border-radius: 8px; margin-bottom: 1rem; font-family: monospace; font-size: 0.9rem; color: var(--text-muted);">
                    <div>IP: {{ $device->ip }}</div>
                    <div>Port: {{ $device->port }}</div>
                </div>

                <div style="display:flex; gap:0.5rem;">
                    <a href="{{ route('devices.show', $device->id) }}" class="btn btn-secondary"
                        style="flex:1; text-align:center;">{{ __('messages.manage') }}</a>
                    <button class="btn btn-primary" onclick="testConnection({{ $device->id }}, this)" style="flex:1;">
                        <i class="ri-refresh-line"></i> {{ __('messages.ping') }}
                    </button>
                </div>
            </div>
        @empty
            <div style="grid-column: 1/-1; text-align: center; padding: 3rem; color: var(--text-muted);">
                <i class="ri-router-line" style="font-size: 3rem; margin-bottom: 1rem; display:block;"></i>
                <p>{{ __('messages.no_devices') }}</p>
            </div>
        @endforelse
    </div>

@endsection

@push('scripts')
    <script>
        function testConnection(id, btn) {
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="loading-spinner" style="width:15px; height:15px;"></span>';
            btn.disabled = true;

            fetch(`/devices/${id}/connect`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        btn.innerHTML = '<i class="ri-check-line"></i> OK';
                        btn.style.background = 'var(--success)';
                    } else {
                        btn.innerHTML = '<i class="ri-close-line"></i> Fail';
                        btn.style.background = 'var(--danger)';
                    }
                    setTimeout(() => {
                        btn.innerHTML = originalText;
                        btn.style.background = '';
                        btn.disabled = false;
                    }, 2000);
                })
                .catch(e => {
                    btn.innerHTML = 'Error';
                    console.error(e);
                });
        }
    </script>
@endpush