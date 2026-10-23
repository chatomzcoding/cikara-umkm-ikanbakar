<?php

namespace Modules\Superadmin\Http\Controllers;

use App\Http\Controllers\Controller;

class DataPokokController extends Controller
{
    public function index()
    {
        return view('superadmin::sistem.data-pokok.index');
    }
}
