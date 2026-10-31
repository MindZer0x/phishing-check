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
     * @param  list<Brand>  $brands
     */
    public function __construct(private array $brands) {}

    public function title(): string
    {
        return 'Täuschend ähnliche Domain';
    }

    public function explanation(): string
    {
        return 'Eine Adresse in dieser E-Mail ahmt eine bekannte Domain nach: zum Beispiel paypa1.com statt paypal.com, oder paypal.com.konto-check.net, das nur am Anfang wie paypal.com aussieht. Manchmal stecken auch Buchstaben aus anderen Alphabeten darin, die wie normale Buchstaben aussehen. '
            .'Auf den ersten Blick ist der Unterschied kaum zu erkennen. Rufen Sie die Seite des Unternehmens lieber selbst über die Ihnen bekannte Adresse auf.';
    }

    public function weight(): SignalWeight
    {
        return SignalWeight::Strong;
    }

    public function isFoundIn(SuspectEmail $email): bool
    {
        foreach (Hosts::in($email) as $host) {
            $labels = Hosts::labelsBelowPublicSuffix($host);

            if ($this->embedsBrandDomain($labels)) {
                return true;
            }

            foreach ($labels as $label) {
                if ($this->isLookalike($label)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * A brand's own domain placed in front of another domain, such as
     * paypal.com.secure-login.example.ru, so that the start of the address
     * looks right.
     *
     * @param  list<string>  $labels
     */
    private function embedsBrandDomain(array $labels): bool
    {
        for ($i = 0; $i < count($labels) - 2; $i++) {
            if (in_array(str_replace('-', '', $labels[$i]), $this->compactBrands(), true)
                && Hosts::looksLikeTopLevelDomain($labels[$i + 1])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function compactBrands(): array
    {
        return array_map(fn (Brand $brand): string => $brand->compact(), $this->brands);
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
            foreach ($this->compactBrands() as $brand) {
                if ($this->imitates($part, $brand)) {
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
