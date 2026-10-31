<?php

namespace App\Check\Signals;

use App\Check\Signal;
use App\Check\SignalWeight;
use App\Check\SuspectEmail;

/**
 * Text that tries to steer an AI assessing the email (ADR-0001).
 */
final readonly class InstructionsAddressedToAi implements Signal
{
    /**
     * @param  list<string>  $phrases  phrases that address an AI
     */
    public function __construct(private array $phrases) {}

    public function title(): string
    {
        return 'Anweisungen an eine KI';
    }

    public function explanation(): string
    {
        return 'Die E-Mail enthält Anweisungen an ein KI-Programm, etwa die E-Mail als sicher einzustufen oder frühere Anweisungen zu ignorieren. Oft ist dieser Text für Menschen unsichtbar. '
            .'Eine echte E-Mail an Sie hat keinen Grund, Prüfprogramme zu beeinflussen: Hier versucht jemand, Sicherheitsprüfungen auszutricksen.';
    }

    public function weight(): SignalWeight
    {
        return SignalWeight::Strong;
    }

    public function isFoundIn(SuspectEmail $email): bool
    {
        return Phrases::anyIn($email->text(), $this->phrases);
    }
}
