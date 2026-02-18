@extends('layouts.master')

@section('title', 'Overview')

@section('content')

    <div class="card-grid">
        <!-- Total Devices -->
        <div class="card">
            <div class="stat-label">Total Devices</div>
            <div class="stat-value">{{ $totalDevices }}</div>
            <div style="color:var(--text-muted); font-size:0.8rem;">
                <span style="color:var(--success)">{{ $activeDevices }} Online</span>
            </div>
            <div style="position:absolute; right:-10px; top:-10px; opacity:0.1; font-size:5rem;">
                <i class="ri-router-line"></i>
            </div>
        </div>

        <!-- Total Users -->
        <div class="card">
            <div class="stat-label">Total Employees</div>
            <div class="stat-value">{{ $totalUsers }}</div>
            <div style="color:var(--text-muted); font-size:0.8rem;">Synced from devices</div>
            <div style="position:absolute; right:-10px; top:-10px; opacity:0.1; font-size:5rem;">
                <i class="ri-group-line"></i>
            </div>
        </div>

        <!-- Today's Attendance -->
        <div class="card">
            <div class="stat-label">Today's Check-ins</div>
            <div class="stat-value">{{ $todayCheckIns }}</div>
            <div style="color:var(--text-muted); font-size:0.8rem;">
                @if($percentageChange > 0)
                    <span style="color:var(--success)">+{{ $percentageChange }}%</span> vs yesterday
                @elseif($percentageChange < 0)
                    <span style="color:var(--danger)">{{ $percentageChange }}%</span> vs yesterday
                @else
                    <span>Same as yesterday</span>
                @endif
            </div>
            <div style="position:absolute; right:-10px; top:-10px; opacity:0.1; font-size:5rem;">
                <i class="ri-calendar-check-line"></i>
            </div>
        </div>
    </div>

    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h3>Recent Activity</h3>
            <button class="btn btn-secondary" style="font-size:0.8rem">View All</button>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Device</th>
                        <th>User ID</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Type</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentLogs as $log)
                        <tr>
                            <td>{{ $log->device->name }}</td>
                            <td>{{ $log->user_id_on_device }}</td>
                            <td>{{ $log->timestamp->format('H:i:s Y-m-d') }}</td>
                            <td>
                                <span class="badge badge-success">
                                    Check-{{ $log->status }} <!-- Modify based on status mapping -->
                                </span>
                            </td>
                            <td>{{ $log->type }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align:center; padding: 2rem; color:var(--text-muted)">No recent activity
                                found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection