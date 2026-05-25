<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(): View
    {
        $logs = AuditLog::with([
            'user',
            'auditable' => fn ($morphTo) => $morphTo->morphWith([
                \App\Models\CaseFile::class => ['company', 'assignee', 'stage'],
                \App\Models\Order::class => ['company', 'package'],
                \App\Models\Report::class => ['caseFile'],
                \App\Models\Message::class => ['caseFile'],
            ]),
        ])
            ->latest()
            ->paginate(config('portal.per_page'))
            ->withQueryString();

        return view('admin.audit.index', compact('logs'));
    }
}
