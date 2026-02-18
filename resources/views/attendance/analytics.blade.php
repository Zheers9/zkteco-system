@extends('layouts.master')

@section('title', 'Attendance Analytics')

@section('content')

    <div class="card">
        <div class="header"
            style="margin-bottom:1rem; justify-content:space-between; display:flex; align-items:flex-end; flex-wrap:wrap; gap:1rem;">
            <div>
                <h3 style="margin:0;">Employee Absence Analytics</h3>
                <p style="margin:0; font-size:0.9rem; color:var(--text-muted);">
                    Tracking compliance with the {{ $requiredDays }}-day/week requirement containing:
                    {{ $weeks_count ?? '0' }} weeks
                </p>
            </div>

            <div style="display:flex; gap:1rem; align-items:flex-end;">
                <!-- Settings Form directly here for convenience -->
                <form action="{{ route('attendance.settings.update') }}" method="POST"
                    style="display:flex; gap:5px; align-items:center; background:rgba(255,255,255,0.05); padding:0.5rem; border-radius:8px;">
                    @csrf
                    <label style="font-size:0.8rem; margin:0;">Required Days/Week:</label>
                    <input type="number" name="required_work_days" value="{{ $requiredDays }}" min="1" max="7"
                        style="width:50px; padding:0.25rem; border-radius:4px; border:1px solid rgba(255,255,255,0.2); background:transparent; color:inherit;">
                    <button type="submit" class="btn btn-sm btn-secondary">Save</button>
                </form>

                <form action="{{ route('attendance.analytics') }}" method="GET"
                    style="display:flex; gap:10px; align-items:flex-end;">
                    <div>
                        <label
                            style="display:block; font-size:0.8rem; color:var(--text-muted); margin-bottom:4px;">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                            placeholder="Search..." style="margin:0; width:200px; padding:0.4rem;">
                    </div>
                    <div>
                        <label style="display:block; font-size:0.8rem; color:var(--text-muted); margin-bottom:4px;">
                            Start Date
                        </label>
                        <input type="date" name="start_date" value="{{ $startDate }}" class="form-control"
                            style="margin:0; width:130px; padding:0.4rem;">
                    </div>
                    <div>
                        <label style="display:block; font-size:0.8rem; color:var(--text-muted); margin-bottom:4px;">
                            End Date
                        </label>
                        <input type="date" name="end_date" value="{{ $endDate }}" class="form-control"
                            style="margin:0; width:130px; padding:0.4rem;">
                    </div>
                    <button type="submit" class="btn btn-primary"
                        style="height:36px; display:inline-flex; align-items:center;">
                        <i class="ri-refresh-line"></i>
                    </button>
                </form>
            </div>
        </div>

        <!-- Summary Cards -->
        <div
            style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:2rem;">
            <div class="card"
                style="background:rgba(255,99,132,0.1); border:1px solid rgba(255,99,132,0.3); padding:1rem; margin:0;">
                <div style="color:var(--text-muted); font-size:0.9rem;">Total Absences Detected</div>
                <div style="font-size:1.8rem; font-weight:700; color:#ff6384;">
                    {{ array_sum(array_column($analytics, 'absences')) }}
                </div>
                <div style="font-size:0.8rem; opacity:0.7;">Across {{ count($analytics) }} employees</div>
            </div>
            <div class="card"
                style="background:rgba(54, 162, 235, 0.1); border:1px solid rgba(54, 162, 235, 0.3); padding:1rem; margin:0;">
                <div style="color:var(--text-muted); font-size:0.9rem;">Avg Compliance Rate</div>
                <div style="font-size:1.8rem; font-weight:700; color:#36a2eb;">
                    @php
                        $avg = count($analytics) > 0 ? array_sum(array_column($analytics, 'compliance_rate')) / count($analytics) : 0;
                    @endphp
                    {{ number_format($avg, 1) }}%
                </div>
                <div style="font-size:0.8rem; opacity:0.7;">Meeting {{ $requiredDays }} days/week goal</div>
            </div>
        </div>

        <div class="table-container">
            <table class="table-hover">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Employee Name</th>
                        <th style="text-align:center;">Total Present Days</th>
                        <th style="text-align:center;">Weeks Analyzed</th>
                        <th style="text-align:right;">Total Absences</th>
                        <th style="width:150px;">Compliance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($analytics as $row)
                        <tr onclick="window.location='{{ route('attendance.analytics.details', ['user' => $row['user_id']]) }}?start_date={{ $startDate }}&end_date={{ $endDate }}'"
                            style="cursor:pointer;">
                            <td>{{ $row['user_id'] }}</td>
                            <td>
                                <div style="font-weight:600;">{{ $row['name'] }}</div>
                            </td>
                            <td style="text-align:center;">
                                {{ $row['attended_days'] }}
                            </td>
                            <td style="text-align:center;">
                                {{ $row['weeks_count'] }}
                            </td>
                            <td style="text-align:right;">
                                @if($row['absences'] > 0)
                                    <span class="badge badge-danger" style="font-size:0.9rem;">
                                        -{{ $row['absences'] }} Days
                                    </span>
                                @else
                                    <span class="badge badge-success">Perfect</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <div
                                        style="flex:1; height:6px; background:rgba(255,255,255,0.1); border-radius:3px; overflow:hidden;">
                                        <div
                                            style="height:100%; width:{{ $row['compliance_rate'] }}%; background: {{ $row['compliance_rate'] < 50 ? '#ff6384' : ($row['compliance_rate'] < 80 ? '#ffcd56' : '#4bc0c0') }};">
                                        </div>
                                    </div>
                                    <span style="font-size:0.8rem; min-width:30px;">{{ $row['compliance_rate'] }}%</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center; padding:2rem;">
                                No data available for this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection