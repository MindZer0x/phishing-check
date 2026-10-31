<?php

namespace App\Check\Signals;

use App\Check\Signal;
use App\Check\SignalWeight;
use App\Check\SuspectEmail;

final readonly class DangerousAttachmentType implements Signal
{
    /**
     * @param  list<string>  $extensions  file extensions without the dot
     */
    public function __construct(private array $extensions) {}

    public function title(): string
    {
        return 'Gefährlicher Dateityp';
    }

    public function explanation(): string
    {
        return 'Die E-Mail nennt eine Datei mit einer Endung wie .exe, .js, .docm oder .zip. '
            .'Solche Dateien können beim Öffnen Schadsoftware starten oder verstecken. Rechnungen, Bewerbungen und Lieferscheine kommen in der Regel als PDF, nicht als Programm oder Archiv.';
    }

    public function weight(): SignalWeight
    {
        return SignalWeight::Strong;
    }

    public function isFoundIn(SuspectEmail $email): bool
    {
        $extensions = implode('|', array_map(fn (string $extension): string => preg_quote($extension, '/'), $this->extensions));

        return preg_match('/\.(?:'.$extensions.')(?![\p{L}\p{N}_])/iu', $email->text()) === 1;
    }
}
