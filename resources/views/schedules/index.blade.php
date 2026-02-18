@extends('layouts.master')

@section('title', 'Weekly Schedule Settings')

@section('content')

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="header"
        style="margin-bottom:1.5rem; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:1rem;">
        <h3 style="margin:0;">Weekly Schedule Settings</h3>
        <p style="margin:0; font-size:0.9rem; color:var(--text-muted);">
            Define standard working hours for each day of the week.
        </p>
    </div>

    @if(session('success'))
        <div class="alert alert-success" style="padding: 1rem; margin-bottom: 1rem; background: rgba(40, 167, 69, 0.2); border: 1px solid rgba(40, 167, 69, 0.4); border-radius: 4px; color: #28a745;">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('schedules.update_all') }}" method="POST">
        @csrf
        <div class="table-container">
            <table class="table" style="width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 15rem;">Day</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th style="text-align:center;">Off Day?</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($schedules as $index => $schedule)
                        <tr style="{{ $schedule->is_off_day ? 'opacity: 0.6;' : '' }}">
                            <td style="font-weight: 600;">
                                <input type="hidden" name="schedules[{{ $index }}][id]" value="{{ $schedule->id }}">
                                {{ $schedule->day_name }}
                            </td>
                            <td>
                                <input type="time" name="schedules[{{ $index }}][start_time]" 
                                    value="{{ $schedule->start_time ? \Carbon\Carbon::parse($schedule->start_time)->format('H:i') : '' }}"
                                    class="form-control"
                                    {{ $schedule->is_off_day ? 'disabled' : '' }}>
                            </td>
                            <td>
                                <input type="time" name="schedules[{{ $index }}][end_time]" 
                                    value="{{ $schedule->end_time ? \Carbon\Carbon::parse($schedule->end_time)->format('H:i') : '' }}"
                                    class="form-control"
                                    {{ $schedule->is_off_day ? 'disabled' : '' }}>
                            </td>
                            <td style="text-align:center;">
                                <div class="form-check form-switch" style="justify-content:center; display:flex;">
                                    <input class="form-check-input off-day-toggle" type="checkbox" 
                                        name="schedules[{{ $index }}][is_off_day]" 
                                        value="1" 
                                        {{ $schedule->is_off_day ? 'checked' : '' }}
                                        data-index="{{ $index }}">
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top: 2rem; text-align: right;">
            <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem;">
                <i class="ri-save-line" style="margin-right: 5px;"></i> Save Changes
            </button>
        </div>
    </form>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const triggers = document.querySelectorAll('.off-day-toggle');
        
        triggers.forEach(trigger => {
            trigger.addEventListener('change', function() {
                const index = this.dataset.index;
                const row = this.closest('tr');
                const inputs = row.querySelectorAll('input[type="time"]');
                
                if (this.checked) {
                    row.style.opacity = '0.6';
                    inputs.forEach(input => input.disabled = true);
                } else {
                    row.style.opacity = '1';
                    inputs.forEach(input => input.disabled = false);
                }
            });
        });
    });
</script>
@endpush
