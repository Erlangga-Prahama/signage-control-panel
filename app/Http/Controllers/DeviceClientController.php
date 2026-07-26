<?php

namespace App\Http\Controllers;

use App\Models\Device;

class DeviceClientController extends Controller
{
    public function show(string $deviceKey)
    {
        $device = Device::where('device_key', $deviceKey)->firstOrFail();

        return view('device-client.show', ['device' => $device]);
    }
}