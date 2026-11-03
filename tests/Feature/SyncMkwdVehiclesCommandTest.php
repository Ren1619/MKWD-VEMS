<?php

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command;

beforeEach(function () {
    config()->set('services.mkwd_ims.base_url', 'http://mkwd-ims.test');
    config()->set('services.mkwd_ims.api_token', 'command-test-secret-token');
    config()->set('services.mkwd_ims.retry_attempts', 3);
    config()->set('services.mkwd_ims.retry_delay_milliseconds', 0);
    Http::preventStrayRequests();
});

test('the command reports synchronization counts and exits successfully', function () {
    Http::fake([
        'http://mkwd-ims.test/api/vehicles' => Http::response(['data' => [amsVehicle()]]),
    ]);

    $this->artisan('mkwd:sync-vehicles')
        ->expectsOutput('created=1 updated=0 unchanged=0 archived=0 reactivated=0 failed=0')
        ->assertExitCode(Command::SUCCESS);
});

test('the command exits unsuccessfully without exposing the bearer token', function () {
    $loggedMessages = [];
    Log::listen(function (MessageLogged $event) use (&$loggedMessages): void {
        $loggedMessages[] = $event->message.' '.json_encode($event->context);
    });

    Http::fake([
        'http://mkwd-ims.test/api/vehicles' => Http::response(status: 401),
    ]);

    $this->artisan('mkwd:sync-vehicles')
        ->expectsOutput('Vehicle synchronization failed.')
        ->doesntExpectOutputToContain('command-test-secret-token')
        ->assertExitCode(Command::FAILURE);

    expect(implode(' ', $loggedMessages))->not->toContain('command-test-secret-token');
    Http::assertSentCount(1);
});
