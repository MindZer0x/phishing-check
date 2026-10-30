<?php

namespace App\Check;

/**
 * A specific, explainable warning sign that can be found in a suspect email.
 */
interface Signal
{
    /**
     * Short German title shown on the learning card.
     */
    public function title(): string;

    /**
     * German explanation of why this is a warning sign.
     */
    public function explanation(): string;

    public function weight(): SignalWeight;

    public function isFoundIn(SuspectEmail $email): bool;
}
