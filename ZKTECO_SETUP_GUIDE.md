# ZKTeco Integration Guide

This guide explains how to integrate and use ZKTeco devices with your Laravel application.

## What Was Done

We've integrated the `fsuuaas/zkteco` package into your Laravel application to connect and manage ZKTeco attendance devices. Here's what was created:

### 1. Configuration File
- **File**: `config/zkteco.php`
- **Purpose**: Stores default IP and port settings for your ZKTeco device

### 2. Controller
- **File**: `app/Http/Controllers/ZktecoController.php`
- **Purpose**: Handles all communication with the ZKTeco device
- **Features**:
  - Connect to device
  - Get device information
  - Get users list
  - Get attendance logs
  - Execute device actions (restart, shutdown, sync time, etc.)

### 3. Routes
- **File**: `routes/web.php`
- **Routes Created**:
  - `GET /` - Main page with device connection form
  - `GET /zkteco/users` - Get all users from device
  - `GET /zkteco/attendance` - Get attendance logs
  - `POST /zkteco/action` - Execute device actions

### 4. Welcome Page
- **File**: `resources/views/welcome.blade.php`
- **Features**:
  - Connection form (IP & Port)
  - Device information display
  - Device action buttons
  - Users and attendance viewer

## Setup Instructions

### Step 1: Install the Package (if not already installed)

```bash
composer require fsuuaas/zkteco
```

### Step 2: Configure Device Settings

You have two options:

#### Option A: Using .env file (Recommended)

Add these lines to your `.env` file:

```env
ZKTECO_IP=192.168.1.201
ZKTECO_PORT=4370
```

Replace `192.168.1.201` with your device's IP address and `4370` with the port (usually 4370).

#### Option B: Use the form on the page

You can enter the IP and port directly in the web interface without editing the .env file.

### Step 3: Access the Application

1. Start your Laravel development server:
   ```bash
   php artisan serve
   ```

2. Open your browser and go to:
   ```
   http://localhost:8000
   ```

3. You'll see the ZKTeco Device Manager interface

## How to Use

### Connecting to Your Device

1. **Enter Device IP and Port**:
   - Fill in the "Device IP" field (e.g., `192.168.1.201`)
   - Fill in the "Port" field (usually `4370`)
   - Click the "Connect" button

2. **View Device Information**:
   - After successful connection, you'll see:
     - Device Name
     - Serial Number
     - Version
     - Firmware Version
     - Platform
     - Device Time
     - Number of Users

### Available Actions

Once connected, you can use these buttons:

1. **Sync Time** - Synchronizes device time with server time
2. **Test Voice** - Plays a test voice message on the device
3. **Get Users** - Displays all users stored on the device
4. **Get Attendance** - Shows attendance logs (last 50 records)
5. **Restart** - Restarts the ZKTeco device

### Troubleshooting

#### Can't Connect?
- Check if the IP address is correct
- Verify the port (default is 4370)
- Make sure your device is powered on and connected to the network
- Check if your firewall is blocking the connection
- Ensure your computer is on the same network as the device

#### Connection Timeout?
- Verify network connectivity (ping the device IP)
- Check if another application is using the device
- Try disconnecting and reconnecting

## Technical Details

### Package Used
- **Package**: `fsuuaas/zkteco`
- **Namespace**: `Fsuuaas\Zkteco\Lib\ZKTeco`
- **Documentation**: Check the package on GitHub/Packagist

### Code Examples

If you want to use ZKTeco in your own code:

```php
use Fsuuaas\Zkteco\Lib\ZKTeco;

// Create instance
$zk = new ZKTeco('192.168.1.201', 4370);

// Connect
if ($zk->connect()) {
    // Enable device (required before operations)
    $zk->enableDevice();
    
    // Get device information
    $version = $zk->version();
    $users = $zk->getUser();
    $attendance = $zk->getAttendance();
    
    // Disable device (required after operations)
    $zk->disableDevice();
    
    // Disconnect
    $zk->disconnect();
}
```

## Files Modified/Created

1. ✅ `config/zkteco.php` - NEW
2. ✅ `app/Http/Controllers/ZktecoController.php` - NEW
3. ✅ `routes/web.php` - MODIFIED
4. ✅ `resources/views/welcome.blade.php` - MODIFIED

## Quick Start Checklist

- [ ] Package installed (`composer require fsuuaas/zkteco`)
- [ ] Configuration file created (`config/zkteco.php`)
- [ ] Controller created (`app/Http/Controllers/ZktecoController.php`)
- [ ] Routes added to `routes/web.php`
- [ ] Welcome page updated
- [ ] Device IP and Port configured (in .env or via form)
- [ ] Device powered on and connected to network
- [ ] Application running (`php artisan serve`)
- [ ] Browser opened to `http://localhost:8000`

## Need Help?

1. Check device IP: Usually found in device settings or network configuration
2. Default port: 4370 (most ZKTeco devices use this)
3. Network: Make sure your computer and device are on the same network
4. Firewall: Check if Windows Firewall or antivirus is blocking connections

That's it! You're ready to use your ZKTeco device with Laravel! 🎉

