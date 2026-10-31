<?php

namespace App\Check\Signals;

use App\Check\Signal;
use App\Check\SignalWeight;
use App\Check\SuspectEmail;

final class LinkTextPointsToDifferentDomain implements Signal
{
    public function title(): string
    {
        return 'Linktext und Linkziel passen nicht zusammen';
    }

    public function explanation(): string
    {
        return 'Der sichtbare Text eines Links nennt eine andere Domain als die Adresse, die sich beim Klick tatsächlich öffnet. '
            .'Betrüger tarnen so gefälschte Seiten als bekannte Absender. Fahren Sie mit der Maus über einen Link, um das echte Ziel zu sehen, bevor Sie klicken.';
    }

    public function weight(): SignalWeight
    {
        return SignalWeight::Strong;
    }

    /**
     * Link formats as they appear in a pasted suspect email: HTML anchors,
     * Markdown links and the "text <url>" form mail clients produce when
     * copying a link as plain text. Each pattern captures text, then target.
     */
    private const array LINK_PATTERNS = [
        '/<a\s[^>]*href\s*=\s*["\']?([^"\'\s>]+)["\']?[^>]*>(.*?)<\/a>/is' => [2, 1],
        '/\[([^\]]+)\]\((https?:\/\/[^)\s]+)\)/i' => [1, 2],
        '/(\S+)[ \t]*<(https?:\/\/[^>\s]+)>/i' => [1, 2],
    ];

    public function isFoundIn(SuspectEmail $email): bool
    {
        foreach (self::LINK_PATTERNS as $pattern => [$textGroup, $targetGroup]) {
            preg_match_all($pattern, $email->body, $links, PREG_SET_ORDER);

            foreach ($links as $link) {
                $textDomain = Hosts::registrableDomainOf(html_entity_decode(strip_tags($link[$textGroup])));
                $targetDomain = Hosts::registrableDomainOf(html_entity_decode($link[$targetGroup]));

                if ($textDomain !== null && $targetDomain !== null && $textDomain !== $targetDomain) {
                    return true;
                }
            }
        }

        return false;
    }
}
