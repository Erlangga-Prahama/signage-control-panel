<?php

namespace App\Http\Middleware;

use App\Models\Device;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateDevice
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
       $key = $request->header('X-Device-Key') ?? $request->query('device_key');

       if (!$key) {
        return response()->json(['message' => 'Device key wajib disertakan'], 401);
       }

       $device = Device::where('device_key', $key)->first();

       if (!$device) {
        return response()->json(['message' => 'Device key tidak dikenal.'], 401);
       }

       $request->attributes->set('device', $device);

        return $next($request);
    } 
}
