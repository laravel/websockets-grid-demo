<?php

use App\Events\UserCountUpdated;
use Illuminate\Support\Facades\Event;

test('returns a successful response', function () {
    // Mock the event broadcasting to avoid connection issues in tests
    Event::fake([UserCountUpdated::class]);

    $response = $this->get('/');

    $response->assertStatus(200);
});
