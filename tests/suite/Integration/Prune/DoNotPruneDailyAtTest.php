<?php

use ROTGP\AuthSodium\Test\IntegrationTestCase;
use Illuminate\Console\Scheduling\Schedule;

class DoNotPruneDailyAtTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        config(['authsodium.prune.daily_at' => null]);
    }

    public function test_pruning_is_not_scheduled()
    {
        $schedule = app(Schedule::class);
        $this->assertCount(0, $schedule->events());
    }
}
