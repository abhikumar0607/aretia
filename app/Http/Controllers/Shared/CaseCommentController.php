<?php

namespace App\Http\Controllers\Shared;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\CaseComment;
use App\Models\CaseFile;
use App\Services\AuditService;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CaseCommentController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function store(Request $request, CaseFile $case): JsonResponse|RedirectResponse
    {
        $this->authorizeInternalCaseAccess($case);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $comment = CaseComment::create([
            'case_id' => $case->id,
            'user_id' => (int) $request->user()->id,
            'body' => trim($data['body']),
        ]);

        $this->audit->log('case.comment_added', $case, [
            'comment_id' => $comment->id,
            'author' => $request->user()->name,
        ]);

        return Toast::to($this->caseShowUrl($case), 'Comment added.');
    }

    private function authorizeInternalCaseAccess(CaseFile $case): void
    {
        $user = auth()->user();

        if ($user->hasRole(UserRole::Client)) {
            abort(403);
        }

        if ($user->hasRole(UserRole::Admin) || $user->hasRole(UserRole::SuperAdmin)) {
            return;
        }

        if ($user->isEmployee() && $case->hasAnalyst($user)) {
            return;
        }

        abort(403);
    }

    private function caseShowUrl(CaseFile $case): string
    {
        $role = auth()->user()->role;
        if ($role instanceof UserRole) {
            $role = $role->value;
        }

        if (UserRole::tryFrom($role)?->isEmployeeRole()) {
            return \App\Support\PortalRoute::route('cases.show', $case, true, auth()->user());
        }

        $routeName = match ($role) {
            UserRole::SuperAdmin->value => 'superadmin.cases.show',
            default => 'admin.cases.show',
        };

        return route($routeName, $case);
    }
}
