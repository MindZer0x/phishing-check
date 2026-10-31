<?php

namespace App\Check\Signals;

use App\Check\SuspectEmail;

/**
 * Finds and normalises the hosts that links in a suspect email point to.
 */
final class Hosts
{
    /**
     * Second-level labels under which country-code TLDs register domains,
     * e.g. amazon.co.uk. Not a full public suffix list, but enough to avoid
     * treating every .co.uk domain as the same domain.
     */
    private const array SECOND_LEVEL_LABELS = ['ac', 'co', 'com', 'edu', 'gov', 'net', 'or', 'org'];

    /**
     * Endings that make link text look like a domain but are file names.
     */
    private const array FILE_EXTENSIONS = ['csv', 'doc', 'docx', 'exe', 'gif', 'htm', 'html', 'jpeg', 'jpg', 'pdf', 'png', 'ppt', 'pptx', 'rar', 'txt', 'xls', 'xlsx', 'zip'];

    /**
     * URLs with a scheme or protocol-relative, as written in plain text, HTML
     * attributes or Markdown, and bare hosts that are clearly meant as links:
     * starting with "www." or followed by a path. Email addresses are skipped.
     */
    private const array LINK_TARGET_PATTERNS = [
        '#(?:https?:)?//[^\s"\'<>]+#iu',
        '#(?<![\w@.\-/])(?:www\.[\p{L}\p{N}.\-]+|[\p{L}\p{N}\-]+(?:\.[\p{L}\p{N}\-]+)+/[^\s"\'<>]*)#iu',
    ];

    /**
     * The sender host and the hosts of all link targets in a suspect email.
     *
     * @return list<string>
     */
    public static function in(SuspectEmail $email): array
    {
        return array_values(array_filter([self::ofAddress($email->senderAddress()), ...self::linkTargetsIn($email->body)]));
    }

    /**
     * The hosts of all link targets in the given text.
     *
     * @return list<string>
     */
    public static function linkTargetsIn(string $text): array
    {
        $hosts = [];

        foreach (self::LINK_TARGET_PATTERNS as $pattern) {
            preg_match_all($pattern, html_entity_decode($text), $matches);

            foreach ($matches[0] as $candidate) {
                $host = self::of(rtrim($candidate, '.,;:!?)]'));

                if ($host !== null) {
                    $hosts[] = $host;
                }
            }
        }

        return array_values(array_unique($hosts));
    }

    /**
     * The lower-case host of a URL or bare domain, or null if the value does
     * not look like one (e.g. link text such as "Hier klicken"). URLs are
     * parsed so that tricks like https://www.paypal.com@evil.example resolve
     * to the host the browser actually opens. Punycode is decoded, so a
     * domain compares equal however it was written.
     */
    public static function of(string $value): ?string
    {
        $value = rtrim(trim($value), '.');

        if (str_starts_with($value, '//')) {
            $value = 'https:'.$value;
        }

        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $value) === 1) {
            $host = parse_url($value, PHP_URL_HOST);

            return preg_match('#^https?://#i', $value) === 1 && is_string($host) && $host !== ''
                ? self::normalise(trim($host, '[]'))
                : null;
        }

        if (preg_match('#^([\p{L}\p{N}-]+(?:\.[\p{L}\p{N}-]+)+)(?::\d+)?(?:[/?\#]\S*)?$#iu', $value, $matches) !== 1) {
            return null;
        }

        return self::normalise($matches[1]);
    }

    /**
     * The host of an email address, e.g. paypal.de for service@paypal.de.
     */
    public static function ofAddress(string $address): ?string
    {
        $at = strrpos($address, '@');

        return $at === false ? null : self::of(substr($address, $at + 1));
    }

    /**
     * The registrable domain of a URL or bare domain, or null if the value
     * does not look like one (e.g. link text such as "Hier klicken").
     */
    public static function registrableDomainOf(string $value): ?string
    {
        $host = self::of($value);

        return $host === null ? null : self::registrableDomain($host);
    }

    /**
     * The registrable domain of a host, e.g. paypal.com for login.paypal.com,
     * or the host itself for an IP address. Null if the host has no plausible
     * top-level domain, e.g. a file name such as bericht.pdf.
     */
    public static function registrableDomain(string $host): ?string
    {
        if (self::isIpAddress($host)) {
            return $host;
        }

        $labels = explode('.', $host);
        $count = count($labels);
        $topLevel = $labels[$count - 1];

        if ($count < 2 || preg_match('/^\p{L}{2,}$/u', $topLevel) !== 1 || in_array($topLevel, self::FILE_EXTENSIONS, true)) {
            return null;
        }

        $registrableLabels = $count >= 3 && strlen($topLevel) === 2 && in_array($labels[$count - 2], self::SECOND_LEVEL_LABELS, true)
            ? 3
            : 2;

        return implode('.', array_slice($labels, -$registrableLabels));
    }

    /**
     * The labels of a host without its public suffix, e.g. login and paypa1
     * for login.paypa1.co.uk.
     *
     * @return list<string>
     */
    public static function labelsBelowPublicSuffix(string $host): array
    {
        $domain = self::isIpAddress($host) ? null : self::registrableDomain($host);

        if ($domain === null) {
            return [];
        }

        $labels = explode('.', $host);
        $suffixLabels = count(explode('.', $domain)) - 1;

        return array_slice($labels, 0, count($labels) - $suffixLabels);
    }

    /**
     * Whether a label could be a top-level domain, e.g. the "com" or "fr" in
     * paypal.com.example.ru.
     */
    public static function looksLikeTopLevelDomain(string $label): bool
    {
        return preg_match('/^(?:\p{L}{2,3}|info)$/u', $label) === 1;
    }

    public static function isIpAddress(string $host): bool
    {
        return filter_var($host, FILTER_VALIDATE_IP) !== false;
    }

    private static function normalise(string $host): string
    {
        $host = rtrim(mb_strtolower($host), '.');
        $decoded = idn_to_utf8($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);

        return is_string($decoded) && $decoded !== '' ? $decoded : $host;
    }
}
