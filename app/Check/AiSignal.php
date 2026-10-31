<?php

namespace App\Check;

/**
 * A signal contributed by the AI analysis rather than found by a rule.
 */
final readonly class AiSignal
{
    public function __construct(
        public string $title,
        public string $explanation,
        public SignalWeight $weight,
    ) {}
}
