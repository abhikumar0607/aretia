<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Support\CaseWorkflow;
use Tests\TestCase;

class EmployeeStageFreezeTest extends TestCase
{
    public function test_qa_frozen_until_research_done(): void
    {
        $this->assertTrue(CaseWorkflow::employeeLaneFrozen(UserRole::Qa, CaseWorkflow::SLUG_RESEARCH_STARTED));
        $this->assertTrue(CaseWorkflow::employeeLaneFrozen(UserRole::Qa, CaseWorkflow::SLUG_ASSIGNED));
        $this->assertFalse(CaseWorkflow::employeeLaneFrozen(UserRole::Qa, CaseWorkflow::SLUG_RESEARCH_DONE));
    }

    public function test_qa_dropdown_excludes_analyst_stages(): void
    {
        $this->assertSame([], CaseWorkflow::employeeDropdownSlugs(UserRole::Qa, CaseWorkflow::SLUG_RESEARCH_STARTED));

        $dropdown = CaseWorkflow::employeeDropdownSlugs(UserRole::Qa, CaseWorkflow::SLUG_RESEARCH_DONE);
        $this->assertSame([CaseWorkflow::SLUG_QA_STARTED], $dropdown);
        $this->assertNotContains(CaseWorkflow::SLUG_RESEARCH_DONE, $dropdown);

        $dropdownAtQa = CaseWorkflow::employeeDropdownSlugs(UserRole::Qa, CaseWorkflow::SLUG_QA_STARTED);
        $this->assertEqualsCanonicalizing(
            [CaseWorkflow::SLUG_QA_STARTED, CaseWorkflow::SLUG_QA_DONE],
            $dropdownAtQa,
        );
    }

    public function test_fqa_frozen_until_qa_done(): void
    {
        $this->assertTrue(CaseWorkflow::employeeLaneFrozen(UserRole::Fqa, CaseWorkflow::SLUG_QA_STARTED));
        $this->assertFalse(CaseWorkflow::employeeLaneFrozen(UserRole::Fqa, CaseWorkflow::SLUG_QA_DONE));

        $dropdown = CaseWorkflow::employeeDropdownSlugs(UserRole::Fqa, CaseWorkflow::SLUG_QA_DONE);
        $this->assertSame([CaseWorkflow::SLUG_FQA_STARTED], $dropdown);
        $this->assertNotContains(CaseWorkflow::SLUG_QA_DONE, $dropdown);
    }

    public function test_analyst_past_lane_cannot_update(): void
    {
        $this->assertTrue(CaseWorkflow::employeeLaneFrozen(UserRole::Analyst, CaseWorkflow::SLUG_QA_STARTED));
        $this->assertSame([], CaseWorkflow::employeeSelectableTargetSlugs(UserRole::Analyst, CaseWorkflow::SLUG_QA_STARTED));
    }

    public function test_analyst_lane_stays_available_until_research_done(): void
    {
        $dropdown = CaseWorkflow::employeeDropdownSlugs(UserRole::Analyst, CaseWorkflow::SLUG_RESEARCH_STARTED);
        $this->assertEqualsCanonicalizing(
            [CaseWorkflow::SLUG_ASSIGNED, CaseWorkflow::SLUG_RESEARCH_STARTED, CaseWorkflow::SLUG_RESEARCH_DONE],
            $dropdown,
        );
    }
}
