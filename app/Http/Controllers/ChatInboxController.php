<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\CaseFile;
use App\Models\Message;
use App\Support\CaseMessageVisibility;
use App\Support\CompanyFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatInboxController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->ensureChatInboxRole($user);

        $messages = $this->inboxQuery($user)
            ->whereNull('read_at')
            ->with(['sender', 'caseFile.company'])
            ->latest()
            ->limit(25)
            ->get()
            ->map(fn (Message $message) => $this->formatInboxItem($message, $user));

        return response()->json([
            'unread_count' => $this->unreadCount($user),
            'messages' => $messages,
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->ensureChatInboxRole($user);

        $this->inboxQuery($user)->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['unread_count' => 0]);
    }

    private function inboxQuery($user)
    {
        $query = Message::query();

        if ($user->hasRole(UserRole::Client)) {
            CaseMessageVisibility::applyClientInbox($query, $user);
        } elseif ($user->isEmployee()) {
            $query
                ->where('recipient_id', $user->id)
                ->whereHas('caseFile', fn ($q) => $q->forAnalyst($user->id));
        } else {
            $query->where('recipient_id', $user->id);
        }

        return $query;
    }

    private function unreadCount($user): int
    {
        return $this->inboxQuery($user)->whereNull('read_at')->count();
    }

    private function formatInboxItem(Message $message, $user): array
    {
        $case = $message->caseFile;

        $sender = $message->sender;

        return [
            'id' => $message->id,
            'case_id' => $message->case_id,
            'case_reference' => $case?->reference,
            'sender_name' => $sender->name,
            'sender_avatar' => $sender->avatarUrl(),
            'sender_initial' => mb_strtoupper(mb_substr($sender->name, 0, 1)),
            'preview' => \Illuminate\Support\Str::limit($message->body, 80),
            'url' => $case ? $this->chatUrl($case, $user).'?chat=1' : null,
            'read_at' => $message->read_at?->toIso8601String(),
            'created_at' => $message->created_at->diffForHumans(),
        ];
    }

    private function chatUrl(CaseFile $case, $user): string
    {
        if ($user->hasRole(UserRole::Client)) {
            return route('client.cases.show', $case);
        }

        return \App\Support\PortalRoute::caseShowRoute($user, $case);
    }

    private function ensureChatInboxRole($user): void
    {
        if ($user->hasRole(UserRole::Client)
            || $user->isEmployee()
            || $user->hasRole(UserRole::Admin)
            || $user->hasRole(UserRole::SuperAdmin)) {
            return;
        }

        abort(403);
    }
}
