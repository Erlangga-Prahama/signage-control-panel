<?php

namespace App\Http\Controllers\Api;

use App\Events\DeviceStatusUpdated;
use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeviceController extends Controller
{
    public function index()
    {
        return Device::with('playlist')->latest()->get();
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'lokasi' => 'nullable|string|max:255',
            'playlist_id' => 'nullable|exists:playlists,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $device = Device::create($validator->validated());

        return response()->json($device, 201);
    }

    public function show(Device $device)
    {
        return $device->load(['playlist.items.content', 'commands' => fn ($q) => $q->latest()->limit(10)]);
    }

    public function update(Request $request, Device $device)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'sometimes|required|string|max:255',
            'lokasi' => 'nullable|string|max:255',
            'playlist_id' => 'nullable|exists:playlists,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $device->update($validator->validated());

        return response()->json($device->fresh());
    }

    public function destroy(Device $device)
    {
        $device->delete();

        return response()->json(['message' => 'Device dihapus.']);
    }

    public function heartbeat(Request $request)
    {
        $device = $request->attributes->get('device');

        $wasOffline = $device->status !== 'online';

        $device->status = 'online';
        $device->last_seen = now();
        $device->save();

        if ($wasOffline) {
            broadcast(new DeviceStatusUpdated($device));
        }

        return response()->json(['message' => 'ok', 'server_time' => now()->toIso8601String()]);
    }
}