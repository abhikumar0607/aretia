<?php

namespace App\Http\Controllers\Shared;

use App\Enums\UserRole;
use App\Events\CaseMessageSent;
use App\Events\CaseMessagesRead;
use App\Http\Controllers\Controller;
use App\Models\CaseFile;
use App\Models\Message;
use App\Models\User;
use App\Services\AuditService;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(Request $request, CaseFile $case): JsonResponse
    {
        $this->authorizeCaseAccess($case);
        $this->authorizeCaseChat($case);

        $messages = $case->messages()
            ->with(['sender', 'recipient', 'caseFile'])
            ->orderBy('created_at')
            ->get()
            ->map(fn (Message $message) => $this->formatMessage($message, $case));

        $partner = $case->chatPartnerFor($request->user());

        return response()->json([
            'messages' => $messages,
            'current_user_id' => $request->user()->id,
            'current_user_name' => $request->user()->name,
            'chat_partner' => $partner ? [
                'id' => $partner->id,
                'name' => $partner->name,
                'email' => $partner->email,
            ] : null,
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
        $recipientId = $this->resolveRecipientId($case, $sender);

        if (! $recipientId) {
            abort(403, 'An analyst must be assigned before messages can be sent.');
        }

        $message = Message::create([
            'case_id' => $case->id,
            'sender_id' => $sender->id,
            'recipient_id' => $recipientId,
            'body' => $data['body'],
        ]);

        $message->load(['sender', 'recipient', 'caseFile']);

        $this->audit->log('message.sent', $case, [
            'sender' => $sender->id,
            'recipient' => $recipientId,
        ]);

        CaseMessageSent::dispatch($message);

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

        $userId = (int) $request->user()->id;
        $now = now();

        $messageIds = Message::query()
            ->where('case_id', $case->id)
            ->where('recipient_id', $userId)
            ->whereNull('read_at')
            ->pluck('id')
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

    private function resolveRecipientId(CaseFile $case, User $sender): ?int
    {
        $case->loadMissing(['assignee', 'order']);

        if ($sender->hasRole(UserRole::Client)) {
            return $case->assigned_to ? (int) $case->assigned_to : null;
        }

        $clientUser = User::query()
            ->where('company_id', $case->company_id)
            ->where('role', UserRole::Client)
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->first();

        if ($clientUser) {
            return (int) $clientUser->id;
        }

        return $case->order?->user_id ? (int) $case->order->user_id : null;
    }

    private function formatMessage(Message $message, ?CaseFile $case = null): array
    {
        $message->loadMissing(['sender', 'recipient', 'caseFile']);
        $caseRef = $message->caseFile?->reference ?? $case?->reference;

        return [
            'id' => $message->id,
            'case_id' => $message->case_id,
            'case_reference' => $caseRef,
            'sender_id' => $message->sender_id,
            'sender_name' => $message->sender->name,
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

        $routeName = match ($role) {
            UserRole::Client->value => 'client.cases.show',
            UserRole::Analyst->value => 'analyst.cases.show',
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

        if ($user->hasRole(UserRole::Client) && (int) $case->company_id === (int) $user->company_id) {
            return;
        }

        if ($user->hasRole(UserRole::Analyst) && (int) $case->assigned_to === (int) $user->id) {
            return;
        }

        abort(403);
    }

    private function authorizeCaseChat(CaseFile $case): void
    {
        $user = auth()->user();

        if ($user->hasRole(UserRole::Admin) || $user->hasRole(UserRole::SuperAdmin)) {
            abort(403, 'Case chat is not available for this role.');
        }

        if (! $case->isChatAvailableFor($user)) {
            abort(403, 'Chat is available after an analyst is assigned to this case.');
        }
    }

}
