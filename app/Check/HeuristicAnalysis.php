<?php

namespace App\Check;

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
     * @param  array{brands: list<string>, free_mail_domains: list<string>, organisation_words: list<string>, url_shorteners: list<string>}  $config
     */
    public static function fromConfig(array $config): self
    {
        return new self([
            new LinkTextPointsToDifferentDomain,
            new LookalikeDomain($config['brands']),
            new LinkTargetIsShortenerOrIpAddress($config['url_shorteners']),
            new DisplayNameDoesNotMatchSenderDomain($config['brands'], $config['free_mail_domains'], $config['organisation_words']),
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
