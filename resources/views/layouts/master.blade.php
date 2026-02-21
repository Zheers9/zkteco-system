<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ZKTeco Manager</title>
    @if(app()->getLocale() == 'ar')
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
        <style>
            body {
                font-family: 'Cairo', sans-serif !important;
            }
        </style>
    @endif
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v={{ time() }}">
    @if(app()->getLocale() == 'ar')
        <link rel="stylesheet" href="{{ asset('css/dashboard-rtl.css') }}?v={{ time() }}">
    @endif
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
</head>

<body>

    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="brand">
            <img src="{{ asset('storage/EPU-5.png') }}" alt="Logo" style="max-width:180px; height:auto;">
        </div>

        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="ri-dashboard-line"></i> <span>{{ __('messages.dashboard') }}</span>
        </a>

        <a href="{{ route('devices.index') }}" class="nav-link {{ request()->routeIs('devices.*') ? 'active' : '' }}">
            <i class="ri-router-line"></i> <span>{{ __('messages.devices') }}</span>
        </a> <!-- Expanded from devices.* match -->

        <a href="{{ route('device-users.index') }}"
            class="nav-link {{ request()->routeIs('device-users.*') ? 'active' : '' }}">
            <i class="ri-group-line"></i> <span>{{ __('messages.device_users') }}</span>
        </a>

        <a href="{{ route('departments.index') }}"
            class="nav-link {{ request()->routeIs('departments.*') ? 'active' : '' }}">
            <i class="ri-building-4-line"></i> <span>Departments</span>
        </a>

        <a href="{{ route('university-users.index') }}"
            class="nav-link {{ request()->routeIs('university-users.*') ? 'active' : '' }}">
            <i class="ri-user-star-line"></i> <span>{{ __('messages.university_users') }}</span>
        </a>

        <a href="{{ route('attendance.index') }}"
            class="nav-link {{ request()->routeIs('attendance.index') ? 'active' : '' }}">
            <i class="ri-calendar-check-line"></i> <span>{{ __('messages.attendance') }}</span>
        </a>

        <a href="{{ route('attendance.report') }}"
            class="nav-link {{ request()->routeIs('attendance.report') ? 'active' : '' }}">
            <i class="ri-file-chart-line"></i> <span>Attendance Report</span>
        </a>

        <a href="{{ route('attendance.analytics') }}"
            class="nav-link {{ request()->routeIs('attendance.analytics') ? 'active' : '' }}">
            <i class="ri-bar-chart-box-line"></i> <span>Absence Analytics</span>
        </a>

        <a href="{{ route('attendance.payroll') }}"
            class="nav-link {{ request()->routeIs('attendance.payroll') ? 'active' : '' }}">
            <i class="ri-money-dollar-circle-line"></i> <span>Payroll Report</span>
        </a>

        <a href="{{ route('schedules.index') }}"
            class="nav-link {{ request()->routeIs('schedules.*') ? 'active' : '' }}">
            <i class="ri-time-line"></i> <span>Weekly Schedule</span>
        </a>

        <a href="{{ route('permissions.index') }}"
            class="nav-link {{ request()->routeIs('permissions.*') ? 'active' : '' }}">
            <i class="ri-calendar-event-line"></i> <span>Permissions / Leaves</span>
        </a>

        <div style="flex:1"></div>

        <a href="#" class="nav-link">
            <i class="ri-settings-4-line"></i> <span>{{ __('messages.settings') }}</span>
        </a>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <header class="header">
            <div>
                <h1 class="page-title">@yield('title', __('messages.dashboard'))</h1>
                <p style="color:var(--text-muted); font-size: 0.9rem; margin-top: 0.25rem;">{{ __('messages.welcome') }}
                </p>
            </div>

            <div style="display:flex; gap:1rem; align-items:center;">
                @yield('header_actions')
                <!-- Language Switcher -->
                <div class="lang-switcher">
                    <a href="{{ route('locale', 'en') }}"
                        class="btn btn-sm {{ app()->getLocale() == 'en' ? 'btn-primary' : 'btn-secondary' }}"
                        style="padding: 0.25rem 0.5rem; margin: 0 2px;">EN</a>
                    <a href="{{ route('locale', 'ar') }}"
                        class="btn btn-sm {{ app()->getLocale() == 'ar' ? 'btn-primary' : 'btn-secondary' }}"
                        style="padding: 0.25rem 0.5rem; margin: 0 2px;">AR</a>
                </div>
                <button class="btn btn-secondary"><i class="ri-notification-3-line"></i></button>
                <div
                    style="width:36px; height:36px; background:linear-gradient(135deg, var(--primary), var(--secondary)); border-radius:50%;">
                </div>
            </div>
        </header>

        <div class="content-area">
            @if(session('success'))
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        showToast("{{ session('success') }}", 'success');
                    });
                </script>
            @endif
            @if(session('error'))
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        showToast("{{ session('error') }}", 'error');
                    });
                </script>
            @endif

            @yield('content')
        </div>
    </main>

    <!-- Toast Overlay (Backdrop) -->
    <div id="toast-overlay" class="toast-overlay"></div>

    <!-- Toast Container -->
    <div id="toast-container" class="toast-container"></div>

    <style>
        /* Force these styles to ensure they override everything */
        .toast-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 9998;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .toast-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        .toast-container {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            align-items: center;
            width: auto;
        }

        .toast {
            background: #1e293b;
            background: var(--bg-card, #1e293b);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 2rem;
            color: #f8fafc;
            color: var(--text-main, #f8fafc);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column;
            min-width: 450px;
            max-width: 90vw;
            opacity: 0;
            transform: scale(0.9);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .toast.show {
            opacity: 1;
            transform: scale(1);
        }

        .toast-success {
            border-left: 6px solid #10b981;
        }

        .toast-error {
            border-left: 6px solid #ef4444;
        }

        .toast-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .toast-header i {
            font-size: 2rem;
        }

        .toast-header h3 {
            font-size: 1.3rem;
            font-weight: 600;
            margin: 0;
        }

        .toast-body {
            padding: 0.5rem 0;
            line-height: 1.6;
        }

        .toast-body p {
            margin: 0.5rem 0;
            font-size: 1rem;
        }

        .toast-footer {
            display: flex;
            justify-content: flex-end;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .toast-btn {
            padding: 0.6rem 2rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-family: inherit;
            transition: all 0.2s;
            font-size: 0.95rem;
        }

        .toast-btn-success {
            background: #10b981;
            color: white;
        }

        .toast-btn-success:hover {
            background: #059669;
        }

        .toast-btn-error {
            background: #ef4444;
            color: white;
        }

        .toast-btn-error:hover {
            background: #dc2626;
        }
    </style>

    <script>
        // Simple interactions
        document.addEventListener('DOMContentLoaded', () => {
            // Add any global JS here
        });

        // Toast Helper - Updated for structured messages with OK button
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const overlay = document.getElementById('toast-overlay');

            // Activate overlay
            overlay.classList.add('active');

            // Create toast
            const toast = document.createElement('div');
            const icon = type === 'success' ? 'ri-checkbox-circle-line' : 'ri-error-warning-line';
            const extraClass = type === 'success' ? 'toast-success' : 'toast-error';
            const color = type === 'success' ? '#10b981' : '#ef4444';
            const title = type === 'success' ? '{{ __('messages.success') }}' : '{{ __('messages.error') }}';
            const btnClass = type === 'success' ? 'toast-btn-success' : 'toast-btn-error';

            toast.className = `toast ${extraClass}`;

            // Structure the message - split by periods or newlines for better formatting
            let messageLines = message.split('.').filter(line => line.trim());
            let messageHTML = messageLines.map(line => `<p>${line.trim()}.</p>`).join('');

            toast.innerHTML = `
                <div class="toast-header">
                    <i class="${icon}" style="color:${color}"></i>
                    <h3>${title}</h3>
                </div>
                <div class="toast-body">
                    ${messageHTML}
                </div>
                <div class="toast-footer">
                    <button class="toast-btn ${btnClass}" onclick="closeToast(this)">OK</button>
                </div>
            `;

            // Clear previous toasts to keep it single modal style
            container.innerHTML = '';
            container.appendChild(toast);

            // Trigger animation
            requestAnimationFrame(() => {
                toast.classList.add('show');
            });

            // Click overlay to close
            overlay.onclick = function () {
                closeToast(toast.querySelector('.toast-btn'));
            };
        }

        // Close toast function
        function closeToast(btn) {
            const container = document.getElementById('toast-container');
            const overlay = document.getElementById('toast-overlay');
            const toast = btn.closest('.toast');

            if (toast) {
                toast.classList.remove('show');
                toast.style.opacity = '0';

                setTimeout(() => {
                    overlay.classList.remove('active');
                    toast.remove();
                }, 300);
            }
        }
    </script>
    @stack('scripts')
</body>

</html>