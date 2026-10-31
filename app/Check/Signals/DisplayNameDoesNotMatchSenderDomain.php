<?php

namespace App\Check\Signals;

use App\Check\Signal;
use App\Check\SignalWeight;
use App\Check\SuspectEmail;

final readonly class DisplayNameDoesNotMatchSenderDomain implements Signal
{
    /**
     * @param  list<string>  $brands  well-known brand names, e.g. "deutsche bank"
     * @param  list<string>  $freeMailDomains  registrable domains anyone can get an address at
     * @param  list<string>  $organisationWords  words that make a display name look like an organisation
     */
    public function __construct(
        private array $brands,
        private array $freeMailDomains,
        private array $organisationWords,
    ) {}

    public function title(): string
    {
        return 'Absendername passt nicht zur Absenderadresse';
    }

    public function explanation(): string
    {
        return 'Der angezeigte Name gibt sich als Unternehmen oder Organisation aus, die E-Mail kommt aber von einer Adresse, die nicht dazugehört – zum Beispiel „Sparkasse“ von einer Gmail-Adresse. '
            .'Den Namen kann jeder frei wählen, entscheidend ist die Adresse dahinter. Prüfen Sie, von welcher Domain die E-Mail wirklich kommt.';
    }

    public function weight(): SignalWeight
    {
        return SignalWeight::Weak;
    }

    public function isFoundIn(SuspectEmail $email): bool
    {
        $name = mb_strtolower($email->senderName());
        $host = Hosts::ofAddress($email->senderAddress());
        $domain = $host === null ? null : Hosts::registrableDomain($host);

        if ($name === '' || $host === null || $domain === null) {
            return false;
        }

        return $this->namesAnotherDomain($name, $domain)
            || $this->namesBrandMissingFromHost($name, $host)
            || (in_array($domain, $this->freeMailDomains, true) && $this->soundsLikeOrganisation($name));
    }

    /**
     * Display names such as "service@paypal.com" or "www.paypal.com".
     */
    private function namesAnotherDomain(string $name, string $domain): bool
    {
        preg_match_all('/(?:@|\bwww\.)([\p{L}\p{N}.-]+)/u', $name, $matches);

        foreach ($matches[1] as $named) {
            $namedDomain = Hosts::registrableDomainOf($named);

            if ($namedDomain !== null && $namedDomain !== $domain) {
                return true;
            }
        }

        return false;
    }

    /**
     * A brand counts as matching if its name appears anywhere in the sender
     * host, ignoring spaces and hyphens: "Deutsche Bank" fits deutsche-bank.de.
     */
    private function namesBrandMissingFromHost(string $name, string $host): bool
    {
        $compactHost = $this->withoutSeparators($host);

        foreach ($this->brands as $brand) {
            $wordPattern = '/(?<![\p{L}\p{N}])'.str_replace('\ ', '[\s-]*', preg_quote($brand, '/')).'(?![\p{L}\p{N}])/u';

            if (preg_match($wordPattern, $name) === 1 && ! str_contains($compactHost, $this->withoutSeparators($brand))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Organisation words count at the end of a word, so that German compounds
     * such as "Kundenservice" match but surnames such as "Kassebaum" do not.
     */
    private function soundsLikeOrganisation(string $name): bool
    {
        foreach ($this->organisationWords as $word) {
            if (preg_match('/'.preg_quote($word, '/').'(?![\p{L}\p{N}])/u', $name) === 1) {
                return true;
            }
        }

        return false;
    }

    private function withoutSeparators(string $value): string
    {
        return (string) preg_replace('/[^\p{L}\p{N}]/u', '', $value);
    }
}
