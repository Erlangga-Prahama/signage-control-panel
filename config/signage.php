<?php

return [
    'offline_threshold' => env('DEVICE_OFFLINE_THRESHOLD', 20),

    'jwt' => [
        'secret' => env('JWT_SECRET', env('APP_KEY')),
        'ttl_minutes' => env('JWT_TTL_MINUTES', 120),
    ],
];