<?php

namespace App\Check;

/**
 * The verdict of a check (ADR-0004). There is deliberately no numeric score.
 */
enum RiskLevel: string
{
    case Unauffaellig = 'unauffaellig';
    case Verdaechtig = 'verdaechtig';
    case Gefaehrlich = 'gefaehrlich';

    /**
     * The heuristic floor: no signal is Unauffällig, one weak signal is
     * Verdächtig, any strong signal or two weak signals are Gefährlich.
     */
    public static function fromSignalWeights(SignalWeight ...$weights): self
    {
        $strong = count(array_filter($weights, fn (SignalWeight $weight): bool => $weight === SignalWeight::Strong));
        $weak = count($weights) - $strong;

        return match (true) {
            $strong >= 1, $weak >= 2 => self::Gefaehrlich,
            $weak === 1 => self::Verdaechtig,
            default => self::Unauffaellig,
        };
    }

    /**
     * The higher of two risk levels, so the AI analysis can raise the
     * heuristic floor but never lower it (ADR-0001). Relies on the cases
     * being declared from lowest to highest.
     */
    public function atLeast(self $other): self
    {
        $order = array_flip(array_column(self::cases(), 'value'));

        return $order[$other->value] > $order[$this->value] ? $other : $this;
    }

    /**
     * Short summary for the learning card when Claude's does not apply.
     */
    public function summary(): string
    {
        return match ($this) {
            self::Unauffaellig => 'Wir haben in dieser E-Mail keine typischen Warnsignale gefunden. Das ist keine Garantie: Bleiben Sie aufmerksam, wenn Sie etwas ungewöhnlich finden.',
            self::Verdaechtig => 'Diese E-Mail enthält ein Warnsignal. Prüfen Sie sie sorgfältig, bevor Sie reagieren.',
            self::Gefaehrlich => 'Diese E-Mail zeigt deutliche Merkmale eines Phishing-Versuchs. Reagieren Sie nicht darauf.',
        };
    }

    /**
     * Fixed "Was jetzt tun?" advice for this risk level.
     *
     * @return list<string>
     */
    public function nextSteps(): array
    {
        return match ($this) {
            self::Unauffaellig => [
                'Sie können die E-Mail normal bearbeiten.',
                'Geben Sie Passwörter oder Zahlungsdaten trotzdem nur auf Seiten ein, die Sie selbst aufgerufen haben.',
                'Wenn Ihnen später etwas merkwürdig vorkommt, fragen Sie Ihre IT-Abteilung.',
            ],
            self::Verdaechtig => [
                'Klicken Sie vorerst auf keine Links und öffnen Sie keine Anhänge.',
                'Fragen Sie beim Absender über einen bekannten Weg nach, zum Beispiel per Telefon – nicht per Antwort auf diese E-Mail.',
                'Im Zweifel leiten Sie die E-Mail an Ihre IT-Abteilung weiter.',
            ],
            self::Gefaehrlich => [
                'Klicken Sie auf keine Links und öffnen Sie keine Anhänge.',
                'Antworten Sie nicht und geben Sie keine Daten ein.',
                'Melden Sie die E-Mail Ihrer IT-Abteilung und löschen Sie sie danach.',
                'Falls Sie bereits geklickt oder Daten eingegeben haben: Ändern Sie sofort das betroffene Passwort und informieren Sie Ihre IT-Abteilung.',
            ],
        };
    }
}
