<?php

use ROTGP\AuthSodium\Test\IntegrationTestCase;
use Illuminate\Console\Scheduling\Schedule;

class PruneDailyAtTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        config(['authsodium.prune.daily_at' => '11:15']);
    }

    public function test_pruning_is_scheduled_at_expected_time()
    {
        $schedule = app(Schedule::class);
        $events = array_map(function ($event) use ($schedule) {
            return [
                'cron' => $event->expression,
                'command' => $this->parseArtisanCommand($event->command),
            ];
        }, $schedule->events());

        $this->assertCount(1, $schedule->events());
        $this->assertCount(1, $events);
        $this->assertEquals('15 11 * * *', $events[0]['cron']);
        $this->assertEquals("'artisan' authsodium:prune", $events[0]['command']);
    }
}
