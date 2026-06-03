<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\CaseFile;
use App\Models\CaseLinkGroup;
use App\Models\User;
use App\Support\CompanyFilter;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CaseLinkService
{
    public function __construct(private AuditService $audit) {}

    /**
     * @param  array<int, int>  $caseIds
     */
    public function linkCases(array $caseIds, User $actor): CaseLinkGroup
    {
        $caseIds = collect($caseIds)->map(fn ($id) => (int) $id)->unique()->values();

        if ($caseIds->count() < 2) {
            throw ValidationException::withMessages([
                'case_ids' => 'Select at least two cases to link as related.',
            ]);
        }

        $cases = CaseFile::query()->whereIn('id', $caseIds)->get();

        if ($cases->count() !== $caseIds->count()) {
            throw ValidationException::withMessages([
                'case_ids' => 'One or more selected cases could not be found.',
            ]);
        }

        foreach ($cases as $case) {
            $this->assertCanLink($actor, $case);
        }

        $companyIds = $cases->pluck('company_id')->unique();
        if ($companyIds->count() > 1) {
            throw ValidationException::withMessages([
                'case_ids' => 'Related cases must belong to the same company.',
            ]);
        }

        $existingGroupIds = $cases->pluck('case_link_group_id')->filter()->unique()->values();

        if ($existingGroupIds->count() > 1) {
            $targetGroupId = (int) $existingGroupIds->first();
            CaseFile::query()
                ->whereIn('case_link_group_id', $existingGroupIds->slice(1)->all())
                ->update(['case_link_group_id' => $targetGroupId]);
            $group = CaseLinkGroup::findOrFail($targetGroupId);
        } elseif ($existingGroupIds->count() === 1) {
            $group = CaseLinkGroup::findOrFail($existingGroupIds->first());
        } else {
            $group = CaseLinkGroup::create(['created_by' => $actor->id]);
        }

        CaseFile::query()
            ->whereIn('id', $caseIds->all())
            ->update(['case_link_group_id' => $group->id]);

        $this->audit->log('case.linked', $group, [
            'case_ids' => $caseIds->all(),
            'references' => $cases->pluck('reference')->all(),
            'linked_by' => $actor->id,
        ]);

        return $group->fresh();
    }

    public function unlinkCase(CaseFile $case, User $actor): void
    {
        $this->assertCanLink($actor, $case);

        if (! $case->case_link_group_id) {
            throw ValidationException::withMessages([
                'case' => 'This case is not linked to any related cases.',
            ]);
        }

        $groupId = $case->case_link_group_id;
        $case->update(['case_link_group_id' => null]);

        $remaining = CaseFile::where('case_link_group_id', $groupId)->count();
        if ($remaining <= 1) {
            CaseFile::where('case_link_group_id', $groupId)->update(['case_link_group_id' => null]);
            CaseLinkGroup::where('id', $groupId)->delete();
        }

        $this->audit->log('case.unlinked', $case, [
            'case_reference' => $case->reference,
            'unlinked_by' => $actor->id,
        ]);
    }

    /** @return Collection<int, CaseFile> */
    public function relatedCasesFor(CaseFile $case): Collection
    {
        if (! $case->case_link_group_id) {
            return collect();
        }

        return CaseFile::query()
            ->where('case_link_group_id', $case->case_link_group_id)
            ->where('id', '!=', $case->id)
            ->with(['stage', 'order.package'])
            ->orderBy('reference')
            ->get();
    }

    private function assertCanLink(User $actor, CaseFile $case): void
    {
        if ($actor->hasRole(UserRole::SuperAdmin) || $actor->hasRole(UserRole::Admin)) {
            return;
        }

        if ($actor->hasRole(UserRole::Client) && CompanyFilter::userCanAccessCompany($actor, $case->company_id)) {
            return;
        }

        throw ValidationException::withMessages([
            'case_ids' => 'You do not have permission to link this case.',
        ]);
    }
}
