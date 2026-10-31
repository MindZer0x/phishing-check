<?php

namespace App\Check\Signals;

use App\Check\Signal;
use App\Check\SignalWeight;
use App\Check\SuspectEmail;

/**
 * Weak on purpose: genuine companies also use domains such as
 * sparkasse-koelnbonn.de, and without a list of every brand's official
 * domains they cannot be told apart from paypal-secure.example.ru.
 */
final readonly class BrandNameInUnrelatedDomain implements Signal
{
    /**
     * Brands shorter than this only count as a separate word in a domain,
     * not written together with others: "ing" is part of booking.com.
     */
    private const int MIN_LENGTH_WRITTEN_TOGETHER = 5;

    /**
     * @param  list<Brand>  $brands
     * @param  list<string>  $brandOwnedDomains  registrable domains brands use themselves
     */
    public function __construct(
        private array $brands,
        private array $brandOwnedDomains,
    ) {}

    public function title(): string
    {
        return 'Markenname in fremder Domain';
    }

    public function explanation(): string
    {
        return 'Eine Adresse in dieser E-Mail enthält den Namen einer bekannten Marke, ist aber nicht die Domain der Marke selbst – zum Beispiel paypal-sicherheit.com statt paypal.com. '
            .'Betrüger bauen Markennamen gern in eigene Domains ein, damit ein Link vertrauenswürdig wirkt. Manche Unternehmen nutzen solche Adressen auch selbst, prüfen Sie im Zweifel über die Ihnen bekannte Adresse des Unternehmens.';
    }

    public function weight(): SignalWeight
    {
        return SignalWeight::Weak;
    }

    public function isFoundIn(SuspectEmail $email): bool
    {
        foreach (Hosts::in($email) as $host) {
            $labels = Hosts::labelsBelowPublicSuffix($host);

            if ($labels === [] || $this->isBrandOwned($host, $labels)) {
                continue;
            }

            foreach ($labels as $label) {
                foreach ($this->brands as $brand) {
                    if ($brand->isNamedIn($label) || $this->isWrittenTogetherWith($label, $brand)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * The brand's own domain (paypal.de, deutsche-bank.de) or a domain from
     * the configured list, together with all their subdomains.
     *
     * @param  non-empty-list<string>  $labels
     */
    private function isBrandOwned(string $host, array $labels): bool
    {
        $registrableLabel = str_replace('-', '', $labels[count($labels) - 1]);

        foreach ($this->brands as $brand) {
            if ($registrableLabel === $brand->compact()) {
                return true;
            }
        }

        return in_array(Hosts::registrableDomain($host), $this->brandOwnedDomains, true);
    }

    /**
     * paypalsecure or meinamazon: the brand at the start or end of a label.
     */
    private function isWrittenTogetherWith(string $label, Brand $brand): bool
    {
        $compactLabel = str_replace('-', '', $label);
        $compactBrand = $brand->compact();

        return strlen($compactBrand) >= self::MIN_LENGTH_WRITTEN_TOGETHER
            && (str_starts_with($compactLabel, $compactBrand) || str_ends_with($compactLabel, $compactBrand));
    }
}
