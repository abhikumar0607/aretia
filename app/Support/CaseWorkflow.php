<?php

namespace App\Support;

use App\Enums\UserRole;

class CaseWorkflow
{
    public const SLUG_ASSIGNED = 'assigned';
    public const SLUG_RESEARCH_STARTED = 'research-started';
    public const SLUG_RESEARCH_DONE = 'research-done';
    public const SLUG_QA_STARTED = 'qa-started';
    public const SLUG_QA_DONE = 'qa-done';
    public const SLUG_FQA_STARTED = 'fqa-started';
    public const SLUG_SENT_TO_CLIENT = 'sent-to-client';
    public const SLUG_CANCELLED = 'cancelled';

    public const CLIENT_STAGE_ORDER_CONFIRMED = 'order-confirmed';

    public const CLIENT_STAGE_RESEARCH_STARTED = 'research-started';

    public const CLIENT_STAGE_SENT_TO_CLIENT = 'sent-to-client';

    public const CLIENT_STAGE_CANCELLED = 'cancelled';

    /**
     * @return list<string>
     */
    public static function clientStageSlugs(): array
    {
        return [
            self::CLIENT_STAGE_ORDER_CONFIRMED,
            self::CLIENT_STAGE_RESEARCH_STARTED,
            self::CLIENT_STAGE_SENT_TO_CLIENT,
            self::CLIENT_STAGE_CANCELLED,
        ];
    }

    /** @return array<string, string> */
    public static function clientStageOptions(): array
    {
        return [
            self::CLIENT_STAGE_ORDER_CONFIRMED => 'Order confirmed',
            self::CLIENT_STAGE_RESEARCH_STARTED => 'Research started',
            self::CLIENT_STAGE_SENT_TO_CLIENT => 'Sent to client',
            self::CLIENT_STAGE_CANCELLED => 'Cancelled',
        ];
    }

    public static function clientStageSlug(?string $internalSlug): string
    {
        $internalSlug = self::normalizeCurrentSlug($internalSlug);

        return match ($internalSlug) {
            self::SLUG_SENT_TO_CLIENT => self::CLIENT_STAGE_SENT_TO_CLIENT,
            self::SLUG_CANCELLED => self::CLIENT_STAGE_CANCELLED,
            self::SLUG_ASSIGNED => self::CLIENT_STAGE_ORDER_CONFIRMED,
            default => self::CLIENT_STAGE_RESEARCH_STARTED,
        };
    }

    public static function clientStageLabel(?string $internalSlug): string
    {
        return self::clientStageOptions()[self::clientStageSlug($internalSlug)] ?? 'Order confirmed';
    }

    public static function clientStageColor(?string $internalSlug): string
    {
        return match (self::clientStageSlug($internalSlug)) {
            self::CLIENT_STAGE_ORDER_CONFIRMED => '#094FA4',
            self::CLIENT_STAGE_RESEARCH_STARTED => '#3b82f6',
            self::CLIENT_STAGE_SENT_TO_CLIENT => '#059669',
            self::CLIENT_STAGE_CANCELLED => '#dc2626',
            default => '#094FA4',
        };
    }

