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

    {{-- Sync All Quick Action --}}
    <div style="margin-bottom:2rem;">
        <a href="{{ route('devices.sync-all') }}" style="display:flex; align-items:center; gap:1rem; padding:1.2rem 1.5rem; border-radius:12px;
                       background: linear-gradient(135deg, rgba(99,102,241,0.15), rgba(16,185,129,0.1));
                       border:1px solid rgba(99,102,241,0.3); text-decoration:none; color:inherit;
                       transition:all 0.2s;"
            onmouseover="this.style.borderColor='rgba(99,102,241,0.6)'; this.style.transform='translateY(-1px)';"
            onmouseout="this.style.borderColor='rgba(99,102,241,0.3)'; this.style.transform='none';">
            <div style="width:48px; height:48px; border-radius:12px; background:rgba(99,102,241,0.2);
                            display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i class="ri-refresh-line" style="font-size:1.5rem; color:#6366f1;"></i>
            </div>
            <div>
                <div style="font-weight:700; font-size:1rem;">Sync All Devices</div>
                <div style="font-size:0.82rem; color:var(--text-muted); margin-top:2px;">
                    Ping all devices, then sync attendance in parallel with live progress
                </div>
            </div>
            <div style="margin-left:auto; color:var(--text-muted);">
                <i class="ri-arrow-right-s-line" style="font-size:1.4rem;"></i>
            </div>
        </a>
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