@extends('layouts.master')

@section('title', 'Schedule Settings')

@section('content')

<div class="page-header-sticky"
    style="position: sticky; top: -2rem; z-index: 100; background: #0b1120; margin: -2rem -2rem 2rem -2rem; padding: 1.5rem 2rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center; backdrop-filter: blur(10px);">
    <div>
        <h3 style="margin:0;">Working Schedule Settings</h3>
        <p style="margin:0; font-size:0.9rem; color:var(--text-muted);">
            Define standard working hours and special periods (e.g., Ramadan, Exams).
        </p>
    </div>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('newPeriodModal').style.display='block'">
        <i class="ri-add-line"></i> Add Special Period
    </button>
</div>

@if(session('success'))
    <div class="alert alert-success" style="padding: 1rem; margin-bottom: 2rem; background: rgba(40, 167, 69, 0.2); border: 1px solid rgba(40, 167, 69, 0.4); border-radius: 4px; color: #28a745;">
        {{ session('success') }}
    </div>
@endif

<div class="grid" style="display: flex; flex-direction: column; gap: 2rem;">
    
    <!-- Weekly Default Schedule -->
    <div class="card">
        <div class="card-header" style="margin-bottom: 1rem;">
            <h4 style="margin:0; color:var(--primary);">Weekly Default Schedule</h4>
            <small style="color:var(--text-muted);">Applied when no special period is active.</small>
        </div>

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
                        @foreach($defaults as $index => $schedule)
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
                                            {{ $schedule->is_off_day ? 'checked' : '' }}>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 1rem; text-align: right;">
                <button type="submit" class="btn btn-primary">
                    <i class="ri-save-line"></i> Save Defaults
                </button>
            </div>
        </form>
    </div>

    <!-- Special Periods -->
    @foreach($periods as $key => $periodSchedules)
        @php
            [$name, $start, $end] = explode('|', $key);
            $formId = 'form_' . md5($key);
        @endphp
        <div class="card" style="border-left: 4px solid var(--accent);">
            <div class="card-header" style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h4 style="margin:0; color:var(--accent);">{{ $name }}</h4>
                    <span class="badge badge-info">{{ $start }} to {{ $end }}</span>
                </div>
                <form action="{{ route('schedules.periods.destroy') }}" method="POST" onsubmit="return confirm('Delete this entire period?')">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="name" value="{{ $name }}">
                    <input type="hidden" name="start_date" value="{{ $start }}">
                    <input type="hidden" name="end_date" value="{{ $end }}">
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="ri-delete-bin-line"></i> Delete Period
                    </button>
                </form>
            </div>

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
                            @foreach($periodSchedules as $index => $schedule)
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
                                                {{ $schedule->is_off_day ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: 1rem; text-align: right;">
                    <button type="submit" class="btn btn-accent">
                        <i class="ri-save-line"></i> Update {{ $name }} Times
                    </button>
                </div>
            </form>
        </div>
    @endforeach

</div>

<!-- New Period Modal (Simple HTML/CSS) -->
<div id="newPeriodModal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.8);">
    <div class="card" style="width:500px; margin: 100px auto; padding:2rem;">
        <h3 style="margin-top:0;">Create Special Period</h3>
        <form action="{{ route('schedules.periods.store') }}" method="POST">
            @csrf
            <div class="form-group" style="margin-bottom:1rem;">
                <label>Period Name (e.g., Ramadan 2026)</label>
                <input type="text" name="name" class="form-control" required placeholder="Summer Time">
            </div>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1.5rem;">
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>End Date</label>
                    <input type="date" name="end_date" class="form-control" required>
                </div>
            </div>
            <div style="text-align:right; gap:0.5rem; display:flex; justify-content:flex-end;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('newPeriodModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Period</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle time inputs when "Off Day" changes
        document.body.addEventListener('change', function(e) {
            if (e.target.classList.contains('off-day-toggle')) {
                const row = e.target.closest('tr');
                const inputs = row.querySelectorAll('input[type="time"]');
                
                if (e.target.checked) {
                    row.style.opacity = '0.6';
                    inputs.forEach(input => input.disabled = true);
                } else {
                    row.style.opacity = '1';
                    inputs.forEach(input => input.disabled = false);
                }
            }
        });
    });
</script>
@endpush

