<?php

namespace App\Check;

use App\Check\Signals\LinkTextPointsToDifferentDomain;

/**
 * Runs the rule-based signals against a suspect email.
 */
final class HeuristicAnalysis
{
    /**
     * @param  list<Signal>  $signals
     */
    public function __construct(
        private array $signals = [
            new LinkTextPointsToDifferentDomain,
        ],
    ) {}

    public function analyse(SuspectEmail $email): HeuristicAssessment
    {
        $found = array_values(array_filter(
            $this->signals,
            fn (Signal $signal): bool => $signal->isFoundIn($email),
        ));

        return new HeuristicAssessment(
            signals: $found,
            heuristicFloor: RiskLevel::fromSignalWeights(...array_map(fn (Signal $signal): SignalWeight => $signal->weight(), $found)),
        );
    }
}
