<?php

namespace App\Check;

/**
 * What the AI analysis made of a suspect email.
 */
final readonly class AiAssessment
{
    /**
     * @param  list<AiSignal>  $signals
     * @param  list<string>  $nextSteps  at most two next steps specific to the suspect email
     */
    public function __construct(
        public RiskLevel $riskLevel,
        public string $summary,
        public array $signals,
        public array $nextSteps,
    ) {}
}
