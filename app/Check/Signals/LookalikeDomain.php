<?php

namespace App\Check\Signals;

use App\Check\Signal;
use App\Check\SignalWeight;
use App\Check\SuspectEmail;

final readonly class LookalikeDomain implements Signal
{
    /**
     * Characters that look like Latin letters (homoglyphs) and the letter
     * they imitate: Cyrillic and Greek letters, digits and letter pairs.
     * "i" and "l" are confused in many fonts, e.g. netfIix for netflix.
     */
    private const array HOMOGLYPHS = [
        'а' => 'a', 'ԁ' => 'd', 'е' => 'e', 'һ' => 'h', 'і' => 'i', 'ј' => 'j', 'к' => 'k', 'ӏ' => 'l',
        'о' => 'o', 'р' => 'p', 'ԛ' => 'q', 'с' => 'c', 'ѕ' => 's', 'у' => 'y', 'ԝ' => 'w', 'х' => 'x',
        'α' => 'a', 'β' => 'b', 'ε' => 'e', 'ι' => 'i', 'κ' => 'k', 'ν' => 'v', 'ο' => 'o', 'ρ' => 'p',
        'τ' => 't', 'υ' => 'u', 'χ' => 'x',
        '0' => 'o', '1' => 'l', '3' => 'e', '4' => 'a', '5' => 's', '7' => 't',
        'i' => 'l', 'rn' => 'm', 'vv' => 'w',
    ];

    /**
     * @param  list<string>  $brands  well-known brand names, e.g. "deutsche bank"
     */
    public function __construct(private array $brands) {}

    public function title(): string
    {
        return 'Täuschend ähnliche Domain';
    }

    public function explanation(): string
    {
        return 'Eine Adresse in dieser E-Mail ahmt eine bekannte Domain nach, zum Beispiel paypa1.com statt paypal.com, oder sie enthält Buchstaben aus anderen Alphabeten, die wie normale Buchstaben aussehen. '
            .'Auf den ersten Blick ist der Unterschied kaum zu erkennen. Rufen Sie die Seite des Unternehmens lieber selbst über die Ihnen bekannte Adresse auf.';
    }

    public function weight(): SignalWeight
    {
        return SignalWeight::Strong;
    }

    public function isFoundIn(SuspectEmail $email): bool
    {
        $hosts = [Hosts::ofAddress($email->senderAddress()), ...Hosts::linkTargetsIn($email->body)];

        foreach (array_filter($hosts) as $host) {
            foreach ($this->labelsBelowPublicSuffix($host) as $label) {
                if ($this->isLookalike($label)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * The labels of a host without its public suffix, e.g. login and paypa1
     * for login.paypa1.co.uk.
     *
     * @return list<string>
     */
    private function labelsBelowPublicSuffix(string $host): array
    {
        $domain = Hosts::isIpAddress($host) ? null : Hosts::registrableDomain($host);

        if ($domain === null) {
            return [];
        }

        $labels = explode('.', $host);
        $suffixLabels = count(explode('.', $domain)) - 1;

        return array_slice($labels, 0, count($labels) - $suffixLabels);
    }

    /**
     * A label mixing Latin with lookalike scripts, or a label (or a part of
     * it between hyphens) that is not a brand but imitates one.
     */
    private function isLookalike(string $label): bool
    {
        if (preg_match('/\p{Latin}/u', $label) === 1 && preg_match('/[\p{Cyrillic}\p{Greek}]/u', $label) === 1) {
            return true;
        }

        $parts = array_unique([str_replace('-', '', $label), ...explode('-', $label)]);

        foreach ($parts as $part) {
            foreach ($this->brands as $brand) {
                if ($this->imitates($part, str_replace(' ', '', $brand))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Typosquatting: the same letters once homoglyphs are replaced, allowing
     * for a doubled or dropped repeated letter (paypall, sparkase). Other
     * one-letter changes are deliberately not caught: they also turn brands
     * into ordinary words and names, such as telekom into telecom.
     */
    private function imitates(string $part, string $brand): bool
    {
        if ($part === $brand) {
            return false;
        }

        return $this->withoutRepeats(strtr($part, self::HOMOGLYPHS)) === $this->withoutRepeats(strtr($brand, self::HOMOGLYPHS));
    }

    private function withoutRepeats(string $value): string
    {
        return (string) preg_replace('/(.)\1+/u', '$1', $value);
    }
}
