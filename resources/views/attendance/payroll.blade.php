@extends('layouts.master')

@section('title', 'Payroll Report (Monthly)')

@section('content')

    <div class="card">
        <div class="header" style="margin-bottom:1rem; justify-content:space-between; display:flex; align-items:flex-end;">
            <div>
                <h3 style="margin:0;">Attendance Payroll</h3>
                <p style="margin:0; font-size:0.9rem; color:var(--text-muted);">
                    Detailed compliance for each required work day
                </p>
            </div>
            <form method="GET" action="{{ route('attendance.payroll') }}"
                style="display:flex; gap:10px; align-items:center;">
                <input type="text" name="search" class="form-control" placeholder="Search Employee..."
                    value="{{ request('search') }}" style="min-width: 200px;">
                <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                <button type="submit" class="btn btn-primary" style="margin-right:10px;">Filter</button>

                <a href="{{ route('attendance.payroll', array_merge(request()->all(), ['export' => 'excel'])) }}"
                    class="btn btn-success" style="display:inline-flex; align-items:center; gap:5px;">
                    <i class="ri-file-excel-line"></i> Excel
                </a>
                <a href="{{ route('attendance.payroll', array_merge(request()->all(), ['export' => 'pdf'])) }}"
                    class="btn btn-danger" style="display:inline-flex; align-items:center; gap:5px;">
                    <i class="ri-file-pdf-line"></i> PDF
                </a>
            </form>
        </div>

        <!-- Scrollable container -->
        <div class="table-container" style="overflow-x:auto;">
            <table class="table-hover" style="width: max-content; border-collapse: separate; border-spacing: 0;">
                <thead>
                    <tr>
                        <th
                            style="background:var(--bg-secondary); position:sticky; left:0; z-index:20; min-width:200px; border-right:2px solid rgba(0,0,0,0.1);">
                            Employee
                        </th>
                        @foreach($weeks as $week)
                            <th colspan="{{ $requiredDays }}"
                                style="text-align:center; border-right:1px solid rgba(255,255,255,0.05); padding:8px;">
                                <div style="font-weight:700;">{{ $week['label'] }}</div>
                                <div style="font-weight:400; color:var(--text-muted); font-size:0.75rem;">Week
                                    {{ $loop->iteration }}
                                </div>
                            </th>
                        @endforeach
                        <th colspan="4" style="text-align:center; background:var(--bg-secondary); border-left:2px solid rgba(0,0,0,0.1);">
                            Summary
                        </th>
                    </tr>
                    <tr>
                        <th
                            style="background:var(--bg-secondary); position:sticky; left:0; z-index:20; border-right:2px solid rgba(0,0,0,0.1);">
                        </th>
                        @foreach($weeks as $week)
                            @for($i = 1; $i <= $requiredDays; $i++)
                                <th
                                    style="text-align:center; min-width:100px; font-size:0.75rem; color:var(--text-muted); border-right:1px solid rgba(255,255,255,0.02);">
                                    Day {{ $i }}
                                </th>
                            @endfor
                        @endforeach
                        <th style="min-width:60px; font-size:0.75rem; text-align:center; color:#10b981; border-left:2px solid rgba(0,0,0,0.1);">Present</th>
                        <th style="min-width:60px; font-size:0.75rem; text-align:center; color:#3b82f6;">Perm.</th>
                        <th style="min-width:60px; font-size:0.75rem; text-align:center; color:#f59e0b;">Late</th>
                        <th style="min-width:60px; font-size:0.75rem; text-align:center; color:#ef4444;">Absent</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payrollData as $row)
                        <tr>
                            <td
                                style="background:var(--bg-card); position:sticky; left:0; z-index:10; font-weight:600; border-right:2px solid rgba(0,0,0,0.1);">
                                {{ $row['name'] }}
                                <div style="font-size:0.8rem; color:var(--text-muted); font-weight:400;">ID:
                                    {{ $row['user_id'] }}
                                </div>
                            </td>
                            @foreach($row['weeks'] as $slots)
                                @foreach($slots as $slot)
                                    <td
                                        style="text-align:center; vertical-align:middle; padding:6px; border-right:1px solid rgba(255,255,255,0.02);">
                                        @if($slot['status'] !== 'Absent')
                                            <div style="
                                                background: {{ $slot['class'] === 'success' ? '#10b98120' : ($slot['class'] === 'info' ? '#3b82f620' : '#f59e0b20') }}; 
                                                color: {{ $slot['class'] === 'success' ? '#10b981' : ($slot['class'] === 'info' ? '#3b82f6' : '#f59e0b') }};
                                                font-size:0.8rem; font-weight:600; padding:4px; border-radius:4px; margin-bottom:2px;
                                            ">
                                                {{ $slot['status'] }}
                                            </div>
                                            <div style="font-size:0.75rem; color:var(--text-muted);">
                                                {{ $slot['details'] }}
                                            </div>
                                        @else
                                            <span class="badge badge-danger" style="font-size:0.75rem;">Absent</span>
                                        @endif
                                    </td>
                                @endforeach
                            @endforeach
                            <td class="text-center font-bold text-success" style="border-left:2px solid rgba(0,0,0,0.1); font-weight:700;">
                                {{ $row['summary']['present'] }}
                            </td>
                            <td class="text-center font-bold text-info" style="font-weight:700; color:#3b82f6;">
                                {{ $row['summary']['permission'] }}
                            </td>
                            <td class="text-center font-bold text-warning" style="font-weight:700;">
                                {{ $row['summary']['late'] }}
                            </td>
                            <td class="text-center font-bold text-danger" style="font-weight:700;">
                                {{ $row['summary']['absent'] }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ (count($weeks) * $requiredDays) + 1 }}" style="text-align:center; padding:2rem;">
                                No data available for this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection