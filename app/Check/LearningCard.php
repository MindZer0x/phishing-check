<?php

namespace App\Check;

/**
 * The result shown to the employee after a check.
 */
final readonly class LearningCard
{
    /**
     * @param  list<Signal>  $signals
     */
    private function __construct(
        public RiskLevel $riskLevel,
        public array $signals,
        public bool $heuristicsOnly,
    ) {}

    public static function fromHeuristics(HeuristicAssessment $assessment): self
    {
        return new self(
            riskLevel: $assessment->heuristicFloor,
            signals: $assessment->signals,
            heuristicsOnly: true,
        );
    }

    /**
     * @return array{riskLevel: string, summary: string, signals: list<array{title: string, explanation: string, weight: string, source: string}>, nextSteps: list<string>, heuristicsOnly: bool}
     */
    public function toArray(): array
    {
        return [
            'riskLevel' => $this->riskLevel->value,
            'summary' => $this->riskLevel->summary(),
            'signals' => array_map(fn (Signal $signal): array => [
                'title' => $signal->title(),
                'explanation' => $signal->explanation(),
                'weight' => $signal->weight()->value,
                'source' => 'rules',
            ], $this->signals),
            'nextSteps' => $this->riskLevel->nextSteps(),
            'heuristicsOnly' => $this->heuristicsOnly,
        ];
    }
}
