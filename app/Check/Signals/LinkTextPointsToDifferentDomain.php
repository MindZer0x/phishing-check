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

    /**
     * Second-level labels under which country-code TLDs register domains,
     * e.g. amazon.co.uk. Not a full public suffix list, but enough to avoid
     * treating every .co.uk domain as the same domain.
     */
    private const array SECOND_LEVEL_LABELS = ['ac', 'co', 'com', 'edu', 'gov', 'net', 'or', 'org'];

    public function isFoundIn(SuspectEmail $email): bool
    {
        foreach (self::LINK_PATTERNS as $pattern => [$textGroup, $targetGroup]) {
            preg_match_all($pattern, $email->body, $links, PREG_SET_ORDER);

            foreach ($links as $link) {
                $textDomain = $this->domainOf(html_entity_decode(strip_tags($link[$textGroup])));
                $targetDomain = $this->domainOf(html_entity_decode($link[$targetGroup]));

                if ($textDomain !== null && $targetDomain !== null && $textDomain !== $targetDomain) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Endings that make link text look like a domain but are file names.
     */
    private const array FILE_EXTENSIONS = ['csv', 'doc', 'docx', 'exe', 'gif', 'htm', 'html', 'jpeg', 'jpg', 'pdf', 'png', 'ppt', 'pptx', 'rar', 'txt', 'xls', 'xlsx', 'zip'];

    /**
     * The registrable domain of a URL or bare domain, or null if the value
     * does not look like one (e.g. link text such as "Hier klicken").
     */
    private function domainOf(string $value): ?string
    {
        $host = $this->hostOf(rtrim(trim($value), '.'));

        if ($host === null) {
            return null;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return $host;
        }

        $labels = explode('.', $host);
        $count = count($labels);
        $topLevel = $labels[$count - 1];

        if ($count < 2 || preg_match('/^[a-z]{2,}$/', $topLevel) !== 1 || in_array($topLevel, self::FILE_EXTENSIONS, true)) {
            return null;
        }

        $registrableLabels = $count >= 3 && strlen($topLevel) === 2 && in_array($labels[$count - 2], self::SECOND_LEVEL_LABELS, true)
            ? 3
            : 2;

        return implode('.', array_slice($labels, -$registrableLabels));
    }

    /**
     * URLs are parsed so that tricks like https://www.paypal.com@evil.example
     * resolve to the host the browser actually opens.
     */
    private function hostOf(string $value): ?string
    {
        if (str_starts_with($value, '//')) {
            $value = 'https:'.$value;
        }

        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $value) === 1) {
            $host = parse_url($value, PHP_URL_HOST);

            return preg_match('#^https?://#i', $value) === 1 && is_string($host) && $host !== ''
                ? rtrim(strtolower($host), '.')
                : null;
        }

        if (preg_match('#^([a-z0-9-]+(?:\.[a-z0-9-]+)+)(?::\d+)?(?:[/?\#]\S*)?$#i', $value, $matches) !== 1) {
            return null;
        }

        return strtolower($matches[1]);
    }
}
