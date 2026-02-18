@extends('layouts.master')

@section('title', 'User Attendance Details')

@section('content')

    <div class="card">
        <div class="header" style="margin-bottom:1rem; justify-content:space-between; display:flex; align-items:flex-end;">
            <div>
                <h3 style="margin:0;">{{ $userName }} (ID: {{ $userId }})</h3>
                <p style="margin:0; font-size:0.9rem; color:var(--text-muted);">
                    Detailed weekly breakdown of attendance
                </p>
            </div>

            <a href="{{ route('attendance.analytics', ['start_date' => $startDate, 'end_date' => $endDate]) }}"
                class="btn btn-secondary">
                <i class="ri-arrow-left-line"></i> Back to Analytics
            </a>
        </div>

        <!-- Summary of this user -->
        <div
            style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:2rem;">
            <div class="card" style="background:rgba(255,255,255,0.02); padding:1rem; margin:0;">
                <div style="color:var(--text-muted); font-size:0.9rem;">Total Weeks Analyzed</div>
                <div style="font-size:1.5rem; font-weight:600;">{{ count($weeklyDetails) }}</div>
            </div>
            <div class="card"
                style="background:rgba(255,99,132,0.1); border:1px solid rgba(255,99,132,0.3); padding:1rem; margin:0;">
                <div style="color:var(--text-muted); font-size:0.9rem;">Total Absences</div>
                <div style="font-size:1.5rem; font-weight:700; color:#ff6384;">
                    {{ array_sum(array_column($weeklyDetails, 'absences')) }}
                </div>
            </div>
        </div>

        <div class="table-container">
            <table class="table-hover">
                <thead>
                    <tr>
                        <th>Week Period</th>
                        <th>Days Attended</th>
                        <th>Required Days</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($weeklyDetails as $week)
                        <tr>
                            <td>
                                {{ \Carbon\Carbon::parse($week['week_start'])->format('M d, Y') }}
                                -
                                {{ \Carbon\Carbon::parse($week['week_end'])->format('M d, Y') }}
                            </td>
                            <td>
                                <span
                                    style="font-weight:600; {{ $week['attended_days'] >= $week['required_days'] ? 'color:#10b981;' : 'color:#f8fafc;' }}">
                                    {{ $week['attended_days'] }}
                                </span>
                            </td>
                            <td>{{ $week['required_days'] }}</td>
                            <td>
                                @if($week['is_compliant'])
                                    <span class="badge badge-success">Met Goal</span>
                                @else
                                    <span class="badge badge-danger">
                                        {{ $week['status'] }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align:center; padding:2rem;">
                                No data available for this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection