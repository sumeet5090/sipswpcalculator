<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\InsightPayload;
use PHPUnit\Framework\TestCase;

class InsightPayloadTest extends TestCase
{
    public function testFromArrayWithStandardData(): void
    {
        $data = [
            'calc_type' => 'SIP',
            'amount' => 5000,
            'duration' => 15,
            'step_up_pct' => 10,
            'currency' => 'USD',
            'pdf_downloaded' => true,
            'interest_rate' => 12.5,
            'sip_amount' => 5000,
            'sip_duration' => 15,
            'sip_step_up' => 10,
            'swp_enabled' => false,
            'final_corpus' => 2500000,
            'total_invested' => 900000,
            'wealth_multiplier' => 2.78,
            'goal_mode' => 'grow',
            'device_type' => 'desktop',
            'table_viewed' => 1,
            'pdf_has_custom_name' => 0,
            'inflation_enabled' => 1,
            'interaction_count' => 3,
            'preset_clicked' => 'aggressive',
            'exit_action' => 'pdf_download',
        ];

        $payload = InsightPayload::fromArray($data);

        $this->assertSame('SIP', $payload->calcType);
        $this->assertSame(5000.0, $payload->amount);
        $this->assertSame(15, $payload->duration);
        $this->assertSame(10.0, $payload->stepUpPct);
        $this->assertSame('USD', $payload->currency);
        $this->assertTrue($payload->pdfDownloaded);
        $this->assertSame(12.5, $payload->interestRate);
        $this->assertSame(5000.0, $payload->sipAmount);
        $this->assertSame(15, $payload->sipDuration);
        $this->assertSame(10.0, $payload->sipStepUp);
        $this->assertSame(0, $payload->swpEnabled);
        $this->assertSame(2500000.0, $payload->finalCorpus);
        $this->assertSame(900000.0, $payload->totalInvested);
        $this->assertSame(2.78, $payload->wealthMultiplier);
        $this->assertSame('grow', $payload->goalMode);
        $this->assertSame('desktop', $payload->deviceType);
        $this->assertSame(1, $payload->tableViewed);
        $this->assertSame(0, $payload->pdfHasCustomName);
        $this->assertSame(1, $payload->inflationEnabled);
        $this->assertSame(3, $payload->interactionCount);
        $this->assertSame('aggressive', $payload->presetClicked);
        $this->assertSame('pdf_download', $payload->exitAction);
    }

    public function testFromArrayWithMalformedAndMissingFields(): void
    {
        $data = [
            'amount' => 'invalid_number',
            'duration' => 'ten_years',
            'swp_enabled' => 'yes',
            'pdf_downloaded' => 'true',
            'inflation_enabled' => 1,
            'table_viewed' => '0',
            'interaction_count' => -5,
            'currency' => '   gbp   ',
        ];

        $payload = InsightPayload::fromArray($data);

        $this->assertSame('SIP', $payload->calcType);
        $this->assertSame(0.0, $payload->amount);
        $this->assertSame(0, $payload->duration);
        $this->assertSame('GBP', $payload->currency);
        $this->assertTrue($payload->pdfDownloaded);
        $this->assertSame(1, $payload->swpEnabled);
        $this->assertSame(0, $payload->tableViewed);
        $this->assertSame(1, $payload->inflationEnabled);
        $this->assertSame(1, $payload->interactionCount); // Clamped to >= 1
        $this->assertNull($payload->interestRate);
        $this->assertNull($payload->finalCorpus);
        $this->assertSame('none', $payload->presetClicked);
        $this->assertSame('calc_only', $payload->exitAction);
    }

    public function testFromArrayClampsExcessivelyLongStrings(): void
    {
        $data = [
            'calc_type' => str_repeat('A', 100),
            'currency' => str_repeat('B', 50),
            'preset_clicked' => str_repeat('C', 200),
            'exit_action' => str_repeat('D', 200),
            'goal_mode' => str_repeat('E', 100),
            'device_type' => str_repeat('F', 100),
        ];

        $payload = InsightPayload::fromArray($data);

        $this->assertSame(32, mb_strlen($payload->calcType));
        $this->assertSame(10, mb_strlen($payload->currency));
        $this->assertSame(64, mb_strlen($payload->presetClicked));
        $this->assertSame(64, mb_strlen($payload->exitAction));
        $this->assertSame(32, mb_strlen($payload->goalMode ?? ''));
        $this->assertSame(32, mb_strlen($payload->deviceType ?? ''));
    }
}
