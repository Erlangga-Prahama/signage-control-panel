<?php

namespace App\Events;

use App\Models\Device;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeviceStatusUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Device $device)
    {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('signage-dashboard')
        ];
    }

    public function broadcastAs(): string
    {
        return 'device.status.updated';
    }

    public function broadcastWtih(): array
    {
        return [
            'id' => $this->device->id,
            'nama' => $this->device->nama,
            'lokasi' => $this->device->lokasi,
            'status' => $this->device->status,
            'last_seen' => $this->device->last_seen?->toIso8601String(),
        ];
    }
}
