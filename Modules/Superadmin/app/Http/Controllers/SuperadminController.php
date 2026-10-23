<?php

namespace Modules\Superadmin\Http\Controllers;

use App\Http\Controllers\Controller;

class SuperadminController extends Controller
{
    public function index()
    {
        return view('superadmin::index');
    }
}
