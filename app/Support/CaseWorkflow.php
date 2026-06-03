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
        $currentSlug = self::normalizeCurrentSlug($currentSlug);

        $slugs = [$currentSlug];

        if (self::isLaneUnlocked($role, $currentSlug)) {
            $slugs = array_values(array_unique(array_merge($slugs, self::laneSlugs($role))));
        }

        return $slugs;
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

