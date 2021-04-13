<?php

namespace ROTGP\AuthSodium\Test\Controllers;

use ROTGP\AuthSodium\Test\Models\Foo;

use Auth;

class FooController extends BaseController
{
    public function index()
    {
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
