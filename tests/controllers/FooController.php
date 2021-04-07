<?php

namespace ROTGP\AuthSodium\Test\Controllers;

use ROTGP\AuthSodium\Test\Models\Foo;

use Auth;

class FooController extends BaseController
{
    public function index()
    {
        // $carbon = now(); //->add(1, 'day');

        // $later = now()->add(38, 'seconds');

        // $diff = $later->diffInSeconds(now());
        
        // dd(
        //     'yasss',
        //     config('app.timezone'),
        //     $diff . ' seconds',
        //     $later->timestamp,
        //     $carbon->timestamp,
        //     $carbon->toDateTimeString(),
        //     // $carbon->timezone,
        //     $carbon->timezoneName,
        //     $carbon->utcOffset(),
        //     $carbon->year,
        //     $carbon->monthName,
        //     $carbon->day . ' ' . $carbon->dayName
        // );
        
        // try {
        //     $foo = Auth::id();
        // } catch (\Exception $e) {
        //     dd($e->getMessage());
        // }
        // dd('foo', $foo);
        // dd('xxxxx', Auth::guard('authsodium')->user());
        // dd('mk', $id, optional(Auth::guard('authsodium')->user())->toArray());
        return $this->respond(Foo::all());
    }

    public function store()
    {
        // dd('here we are', Auth::guard('authsodium')->user());
        // dd('here we are', Auth::user());

        $payload = request()->post();
        $payload['user_id'] = Auth::guard('authsodium')->id();
        
        if ($payload['user_id'] === null)
            $this->validationErrorResponse(['error_key' => 'user id can not be null']);
        
        return $this->respond(Foo::create($payload));
    }

    public function show($id)
    {
        return $this->respond(Foo::find($id));
    }

    public function update($id)
    {
        $foo = Foo::find($id);
        $foo->fill(request()->all())->save();
        return $this->respond($foo);
    }

    public function destroy($id)
    {
        Foo::find($id)->delete();
        return $this->respond(null);
    }
}
