@extends('layouts.master')

@section('title', 'Attendance Report')

@section('content')

    <div class="card">
        <div class="header"
            style="margin-bottom:1.5rem; justify-content:space-between; display:flex; align-items:flex-end; flex-wrap:wrap; gap:1rem;">
            <div>
                <h3 style="margin:0;">Attendance Report</h3>
                <p style="margin:0; font-size:0.9rem; color:var(--text-muted);">
                    Analyzing first/last stamps against weekly schedule
                </p>
            </div>

            <form action="{{ route('attendance.report') }}" method="GET"
                style="display:flex; gap:10px; align-items:flex-end;">
                <div>
                    <label
                        style="display:block; font-size:0.8rem; color:var(--text-muted); margin-bottom:4px;">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                        placeholder="Search..." style="margin:0; width:140px; padding:0.5rem;">
                </div>
                <div>
                    <label style="display:block; font-size:0.8rem; color:var(--text-muted); margin-bottom:4px;">
                        Start Date
                    </label>
                    <input type="date" name="start_date" value="{{ $startDate }}" class="form-control"
                        style="margin:0; width:140px; padding:0.5rem;">
                </div>
                <div>
                    <label style="display:block; font-size:0.8rem; color:var(--text-muted); margin-bottom:4px;">
                        End Date
                    </label>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="form-control"
                        style="margin:0; width:140px; padding:0.5rem;">
                </div>
                <button type="submit" class="btn btn-primary" style="height:38px; display:inline-flex; align-items:center;">
                    <i class="ri-filter-3-line" style="margin-right:5px;"></i> Generate
                </button>
            </form>
        </div>

        <!-- Summary Stats could go here -->

        <div class="table-container">
            <table class="table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Day</th> <!-- Weekday Name -->
                        <th>Employee</th>
                        <th>Schedule</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Device</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report as $row)
                        <tr>
                            <td style="white-space:nowrap; font-weight:500;">
                                {{ $row['date'] }}
                            </td>
                            <td>
                                {{ $row['day_name'] }}
                            </td>
                            <td>
                                <div style="font-weight:600;">{{ $row['name'] }}</div>
                                @if(str_contains($row['name'], 'Unknown'))
                                    <small style="color:var(--text-muted);">ID: {{ $row['user_id'] }}</small>
                                @endif
                            </td>
                            <td>
                                @if($row['schedule_in'] !== '-')
                                    <span class="badge badge-secondary" style="background:rgba(255,255,255,0.1);">
                                        {{ $row['schedule_in'] }} - {{ $row['schedule_out'] }}
                                    </span>
                                @else
                                    <span style="color:var(--text-muted);">Off Day</span>
                                @endif
                            </td>
                            <td style="color: {{ $row['check_in'] === '-' ? 'var(--text-muted)' : 'inherit' }}">
                                {{ $row['check_in'] }}
                            </td>
                            <td style="color: {{ $row['check_out'] === '-' ? 'var(--text-muted)' : 'inherit' }}">
                                {{ $row['check_out'] }}
                            </td>
                            <td>
                                <span style="font-size:0.85rem;">{{ $row['device_name'] }}</span>
                            </td>
                            <td>
                                @php
                                    $statusColor = 'success';
                                    if ($row['status'] === 'Late' || $row['status'] === 'Early Leave') {
                                        $statusColor = 'warning';
                                    } elseif ($row['status'] === 'Late & Early Leave' || $row['status'] === 'Absent') {
                                        $statusColor = 'danger';
                                    } elseif ($row['status'] === '-' || $row['check_in'] === '-') {
                                        $statusColor = 'secondary';
                                    } elseif ($row['status'] === 'Permission') {
                                        $statusColor = 'info';
                                    }
                                @endphp
                                <span class="badge badge-{{ $statusColor }}">
                                    {{ $row['status'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center; padding:2rem;">
                                <div style="color:var(--text-muted);">No records found for this period.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection

@push('styles')
    <style>
        .table-hover tbody tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }

        .badge-secondary {
            background: #6c757d;
            color: white;
        }

        .badge-warning {
            background: #ffc107;
            color: #212529;
        }

        .badge-danger {
            background: #dc3545;
            color: white;
        }

        .badge-success {
            background: #28a745;
            color: white;
        }

        .badge-info {
            background: #0dcaf0;
            /* Bootstrap info color */
            color: black;
        }

        /* Adjust input widths for better visibility */
        input[name="search"] {
            width: 200px !important;
        }

        input[name="start_date"],
        input[name="end_date"] {
            width: 150px !important;
        }
    </style>
@endpush