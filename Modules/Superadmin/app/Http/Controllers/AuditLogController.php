<?php

namespace Modules\Superadmin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Gate;

class AuditLogController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', AuditLog::class);

        return view('superadmin::sistem.audit-log.index');
    }
}
