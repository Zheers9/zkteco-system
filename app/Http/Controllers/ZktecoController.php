<?php

namespace App\Http\Controllers;

use Fsuuaas\Zkteco\Lib\ZKTeco;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ZktecoController extends Controller
{
    /**
     * Get ZKTeco instance
     */
    private function getZKTeco($ip = null, $port = null)
    {
        $ip = $ip ?? config('zkteco.ip');
        $port = $port ?? config('zkteco.port');
        
        return new ZKTeco($ip, $port);
    }

    /**
     * Display device information
     */
    public function index(Request $request)
    {
        $ip = $request->input('ip', config('zkteco.ip'));
        $port = $request->input('port', config('zkteco.port'));
        
        $zk = $this->getZKTeco($ip, $port);
        $info = [];
        $connected = false;
        $error = null;

        try {
            if ($zk->connect()) {
                $connected = true;
                
                // Enable device for reading
                $zk->enableDevice();
                
                // Get device information (with error handling for each method)
                $info = [
                    'connected' => true,
                    'ip' => $ip,
                    'port' => $port,
                ];
                
                // Try to get each piece of info, catch errors individually
                try { $info['version'] = $zk->version(); } catch (\Exception $e) { $info['version'] = 'N/A'; }
                try { $info['os_version'] = $zk->osVersion(); } catch (\Exception $e) { $info['os_version'] = 'N/A'; }
                try { $info['platform'] = $zk->platform(); } catch (\Exception $e) { $info['platform'] = 'N/A'; }
                try { $info['firmware_version'] = $zk->fmVersion(); } catch (\Exception $e) { $info['firmware_version'] = 'N/A'; }
                try { $info['serial_number'] = $zk->serialNumber(); } catch (\Exception $e) { $info['serial_number'] = 'N/A'; }
                try { $info['device_name'] = $zk->deviceName(); } catch (\Exception $e) { $info['device_name'] = 'N/A'; }
                try { $info['work_code'] = $zk->workCode(); } catch (\Exception $e) { $info['work_code'] = 'N/A'; }
                try { $info['ssr'] = $zk->ssr(); } catch (\Exception $e) { $info['ssr'] = 'N/A'; }
                try { $info['pin_width'] = $zk->pinWidth(); } catch (\Exception $e) { $info['pin_width'] = 'N/A'; }
                try { $info['device_time'] = $zk->getTime(); } catch (\Exception $e) { $info['device_time'] = 'N/A'; }
                try { 
                    $users = $zk->getUser();
                    $info['users_count'] = is_array($users) ? count($users) : 0;
                } catch (\Exception $e) { 
                    $info['users_count'] = 'N/A'; 
                }
                
                // Disable device
                $zk->disableDevice();
                $zk->disconnect();
            } else {
                $error = 'Failed to connect to device. Please check IP and port.';
            }
        } catch (\Exception $e) {
            $error = 'Error: ' . $e->getMessage();
            Log::error('ZKTeco Error: ' . $e->getMessage());
            if (isset($zk)) {
                try {
                    @$zk->disableDevice();
                    @$zk->disconnect();
                } catch (\Exception $e2) {
                    // Ignore disconnect errors
                }
            }
        }

        return view('welcome', compact('info', 'connected', 'error', 'ip', 'port'));
    }

    /**
     * Get device users
     */
    public function getUsers(Request $request)
    {
        $ip = $request->input('ip', config('zkteco.ip'));
        $port = $request->input('port', config('zkteco.port'));
        
        $zk = $this->getZKTeco($ip, $port);
        $users = [];
        $error = null;

        try {
            if ($zk->connect()) {
                $zk->enableDevice();
                $users = $zk->getUser();
                $zk->disableDevice();
                $zk->disconnect();
            } else {
                $error = 'Failed to connect to device';
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
            Log::error('ZKTeco Get Users Error: ' . $e->getMessage());
        }

        return response()->json(['users' => $users, 'error' => $error]);
    }

    /**
     * Get attendance logs
     */
    public function getAttendance(Request $request)
    {
        $ip = $request->input('ip', config('zkteco.ip'));
        $port = $request->input('port', config('zkteco.port'));
        $recordSize = $request->input('record_size', 40);
        
        $zk = $this->getZKTeco($ip, $port);
        $attendance = [];
        $error = null;

        try {
            if ($zk->connect()) {
                $zk->enableDevice();
                $attendance = $zk->getAttendance($recordSize);
                $zk->disableDevice();
                $zk->disconnect();
            } else {
                $error = 'Failed to connect to device';
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
            Log::error('ZKTeco Get Attendance Error: ' . $e->getMessage());
        }

        return response()->json(['attendance' => $attendance, 'error' => $error]);
    }

    /**
     * Execute device action (restart, shutdown, etc.)
     */
    public function action(Request $request)
    {
        $ip = $request->input('ip', config('zkteco.ip'));
        $port = $request->input('port', config('zkteco.port'));
        $action = $request->input('action');
        
        $zk = $this->getZKTeco($ip, $port);
        $result = false;
        $error = null;
        $message = '';

        try {
            if ($zk->connect()) {
                $zk->enableDevice();
                
                switch ($action) {
                    case 'restart':
                        $result = $zk->restart();
                        $message = 'Device restart command sent';
                        break;
                    case 'shutdown':
                        $result = $zk->shutdown();
                        $message = 'Device shutdown command sent';
                        break;
                    case 'sleep':
                        $result = $zk->sleep();
                        $message = 'Device sleep command sent';
                        break;
                    case 'resume':
                        $result = $zk->resume();
                        $message = 'Device resume command sent';
                        break;
                    case 'test_voice':
                        $result = $zk->testVoice();
                        $message = 'Voice test command sent';
                        break;
                    case 'sync_time':
                        $result = $zk->setTime(date('Y-m-d H:i:s'));
                        $message = 'Device time synced with server';
                        break;
                    default:
                        $error = 'Unknown action';
                }
                
                $zk->disableDevice();
                $zk->disconnect();
            } else {
                $error = 'Failed to connect to device';
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
            Log::error('ZKTeco Action Error: ' . $e->getMessage());
        }

        return response()->json([
            'success' => $result !== false,
            'message' => $message,
            'error' => $error
        ]);
    }
}


