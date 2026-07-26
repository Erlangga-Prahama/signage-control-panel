<?php


use Illuminate\Support\Facades\Broadcast;

// Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
//     return (int) $user->id === (int) $id;
// });

// signage-dashboard and device.{id} are public channels here to keep the
// device client dead simple (it already authenticates its HTTP calls with
// its device_key). If you want to lock the WebSocket channels down too,
// switch both channels to PrivateChannel and add auth here, e.g.:
//
// Broadcast::channel('device.{deviceId}', function ($user, $deviceId) {
//     return true; // validate a device token passed via Echo auth here
// });