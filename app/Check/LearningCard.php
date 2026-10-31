<?php

namespace App\Check;

/**
 * The result shown to the employee after a check.
 */
final readonly class LearningCard
{
    /**
     * @param  list<array{title: string, explanation: string, weight: string, source: string}>  $signals
     * @param  list<string>  $nextSteps
     */
    private function __construct(
        public RiskLevel $riskLevel,
        public string $summary,
        public array $signals,
        public array $nextSteps,
        public bool $heuristicsOnly,
    ) {}

    public static function fromHeuristics(HeuristicAssessment $assessment): self
    {
        return new self(
            riskLevel: $assessment->heuristicFloor,
            summary: $assessment->heuristicFloor->summary(),
            signals: self::ruleSignals($assessment),
            nextSteps: $assessment->heuristicFloor->nextSteps(),
            heuristicsOnly: true,
        );
    }

    /**
     * Combines both analyses. The risk level never drops below the heuristic
     * floor; when the floor overrules Claude, so does its summary, so a
     * prompt injection cannot contradict the risk level in the text either.
     * The fixed next steps always come before Claude's specific ones.
     */
    public static function fromHeuristicsAndAi(HeuristicAssessment $heuristics, AiAssessment $ai): self
    {
        $riskLevel = $heuristics->heuristicFloor->atLeast($ai->riskLevel);

        return new self(
            riskLevel: $riskLevel,
            summary: $riskLevel === $ai->riskLevel ? $ai->summary : $riskLevel->summary(),
            signals: [
                ...self::ruleSignals($heuristics),
                ...array_map(fn (AiSignal $signal): array => [
                    'title' => $signal->title,
                    'explanation' => $signal->explanation,
                    'weight' => $signal->weight->value,
                    'source' => 'ai',
                ], $ai->signals),
            ],
            nextSteps: [...$riskLevel->nextSteps(), ...$ai->nextSteps],
            heuristicsOnly: false,
        );
    }

    /**
     * @return array{riskLevel: string, summary: string, signals: list<array{title: string, explanation: string, weight: string, source: string}>, nextSteps: list<string>, heuristicsOnly: bool}
     */
    public function toArray(): array
    {
        return [
            'riskLevel' => $this->riskLevel->value,
            'summary' => $this->summary,
            'signals' => $this->signals,
            'nextSteps' => $this->nextSteps,
            'heuristicsOnly' => $this->heuristicsOnly,
        ];
    }

    /**
     * @return list<array{title: string, explanation: string, weight: string, source: string}>
     */
    private static function ruleSignals(HeuristicAssessment $assessment): array
    {
        return array_map(fn (Signal $signal): array => [
            'title' => $signal->title(),
            'explanation' => $signal->explanation(),
            'weight' => $signal->weight()->value,
            'source' => 'rules',
        ], $assessment->signals);
    }
}
