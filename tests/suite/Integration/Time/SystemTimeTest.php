<?php

use ROTGP\AuthSodium\Test\IntegrationTestCase;
use ROTGP\AuthSodium\Test\Controllers\FooController;

use Carbon\Carbon;

class SystemTimeTest extends IntegrationTestCase
{
    protected function customizeSetup()
    {
        $this->router()
            ->resource('foos', FooController::class)
            ->middleware('authsodium');

        // config(['app.timezone' => 'Asia/Jerusalem']);
    }

    public function test_that_system_time_is_st_patricks_day()
    {
        $now = Carbon::now();
        $this->assertTrue($now->equalTo($this->epoch));
        $this->assertEquals('UTC', config('app.timezone'));
        $this->assertEquals(1616007320, $now->timestamp);
        $this->assertEquals('2021-03-17 18:55:20', $now->toDateTimeString());
        $this->assertEquals('UTC', $now->timezoneName);
        $this->assertEquals(0, $now->utcOffset());
        $this->assertEquals(2021, $now->year);
        $this->assertEquals('March', $now->monthName);
        $this->assertEquals(17, $now->day);
        $this->assertEquals('Wednesday', $now->dayName);
       
        // https://stackoverflow.com/questions/29684111/how-to-compare-two-carbon-timestamps
        // equalTo()
        // notEqualTo()
        // greaterThan()
        // greaterThanOrEqualTo()
        // lessThan()
        // lessThanOrEqualTo()

        // $carbon = Carbon::now(); //->add(1, 'day');
        // $later = Carbon::now()->add(38, 'seconds');
        // $diff = $later->diffInSeconds(now());
    }

    public function test_that_request_timestamp_is_system_timestamp()
    {
        $this->signed()->request();
        $this->assertEquals($this->getTimestamp(), $this->epoch->timestamp);
    }
}
