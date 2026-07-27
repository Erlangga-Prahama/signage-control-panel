<?php

namespace App\Console\Commands;

use App\Events\DeviceStatusUpdated;
use App\Models\Device;
use Illuminate\Console\Command;

class SweepOfflineDevices extends Command
{
    protected $signature = 'signage:sweep-offline {--loop : Keep running, sweeping every few seconds (recommended for real-time status)} {--interval=5 : Seconds between sweeps when --loop is used}';

    protected $description = 'Mark devices offline if no heartbeat has arrived within the configured threshold';

    public function handle(): int
    {
        if ($this->option('loop')) {
            $interval = max(1, (int) $this->option('interval'));

            $this->info("Sweeping every {$interval}s. Press Ctrl+C to stop.");

            while (true) {
                $this->sweep();
                sleep($interval);
            }
        }

        $this->sweep();

        return self::SUCCESS;
    }

    protected function sweep(): void
    {
        $threshold = (int) config('signage.offline_threshold', 15);

        $stale = Device::where('status', 'online')
            ->where(function ($q) use ($threshold) {
                $q->whereNull('last_seen')
                    ->orWhere('last_seen', '<', now()->subSeconds($threshold));
            })
            ->get();

        foreach ($stale as $device) {
            $device->status = 'offline';
            $device->save();

            broadcast(new DeviceStatusUpdated($device));

            $this->info("Device #{$device->id} ({$device->nama}) marked offline.");
        }
    }
}