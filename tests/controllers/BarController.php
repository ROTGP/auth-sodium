<?php

namespace ROTGP\AuthSodium\Test\Controllers;

use ROTGP\AuthSodium\Test\Models\Bar;

class BarController extends BaseController
{
    public function index()
    {
        return Foo::all();
    }

    public function show($id)
    {
        //
    }

    public function update($id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}
