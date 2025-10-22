<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('grid-presence', function () {
    return true;
});

Broadcast::channel('grid-active-users', function () {
    return true;
});
