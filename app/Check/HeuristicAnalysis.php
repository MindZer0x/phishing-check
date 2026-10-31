<?php

namespace App\Check;

use App\Check\Signals\Brand;
use App\Check\Signals\BrandNameInUnrelatedDomain;
use App\Check\Signals\DisplayNameDoesNotMatchSenderDomain;
use App\Check\Signals\LinkTargetIsShortenerOrIpAddress;
use App\Check\Signals\LinkTextPointsToDifferentDomain;
use App\Check\Signals\LookalikeDomain;

/**
 * Runs the rule-based signals against a suspect email.
 */
final class HeuristicAnalysis
{
    /**
     * @param  list<Signal>  $signals
     */
    public function __construct(private array $signals) {}

    /**
     * All rule-based signals, using the lists from config/check.php.
     *
     * @param  array{brands: list<string>, brand_owned_domains: list<string>, free_mail_domains: list<string>, organisation_words: list<string>, url_shorteners: list<string>}  $config
     */
    public static function fromConfig(array $config): self
    {
        $brands = array_map(fn (string $name): Brand => new Brand($name), $config['brands']);

        return new self([
            new LinkTextPointsToDifferentDomain,
            new LookalikeDomain($brands),
            new BrandNameInUnrelatedDomain($brands, $config['brand_owned_domains']),
            new LinkTargetIsShortenerOrIpAddress($config['url_shorteners']),
            new DisplayNameDoesNotMatchSenderDomain($brands, $config['free_mail_domains'], $config['organisation_words']),
        ]);
    }

    public function analyse(SuspectEmail $email): HeuristicAssessment
    {
        $found = array_values(array_filter(
            $this->signals,
            fn (Signal $signal): bool => $signal->isFoundIn($email),
        ));

        return new HeuristicAssessment(
            signals: $found,
            heuristicFloor: RiskLevel::fromSignalWeights(...array_map(fn (Signal $signal): SignalWeight => $signal->weight(), $found)),
        );
    }
}
