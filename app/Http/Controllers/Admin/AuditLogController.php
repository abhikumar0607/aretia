<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\AuditLogFilters;
use App\Support\CompanyFilter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = AuditLog::with([
            'user',
            'auditable' => fn ($morphTo) => $morphTo->morphWith([
                \App\Models\CaseFile::class => ['company', 'assignee', 'stage', 'order'],
                \App\Models\Order::class => ['company', 'package'],
                \App\Models\Report::class => ['caseFile.order'],
                \App\Models\Message::class => ['caseFile.order'],
                \App\Models\Document::class => ['documentable'],
            ]),
        ])
            ->tap(fn ($query) => AuditLogFilters::apply($query, $request))
            ->latest()
            ->paginate(config('portal.per_page'))
            ->withQueryString();

        $companyOptions = CompanyFilter::optionsForUser($request->user());

        return view('admin.audit.index', compact('logs', 'companyOptions'));
    }
}