    /**
     * @return list<string>
     */
    public static function internalSlugsForClientStage(string $clientStageSlug): array
    {
        return match ($clientStageSlug) {
            self::CLIENT_STAGE_ORDER_CONFIRMED => [self::SLUG_ASSIGNED],
            self::CLIENT_STAGE_RESEARCH_STARTED => [
                self::SLUG_RESEARCH_STARTED,
                self::SLUG_RESEARCH_DONE,
                self::SLUG_QA_STARTED,
                self::SLUG_QA_DONE,
                self::SLUG_FQA_STARTED,
            ],
            self::CLIENT_STAGE_SENT_TO_CLIENT => [self::SLUG_SENT_TO_CLIENT],
            self::CLIENT_STAGE_CANCELLED => [self::SLUG_CANCELLED],
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    public static function orderedSlugs(): array
    {
        return [
            self::SLUG_ASSIGNED,
            self::SLUG_RESEARCH_STARTED,
            self::SLUG_RESEARCH_DONE,
            self::SLUG_QA_STARTED,
            self::SLUG_QA_DONE,
            self::SLUG_FQA_STARTED,
            self::SLUG_SENT_TO_CLIENT,
            self::SLUG_CANCELLED,
        ];
    }

    /**
     * @return list<string>
     */
    public static function completedSlugs(): array
    {
        return [self::SLUG_SENT_TO_CLIENT];
    }

    public static function normalizeCurrentSlug(?string $currentSlug): string
    {
        return $currentSlug ?: self::SLUG_ASSIGNED;
    }

    /**
     * @return list<string>
     */
    public static function laneSlugs(UserRole $role): array
    {
        return match ($role) {
            UserRole::Analyst => [self::SLUG_ASSIGNED, self::SLUG_RESEARCH_STARTED, self::SLUG_RESEARCH_DONE],
            UserRole::Qa => [self::SLUG_QA_STARTED, self::SLUG_QA_DONE],
            UserRole::Fqa => [self::SLUG_FQA_STARTED, self::SLUG_SENT_TO_CLIENT],
            default => [],
        };
    }

    /**
     * The stage slug at which a role's lane becomes available.
     */
    public static function unlockAtSlug(UserRole $role): ?string
    {
        return match ($role) {
            UserRole::Analyst => self::SLUG_ASSIGNED,
            UserRole::Qa => self::SLUG_RESEARCH_DONE,
            UserRole::Fqa => self::SLUG_QA_DONE,
            default => null,
        };
    }

    public static function laneEndSlug(UserRole $role): ?string
    {
        return match ($role) {
            UserRole::Analyst => self::SLUG_RESEARCH_DONE,
            UserRole::Qa => self::SLUG_QA_DONE,
            UserRole::Fqa => self::SLUG_SENT_TO_CLIENT,
            default => null,
        };
    }

    public static function isLaneUnlocked(UserRole $role, ?string $currentSlug): bool
    {
        $currentSlug = self::normalizeCurrentSlug($currentSlug);
        $unlock = self::unlockAtSlug($role);
        $end = self::laneEndSlug($role);
        if ($unlock === null || $end === null) {
            return false;
        }

        $order = self::orderedSlugs();
        $currentIndex = array_search($currentSlug, $order, true);
        $unlockIndex = array_search($unlock, $order, true);
        $endIndex = array_search($end, $order, true);

        if ($currentIndex === false || $unlockIndex === false || $endIndex === false) {
            return false;
        }

        // Can act from unlock boundary until the lane ends (inclusive).
        return $currentIndex >= $unlockIndex && $currentIndex <= $endIndex;
    }

    /**
     * Slugs the user can choose in dropdown (current + lane stages once unlocked).
     *
     * @return list<string>
     */
    public static function selectableSlugs(UserRole $role, ?string $currentSlug): array
    {
        return self::employeeSelectableTargetSlugs($role, $currentSlug);
    }

    /**
     * Stages shown in the employee stage dropdown — only this role's lane, never prior teams.
     *
     * @return list<string>
     */
    public static function employeeDropdownSlugs(UserRole $role, ?string $currentSlug): array
    {
        $currentSlug = self::normalizeCurrentSlug($currentSlug);
        $lane = self::laneSlugs($role);

        if ($lane === [] || ! self::isLaneUnlocked($role, $currentSlug)) {
            return [];
        }

        if ($role === UserRole::Analyst) {
            return $lane;
        }

        if (in_array($currentSlug, $lane, true)) {
            $slugs = [$currentSlug];
            foreach (self::allowedNextSlugs($role, $currentSlug) as $next) {
                if (in_array($next, $lane, true)) {
                    $slugs[] = $next;
                }
            }

            return array_values(array_unique($slugs));
        }

        return array_values(array_filter(
            self::allowedNextSlugs($role, $currentSlug),
            fn (string $slug) => in_array($slug, $lane, true),
        ));
    }

    /**
     * Stage slugs an employee may submit when updating a case.
     *
     * @return list<string>
     */
    public static function employeeSelectableTargetSlugs(UserRole $role, ?string $currentSlug): array
    {
        $currentSlug = self::normalizeCurrentSlug($currentSlug);
        $dropdown = self::employeeDropdownSlugs($role, $currentSlug);

        if ($dropdown === []) {
            return [];
        }

        $lane = self::laneSlugs($role);
        $next = array_values(array_filter(
            self::allowedNextSlugs($role, $currentSlug),
            fn (string $slug) => in_array($slug, $lane, true),
        ));

        if (! in_array($currentSlug, $lane, true)) {
            return $next;
        }

        $targets = in_array($currentSlug, $dropdown, true) ? [$currentSlug] : [];

        return array_values(array_unique(array_merge($targets, $next)));
    }

    public static function employeeLaneFrozen(UserRole $role, ?string $currentSlug): bool
    {
        return self::employeeDropdownSlugs($role, $currentSlug) === [];
    }

    /**
     * @return list<string>
     */
    public static function employeeVisibleHistorySlugs(UserRole $role): array
    {
        return self::laneSlugs($role);
    }

    /**
     * @return list<string>
     */
    public static function allowedNextSlugs(UserRole $role, ?string $currentSlug): array
    {
        $currentSlug = self::normalizeCurrentSlug($currentSlug);

        $map = match ($role) {
            UserRole::Analyst => [
                self::SLUG_ASSIGNED => [self::SLUG_RESEARCH_STARTED],
                self::SLUG_RESEARCH_STARTED => [self::SLUG_RESEARCH_DONE],
            ],
            UserRole::Qa => [
                self::SLUG_RESEARCH_DONE => [self::SLUG_QA_STARTED],
                self::SLUG_QA_STARTED => [self::SLUG_QA_DONE],
            ],
            UserRole::Fqa => [
                self::SLUG_QA_DONE => [self::SLUG_FQA_STARTED],
                self::SLUG_FQA_STARTED => [self::SLUG_SENT_TO_CLIENT],
            ],
            default => [],
        };

        return $map[$currentSlug] ?? [];
    }

    public static function canTransition(UserRole $role, ?string $fromSlug, string $toSlug): bool
    {
        $fromSlug = self::normalizeCurrentSlug($fromSlug);

        return in_array($toSlug, self::allowedNextSlugs($role, $fromSlug), true);
    }
}

