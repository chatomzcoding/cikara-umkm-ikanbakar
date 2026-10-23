<?php

namespace Modules\Superadmin\Http\Controllers;

use App\Http\Controllers\Controller;

class RoleController extends Controller
{
    public function index()
    {
        return view('superadmin::sistem.role.index');
    }
}
