<?php

namespace ROTGP\AuthSodium\Test\Controllers;

use ROTGP\AuthSodium\Test\Models\Foo;

use Auth;

class FooController extends BaseController
{
    public function index()
    {
       
        // dd('mk', $id, optional(Auth::guard('authsodium')->user())->toArray());
        return $this->respond(Foo::all());
    }

    public function store()
    {
        return $this->respond(Foo::create(request()->all()));
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
