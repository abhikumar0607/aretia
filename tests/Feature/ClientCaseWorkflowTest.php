<?php

namespace Tests\Feature;

use App\Support\CaseWorkflow;
use Tests\TestCase;

class ClientCaseWorkflowTest extends TestCase
{
    public function test_client_stage_mapping(): void
    {
        $this->assertSame('order-confirmed', CaseWorkflow::clientStageSlug(CaseWorkflow::SLUG_ASSIGNED));
        $this->assertSame('Order confirmed', CaseWorkflow::clientStageLabel(CaseWorkflow::SLUG_ASSIGNED));

        $this->assertSame('research-started', CaseWorkflow::clientStageSlug(CaseWorkflow::SLUG_RESEARCH_STARTED));
        $this->assertSame('research-started', CaseWorkflow::clientStageSlug(CaseWorkflow::SLUG_QA_DONE));
        $this->assertSame('research-started', CaseWorkflow::clientStageSlug(CaseWorkflow::SLUG_FQA_STARTED));
        $this->assertSame('Research started', CaseWorkflow::clientStageLabel(CaseWorkflow::SLUG_FQA_STARTED));

        $this->assertSame('sent-to-client', CaseWorkflow::clientStageSlug(CaseWorkflow::SLUG_SENT_TO_CLIENT));
        $this->assertSame('Sent to client', CaseWorkflow::clientStageLabel(CaseWorkflow::SLUG_SENT_TO_CLIENT));
    }
}
