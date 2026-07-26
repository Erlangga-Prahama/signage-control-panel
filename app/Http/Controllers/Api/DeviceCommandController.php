<?php

namespace App\Http\Controllers\Api;

use App\Events\CommandDispatched;
use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceCommand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeviceCommandController extends Controller
{
    public function store(Request $request, Device $device)
    {
        $validator = Validator::make($request->all(), [
            'command' => 'required|in:push_content,refresh,reboot,clear_override',
            'content_id' => 'required_if:command,push_content|nullable|exists:contents,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $command = DeviceCommand::create([
            'device_id' => $device->id,
            'content_id' => $request->input('content_id'),
            'command' => $request->input('command'),
            'status' => 'pending',
        ]);

        broadcast(new CommandDispatched($command));

        $command->update(['status' => 'delivered', 'delivered_at' => now()]);

        return response()->json($command->load('content'), 201);
    }

    /**
     * Device client calls this after it has applied the command, so the
     * dashboard can show a "confirmed on screen" checkmark.
     */
    public function ack(Request $request, DeviceCommand $command)
    {
        $device = $request->attributes->get('device');

        if (! $device || $device->id !== $command->device_id) {
            return response()->json(['message' => 'Command tidak ditemukan untuk device ini.'], 403);
        }

        $command->update(['status' => 'acked', 'acked_at' => now()]);

        return response()->json($command);
    }
}