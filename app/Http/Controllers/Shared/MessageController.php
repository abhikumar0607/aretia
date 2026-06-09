<?php

namespace App\Http\Controllers\Shared;

use App\Enums\MessageChannel;
use App\Enums\Permission;
use App\Enums\UserRole;
use App\Events\CaseMessageSent;
use App\Events\CaseMessagesRead;
use App\Http\Controllers\Controller;
use App\Models\CaseFile;
use App\Models\Message;
use App\Models\User;
use App\Services\AuditService;
use App\Services\CaseChatService;
use App\Services\CaseMessageNotifyService;
use App\Support\CompanyFilter;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(
        private AuditService $audit,
        private CaseMessageNotifyService $messageNotify,
        private CaseChatService $caseChat,
    ) {}

    public function index(Request $request, CaseFile $case): JsonResponse
    {
        $this->authorizeCaseAccess($case);
        $this->authorizeCaseChat($case);

        $user = $request->user();
        $query = $case->messages()->with(['sender', 'recipient', 'caseFile'])->orderBy('created_at');
        $this->caseChat->applyCaseThreadVisibility($query, $case, $user);

        $messages = $query->get()->map(fn (Message $message) => $this->formatMessage($message, $case));

        return response()->json([
            'messages' => $messages,
            'current_user_id' => $user->id,
            'current_user_name' => $user->name,
            'thread' => [
                'title' => $this->caseChat->threadTitle($case),
                'subtitle' => $this->caseChat->threadSubtitle($case),
            ],
            'case' => [
                'id' => $case->id,
                'reference' => $case->reference,
                'company_name' => $case->company?->name,
            ],
        ]);
    }

    public function store(Request $request, CaseFile $case): JsonResponse|RedirectResponse
    {
        $this->authorizeCaseAccess($case);
        $this->authorizeCaseChat($case);

        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);

        $sender = $request->user();

        if (! $this->caseChat->canSendCaseChat($sender)) {
            abort(403, 'You do not have permission to post in case chat.');
        }

        $message = Message::create([
            'case_id' => $case->id,
            'sender_id' => $sender->id,
            'recipient_id' => null,
            'channel' => MessageChannel::Client,
            'body' => $data['body'],
        ]);

        $message->load(['sender', 'recipient', 'caseFile']);

        $this->audit->log('message.sent', $case, [
            'sender' => $sender->id,
            'channel' => MessageChannel::Client->value,
        ]);

        CaseMessageSent::dispatch($message);
        $this->messageNotify->notify($message);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => $this->formatMessage($message, $case),
            ]);
        }

        return Toast::to($this->caseShowUrl($case), 'Message sent successfully.');
    }

    public function markRead(Request $request, CaseFile $case): JsonResponse
    {
        $this->authorizeCaseAccess($case);
        $this->authorizeCaseChat($case);

        $user = $request->user();
        $userId = (int) $user->id;
        $now = now();

        $query = Message::query()
            ->where('case_id', $case->id)
            ->whereNull('read_at')
            ->where('sender_id', '!=', $userId);

        $this->caseChat->applyCaseThreadVisibility($query, $case, $user);

        $messageIds = $query->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($messageIds !== []) {
            Message::query()
                ->whereIn('id', $messageIds)
                ->update(['read_at' => $now]);

            CaseMessagesRead::dispatch($case->id, $messageIds, $userId, $now->toIso8601String());
        }

        return response()->json([
            'read_message_ids' => $messageIds,
            'read_at' => $now->toIso8601String(),
        ]);
    }

    private function formatMessage(Message $message, ?CaseFile $case = null): array
    {
        $message->loadMissing(['sender', 'recipient', 'caseFile']);
        $caseRef = $message->caseFile?->reference ?? $case?->reference;

        return [
            'id' => $message->id,
            'case_id' => $message->case_id,
            'case_reference' => $caseRef,
            'channel' => $message->channel?->value,
            'sender_id' => $message->sender_id,
            'sender_name' => $message->sender->name,
            'sender_role' => $message->sender->role?->value,
            'sender_role_label' => $message->sender->role?->label(),
            'recipient_id' => $message->recipient_id,
            'recipient_name' => $message->recipient?->name,
            'body' => $message->body,
            'created_at' => $message->created_at->toIso8601String(),
            'created_at_label' => $message->created_at->format('d M Y, H:i'),
            'read_at' => $message->read_at?->toIso8601String(),
            'is_read' => $message->read_at !== null,
        ];
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
            UserRole::Client->value => 'client.cases.show',
            UserRole::SuperAdmin->value => 'superadmin.cases.show',
            default => 'admin.cases.show',
        };

        return route($routeName, $case);
    }

    private function authorizeCaseAccess(CaseFile $case): void
    {
        $user = auth()->user();

        if ($user->hasRole(UserRole::Admin) || $user->hasRole(UserRole::SuperAdmin)) {
            return;
        }

        if ($user->hasRole(UserRole::Client) && CompanyFilter::userCanAccessCompany($user, $case->company_id)) {
            return;
        }

        if ($user->isEmployee() && $case->hasAnalyst($user)) {
            return;
        }

        abort(403);
    }

    private function authorizeCaseChat(CaseFile $case): void
    {
        $user = auth()->user();

        if (! $user->hasPermission(Permission::ChatClient)) {
            abort(403, 'You do not have permission to use case chat.');
        }

        if (! $this->caseChat->canUseCaseChat($case, $user)) {
            abort(403, 'Case chat is not available on this case.');
        }
    }
}
