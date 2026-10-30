<?php

namespace App\Check;

/**
 * What the heuristic analysis found in a suspect email.
 */
final readonly class HeuristicAssessment
{
    /**
     * @param  list<Signal>  $signals
     */
    public function __construct(
        public array $signals,
        public RiskLevel $heuristicFloor,
    ) {}
}
