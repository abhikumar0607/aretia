<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(): View
    {
        $logs = AuditLog::with('user')->latest()->paginate(config('portal.per_page'))->withQueryString();

        return view('admin.audit.index', compact('logs'));
    }
}
