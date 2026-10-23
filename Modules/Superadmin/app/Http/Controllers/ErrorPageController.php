<?php

namespace Modules\Superadmin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ErrorPageSetting;
use App\Services\ErrorPageManager;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ErrorPageController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', ErrorPageSetting::class);

        return view('superadmin::sistem.error-page.index');
    }

    public function preview(int $statusCode): Response
    {
        Gate::authorize('viewAny', ErrorPageSetting::class);
        abort_unless(in_array($statusCode, ErrorPageManager::SUPPORTED_CODES, true), 404);

        return response()->view("errors.{$statusCode}");
    }
}
