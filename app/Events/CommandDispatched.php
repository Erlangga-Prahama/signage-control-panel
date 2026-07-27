<?php

namespace App\Events;

use App\Models\Device;
use App\Models\DeviceCommand;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommandDispatched implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public DeviceCommand $command)
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
            new Channel('device.'.$this->command->device_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'command.dispatched';
    }

    public function broadcastWith(): array
    {
        $this->command->loadMissing('content');

        return [
            'id' => $this->command->id,
            'command' => $this->command->command,
            'content' => $this->command->content ? [
                'id' => $this->command->content->id,
                'judul' => $this->command->content->judul,
                'tipe' => $this->command->content->tipe,
                'url' => $this->command->content->resolved_url,
            ] : null,
        ];
    }
}
