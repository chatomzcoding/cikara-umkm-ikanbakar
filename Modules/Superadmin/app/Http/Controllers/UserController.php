<?php

namespace Modules\Superadmin\Http\Controllers;

use App\Http\Controllers\Controller;

class UserController extends Controller
{
    public function index()
    {
        return view('superadmin::sistem.user.index');
    }
}
