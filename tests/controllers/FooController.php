<?php

namespace ROTGP\AuthSodium\Test\Controllers;

use ROTGP\AuthSodium\Test\Models\Foo;

use Auth;

class FooController extends BaseController
{
    public function index()
    {
        // dd('xxxxx', Auth::guard('authsodium')->user());
        // dd('mk', $id, optional(Auth::guard('authsodium')->user())->toArray());
        return $this->respond(Foo::all());
    }

    public function store()
    {
        // if (!Auth::guard('authsodium')->check())
        //     $this->errorResponse(400, ['nope' => 'no authorized user']);
                
        $payload = request()->post();
        $payload['user_id'] = Auth::guard('authsodium')->id();
        
        // Auth::authenticateSignature();
        // Auth::invalidateUser();
        
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
