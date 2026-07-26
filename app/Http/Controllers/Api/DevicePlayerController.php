<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\Request;

class DevicePlayerController extends Controller
{
    /**
     * What the device client should be showing right now: its assigned
     * playlist (in order) plus reverb connection details so it can open
     * its own private "device.{id}" channel for pushed commands.
     */
    public function show(Request $request)
    {
        /** @var Device $device */
        $device = $request->attributes->get('device');

        $device->load('playlist.items.content');

        return response()->json([
            'device' => [
                'id' => $device->id,
                'nama' => $device->nama,
                'lokasi' => $device->lokasi,
            ],
            'playlist' => $device->playlist ? [
                'id' => $device->playlist->id,
                'nama' => $device->playlist->nama,
                'items' => $device->playlist->items->map(fn ($item) => [
                    'id' => $item->id,
                    'durasi_detik' => $item->durasi_detik,
                    'content' => [
                        'id' => $item->content->id,
                        'judul' => $item->content->judul,
                        'tipe' => $item->content->tipe,
                        'url' => $item->content->resolved_url,
                        'payload' => $item->content->tipe === 'text' ? $item->content->payload : null,
                    ],
                ]),
            ] : null,
            'reverb' => [
                'key' => env('REVERB_APP_KEY'),
                'host' => env('REVERB_HOST', 'localhost'),
                'port' => (int) env('REVERB_PORT', 8080),
                'scheme' => env('REVERB_SCHEME', 'http'),
            ],
        ]);
    }
}