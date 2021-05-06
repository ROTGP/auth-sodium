<?php

use ROTGP\AuthSodium\Test\IntegrationTestCase;
use ROTGP\AuthSodium\Test\Controllers\FooController;

use Carbon\Carbon;

class SystemTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class)
            ->middleware('authsodium');
    }

    public function test_that_system_time_is_st_patricks_day()
    {
        $now = Carbon::now();
        $this->assertTrue($now->equalTo($this->epoch));
        $this->assertEquals('UTC', config('app.timezone'));
        $this->assertEquals(1616007320, $now->timestamp);
        $this->assertEquals(1616007320000, intval($now->getPreciseTimestamp(3)));
        $this->assertEquals('2021-03-17 18:55:20', $now->toDateTimeString());
        $this->assertEquals('UTC', $now->timezoneName);
        $this->assertEquals(0, $now->utcOffset());
        $this->assertEquals(2021, $now->year);
        $this->assertEquals('March', $now->monthName);
        $this->assertEquals(17, $now->day);
        $this->assertEquals('Wednesday', $now->dayName);
    }

    public function test_that_request_timestamp_is_system_timestamp()
    {
        $this->signed()->request();
        $this->assertEquals($this->getTimestamp(), intval($this->epoch->getPreciseTimestamp(3)));
    }
}
