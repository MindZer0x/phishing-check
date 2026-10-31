<?php

namespace App\Check\Signals;

use App\Check\Signal;
use App\Check\SignalWeight;
use App\Check\SuspectEmail;

final readonly class PressureLanguage implements Signal
{
    /**
     * @param  list<string>  $phrases  urgency and pressure phrases
     */
    public function __construct(private array $phrases) {}

    public function title(): string
    {
        return 'Zeitdruck oder Drohung';
    }

    public function explanation(): string
    {
        return 'Die E-Mail drängt zur Eile oder droht mit Folgen, etwa einer Sperrung oder einer knappen Frist. '
            .'Angreifer erzeugen so Druck, damit Sie handeln, bevor Sie nachdenken. Seriöse Absender lassen Ihnen Zeit und drohen nicht per E-Mail.';
    }

    public function weight(): SignalWeight
    {
        return SignalWeight::Weak;
    }

    public function isFoundIn(SuspectEmail $email): bool
    {
        return Phrases::anyIn($email->text(), $this->phrases);
    }
}
