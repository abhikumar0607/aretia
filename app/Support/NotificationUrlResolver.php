<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\CaseFile;
use App\Models\User;

class NotificationUrlResolver
{
    /** Resolve a stored notification link for the current recipient (role-correct portal prefix). */
    public static function resolve(User $user, array $data): ?string
    {
        $stored = $data['url'] ?? null;
        $type = $data['type'] ?? null;
        $caseId = isset($data['case_id']) ? (int) $data['case_id'] : null;

        if ($caseId > 0 && $type === 'case_assigned') {
            return self::caseShowUrl($user, $caseId);
        }

        if ($user->isEmployee() && $stored && ($caseId > 0 || ($caseId = self::caseIdFromUrl($stored)))) {
            $rewritten = self::caseShowUrl($user, $caseId);
            if ($rewritten) {
                return $rewritten;
            }
        }

        return self::rewriteStaffPrefix($user, $stored);
    }

    /** Swap /admin/ ↔ /superadmin/ so staff notifications match the signed-in role. */
    private static function rewriteStaffPrefix(User $user, ?string $url): ?string
    {
        if ($url === null || $url === '') {
            return $url;
        }

        if (! $user->hasRole(UserRole::Admin) && ! $user->hasRole(UserRole::SuperAdmin)) {
            return $url;
        }

        $parts = parse_url($url);
        $path = $parts['path'] ?? '';

        if (! preg_match('#^/(admin|superadmin)(/.*)?$#', $path, $matches)) {
            return $url;
        }

        $targetPrefix = $user->role->value;
        if ($matches[1] === $targetPrefix) {
            return $url;
        }

        $parts['path'] = '/'.$targetPrefix.($matches[2] ?? '');

        return self::buildUrl($parts);
    }

    /** @param  array<string, mixed>  $parts */
    private static function buildUrl(array $parts): string
    {
        $url = '';

        if (isset($parts['scheme'])) {
            $url .= $parts['scheme'].'://';
        }

        if (isset($parts['host'])) {
            $url .= $parts['host'];
        }

        if (isset($parts['port'])) {
            $url .= ':'.$parts['port'];
        }

        $url .= $parts['path'] ?? '';

        if (isset($parts['query'])) {
            $url .= '?'.$parts['query'];
        }

        if (isset($parts['fragment'])) {
            $url .= '#'.$parts['fragment'];
        }

        return $url;
    }

    private static function caseShowUrl(User $user, int $caseId): ?string
    {
        $case = CaseFile::query()->find($caseId);
        if (! $case) {
            return null;
        }

        if ($user->hasRole(UserRole::Client)) {
            if (! CompanyFilter::userCanAccessCompany($user, $case->company_id)) {
                return null;
            }

            return route('client.cases.show', $case);
        }

        if ($user->hasRole(UserRole::Admin) || $user->hasRole(UserRole::SuperAdmin)) {
            return route($user->role->value.'.cases.show', $case);
        }

        if ($user->isEmployee()) {
            return PortalRoute::caseShowRoute($user, $case);
        }

        return null;
    }

    private static function caseIdFromUrl(string $url): int
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';

        if (preg_match('#/(?:analyst|qa|fqa)/cases/(\d+)#', $path, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }
}
