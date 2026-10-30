<?php

use App\Check\HeuristicAnalysis;
use App\Check\RiskLevel;
use App\Check\Signal;
use App\Check\SignalWeight;
use App\Check\SuspectEmail;

function suspectEmailWithBody(string $body): SuspectEmail
{
    return new SuspectEmail(
        sender: 'Service <service@example.com>',
        subject: 'Ihr Konto',
        body: $body,
    );
}

test('a link whose text names a different domain than its target is a strong signal', function (string $body) {
    $assessment = (new HeuristicAnalysis)->analyse(suspectEmailWithBody($body));

    expect($assessment->signals)->toHaveCount(1)
        ->and($assessment->signals[0]->weight())->toBe(SignalWeight::Strong)
        ->and($assessment->signals[0]->title())->toBe('Linktext und Linkziel passen nicht zusammen')
        ->and($assessment->heuristicFloor)->toBe(RiskLevel::Gefaehrlich);
})->with([
    'HTML link with URL text' => 'Bitte bestätigen Sie: <a href="https://paypal-login.example.ru/verify">https://www.paypal.com</a>',
    'HTML link with bare domain text' => '<a href="http://203.0.113.7/login">www.paypal.com</a>',
    'target only starts with the real domain' => '<a href="https://paypal.com.example.ru/">paypal.com</a>',
    'Markdown link' => '[www.sparkasse.de](https://sparkasse-sicherheit.example.com/login)',
    'target hidden behind userinfo' => '<a href="https://www.paypal.com@paypal-login.example.ru/">www.paypal.com</a>',
    'unquoted target' => '<a href=https://paypal-login.example.ru/verify>www.paypal.com</a>',
    'protocol-relative target' => '<a href="//paypal-login.example.ru/verify">www.paypal.com</a>',
    'text with trailing dot' => '<a href="https://paypal-login.example.ru/verify">www.paypal.com.</a>',
    'plain-text link as copied from a mail client' => "Jetzt anmelden: www.dhl.de <https://dhl-paket.example.net/track>\nDanke",
]);

test('links whose text and target belong to the same domain are not a signal', function (string $body) {
    $assessment = (new HeuristicAnalysis)->analyse(suspectEmailWithBody($body));

    expect($assessment->signals)->toBeEmpty()
        ->and($assessment->heuristicFloor)->toBe(RiskLevel::Unauffaellig);
})->with([
    'identical host' => '<a href="https://www.paypal.com/login">https://www.paypal.com</a>',
    'www prefix only in text' => '<a href="https://paypal.com/login">www.paypal.com</a>',
    'subdomain of the same domain' => '<a href="https://login.paypal.com/verify">paypal.com</a>',
    'country-code second-level domain' => '<a href="https://shop.amazon.co.uk/orders">amazon.co.uk</a>',
    'file name as text' => '<a href="https://drive.example.com/file/123">bericht.pdf</a>',
    'version number as text' => 'Neu in Version 2.0 <https://docs.example.com/changelog>',
    'text without a domain' => '<a href="https://example.ru/verify">Hier klicken</a>',
    'no links at all' => 'Hallo, anbei die Unterlagen für morgen.',
]);

/**
 * A signal that is always found, standing in for signals of a given weight
 * that later slices add.
 */
function alwaysFoundSignal(SignalWeight $weight): Signal
{
    return new readonly class($weight) implements Signal
    {
        public function __construct(private SignalWeight $weight) {}

        public function title(): string
        {
            return 'Testsignal';
        }

        public function explanation(): string
        {
            return 'Nur für Tests.';
        }

        public function weight(): SignalWeight
        {
            return $this->weight;
        }

        public function isFoundIn(SuspectEmail $email): bool
        {
            return true;
        }
    };
}

test('the heuristic floor follows the signal weights', function (array $weights, RiskLevel $expected) {
    $analysis = new HeuristicAnalysis(array_map(alwaysFoundSignal(...), $weights));

    expect($analysis->analyse(suspectEmailWithBody('Hallo'))->heuristicFloor)->toBe($expected);
})->with([
    'no signal' => [[], RiskLevel::Unauffaellig],
    'one weak signal' => [[SignalWeight::Weak], RiskLevel::Verdaechtig],
    'two weak signals' => [[SignalWeight::Weak, SignalWeight::Weak], RiskLevel::Gefaehrlich],
    'three weak signals' => [[SignalWeight::Weak, SignalWeight::Weak, SignalWeight::Weak], RiskLevel::Gefaehrlich],
    'one strong signal' => [[SignalWeight::Strong], RiskLevel::Gefaehrlich],
    'one strong and one weak signal' => [[SignalWeight::Strong, SignalWeight::Weak], RiskLevel::Gefaehrlich],
]);
