<?php

namespace App\Check\Signals;

use App\Check\Signal;
use App\Check\SignalWeight;
use App\Check\SuspectEmail;

final readonly class LinkTargetIsShortenerOrIpAddress implements Signal
{
    /**
     * @param  list<string>  $urlShorteners  domains of URL shortening services
     */
    public function __construct(private array $urlShorteners) {}

    public function title(): string
    {
        return 'Link mit verschleiertem Ziel';
    }

    public function explanation(): string
    {
        return 'Ein Link führt über einen Kurzlink-Dienst oder direkt zu einer IP-Adresse statt zu einer normalen Internetadresse. '
            .'So können Sie vor dem Klick nicht erkennen, welche Seite sich tatsächlich öffnet. Seriöse Unternehmen verlinken in der Regel auf ihre eigene, erkennbare Domain.';
    }

    public function weight(): SignalWeight
    {
        return SignalWeight::Weak;
    }

    public function isFoundIn(SuspectEmail $email): bool
    {
        foreach (Hosts::linkTargetsIn($email->body) as $host) {
            if (Hosts::isIpAddress($host) || in_array(Hosts::registrableDomain($host), $this->urlShorteners, true)) {
                return true;
            }
        }

        return false;
    }
}
