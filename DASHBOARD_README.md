# ZKTeco Dashboard Guide

## Features
- **Multi-Device Support**: Manage multiple ZKTeco devices from a single dashboard.
- **Modern UI**: Dark/Glassmorphism design.
- **Data Sync**: Sync Users and Attendance Logs to your local database.
- **Real-time Stats**: View active devices and recent logs.

## Getting Started

1. **Access the Dashboard**:
   Go to `http://localhost:8000` (or your app URL).

2. **Add a Device**:
   - Click "Devices" in the sidebar.
   - Click "Add New Device".
   - Enter Name, IP, Port (usually 4370), and Location.

3. **Connect & Sync**:
   - On the Devices list, click "Ping" to test connection.
   - Click "Manage" to view details.
   - Use "Sync Users" to fetch employees from the device.
   - Use "Sync Attendance" to fetch logs.

## Troubleshooting
- **Connection Failed**: Ensure the server can ping the device IP. Check firewall settings.
- **Empty Logs**: Make sure the device has attendance records.

## Technical Info
- **Database Tables**: `devices`, `device_users`, `attendance_logs`.
- **Controllers**: `DeviceController`, `DashboardController`.
- **Styles**: `public/css/dashboard.css`.
