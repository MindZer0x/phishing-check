<?php

use App\Check\HeuristicAnalysis;
use App\Check\HeuristicAssessment;
use App\Check\RiskLevel;
use App\Check\Signal;
use App\Check\SignalWeight;
use App\Check\SuspectEmail;

function suspectEmailWithBody(string $body): SuspectEmail
{
    return suspectEmailFrom('Service <service@example.com>', $body);
}

function suspectEmailFrom(string $sender, string $body = 'Hallo, anbei die Unterlagen für morgen.'): SuspectEmail
{
    return new SuspectEmail(
        sender: $sender,
        subject: 'Ihr Konto',
        body: $body,
    );
}

/**
 * The heuristic analysis with the shipped configuration (config/check.php).
 */
function heuristicAnalysis(): HeuristicAnalysis
{
    return HeuristicAnalysis::fromConfig(require __DIR__.'/../../../config/check.php');
}

/**
 * @return array<string, SignalWeight> signal weight by title
 */
function signalsIn(HeuristicAssessment $assessment): array
{
    $found = [];

    foreach ($assessment->signals as $signal) {
        $found[$signal->title()] = $signal->weight();
    }

    return $found;
}

test('a link whose text names a different domain than its target is a strong signal', function (string $body) {
    $assessment = heuristicAnalysis()->analyse(suspectEmailWithBody($body));

    // Some targets also trigger other signals, e.g. a raw IP address.
    expect(signalsIn($assessment))->toHaveKey('Linktext und Linkziel passen nicht zusammen', SignalWeight::Strong)
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
    $assessment = heuristicAnalysis()->analyse(suspectEmailWithBody($body));

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

test('a link to a URL shortener or a raw IP address is a weak signal', function (string $body) {
    $assessment = heuristicAnalysis()->analyse(suspectEmailWithBody($body));

    expect(signalsIn($assessment))->toBe(['Link mit verschleiertem Ziel' => SignalWeight::Weak])
        ->and($assessment->heuristicFloor)->toBe(RiskLevel::Verdaechtig);
})->with([
    'shortener as plain URL' => 'Ihr Paket wartet: https://bit.ly/3xYzAbc',
    'shortener without scheme' => 'Details unter tinyurl.com/paket-dhl',
    'shortener subdomain' => 'Hier entlang: https://www.bit.ly/abc',
    'shortener as HTML link' => '<a href="https://t.co/AbC123">Jetzt ansehen</a>',
    'shortener as Markdown link' => '[Rechnung öffnen](https://is.gd/rechnung)',
    'shortener in upper case' => 'Klicken Sie hier: HTTPS://BIT.LY/ABC',
    'IPv4 address as plain URL' => 'Bitte melden Sie sich an: http://203.0.113.7/login',
    'IPv4 address with port' => '<a href="http://198.51.100.23:8080/konto">Zum Konto</a>',
    'IPv4 address without scheme' => 'Anmeldung: 203.0.113.7/login',
    'IPv6 address' => 'Login: http://[2001:db8::1]/login',
]);

test('ordinary links and numbers are not taken for shorteners or IP addresses', function (string $body) {
    expect(heuristicAnalysis()->analyse(suspectEmailWithBody($body))->signals)->toBeEmpty();
})->with([
    'regular link' => 'Alle Infos auf https://www.example.com/news',
    'domain that only contains a shortener name' => 'Siehe https://notbit.ly.example.com/info',
    'version number' => 'Neu in Version 2.0.1.4 – jetzt aktualisieren.',
    'IP address mentioned without a link' => 'Der Drucker hat die Adresse 192.168.0.12.',
    'email address' => 'Fragen an support@example.com',
]);

test('a sender display name that does not match the sender domain is a weak signal', function (string $sender) {
    $assessment = heuristicAnalysis()->analyse(suspectEmailFrom($sender));

    expect(signalsIn($assessment))->toBe(['Absendername passt nicht zur Absenderadresse' => SignalWeight::Weak])
        ->and($assessment->heuristicFloor)->toBe(RiskLevel::Verdaechtig);
})->with([
    'brand from a free-mail address' => '"Sparkasse" <sicherheit.sparkasse24@gmail.com>',
    'brand from an unrelated domain' => 'PayPal <service@konto-sicherheit.example.ru>',
    'brand in a longer name' => 'DHL Paketservice <info@zustellung.example.net>',
    'brand written with a space' => 'Deutsche Bank Kundenservice <info@kunden-portal.example.com>',
    'brand in upper case, unquoted' => 'AMAZON <noreply@bestellung.example.org>',
    'address of another domain as name' => '"rechnung@example-lieferant.de" <rechnung@example.org>',
    'organisation from a free-mail address' => 'IT-Abteilung <it.helpdesk@gmx.de>',
    'English organisation from a free-mail address' => 'Account Security Team <security.alerts@outlook.com>',
    'organisation from a free-mail subdomain' => '"Kundenservice" <kundenservice@mail.yahoo.com>',
]);

test('a sender display name that fits the sender is not a signal', function (string $sender) {
    expect(heuristicAnalysis()->analyse(suspectEmailFrom($sender))->signals)->toBeEmpty();
})->with([
    'address without display name' => 'service@example.com',
    'person from a free-mail address' => 'Max Mustermann <max.mustermann@gmail.com>',
    'quoted person name with umlauts' => '"Jürgen Müller" <juergen.mueller@web.de>',
    'brand from its own domain' => 'PayPal <service@paypal.de>',
    'brand from its own subdomain' => 'Microsoft account team <account-security-noreply@accountprotection.microsoft.com>',
    'brand inside a longer domain' => 'Sparkasse KölnBonn <info@sparkasse-koelnbonn.de>',
    'brand with a space and a hyphenated domain' => 'Deutsche Bank <info@deutsche-bank.de>',
    'brand name only as part of a word' => 'Ingo Schmidt <ingo.schmidt@gmail.com>',
    'organisation from a company domain' => 'Kundenservice <service@example-shop.de>',
    'own address as name' => '"info@example.com" <info@example.com>',
    'surname starting with an organisation word' => 'Peter Kassebaum <peter.kassebaum@gmx.de>',
    'surname containing an organisation word' => 'Stefan Banke <s.banke@web.de>',
    'name containing an organisation word inside' => 'Anna Steamer <anna.steamer@gmail.com>',
    'first name that is also a brand' => 'Hermes Papadopoulos <hermes.p@gmail.com>',
]);

test('a domain that imitates a well-known brand is a strong signal', function (SuspectEmail $email) {
    $assessment = heuristicAnalysis()->analyse($email);

    expect(signalsIn($assessment))->toBe(['Täuschend ähnliche Domain' => SignalWeight::Strong])
        ->and($assessment->heuristicFloor)->toBe(RiskLevel::Gefaehrlich);
})->with([
    'digit instead of a letter in the sender' => fn () => suspectEmailFrom('service@paypa1.com'),
    'digit instead of a letter in a link' => fn () => suspectEmailWithBody('Jetzt bestätigen: https://www.paypa1.com/login'),
    'lookalike before a hyphen' => fn () => suspectEmailWithBody('<a href="https://amaz0n-konto.com/verify">Konto prüfen</a>'),
    'lookalike in a subdomain' => fn () => suspectEmailWithBody('Anmelden unter https://paypa1.konto-login.example.ru/'),
    '"rn" instead of "m"' => fn () => suspectEmailWithBody('Ihr Konto: https://login.rnicrosoft.com/'),
    'one letter added' => fn () => suspectEmailWithBody('Siehe www.paypall.com'),
    'one letter missing' => fn () => suspectEmailFrom('kundenservice@sparkase.de'),
    'capital I instead of l' => fn () => suspectEmailWithBody('Abo verlängern: https://www.netfIix.com/account'),
    'Cyrillic letter in a Latin domain' => fn () => suspectEmailWithBody('Anmelden: https://www.pаypal.com/signin'),
    'the same domain as punycode' => fn () => suspectEmailWithBody('<a href="https://xn--pypal-4ve.com/signin">Anmelden</a>'),
    'Greek letter in a Latin domain' => fn () => suspectEmailWithBody('Sicherheitswarnung: https://accounts.gοogle.com/'),
    'domain written entirely in Cyrillic lookalikes' => fn () => suspectEmailWithBody('Ihre ID wurde gesperrt: https://аррӏе.com/id'),
]);

test('genuine brand domains and ordinary domains are not taken for lookalikes', function (SuspectEmail $email) {
    expect(heuristicAnalysis()->analyse($email)->signals)->toBeEmpty();
})->with([
    'genuine brand domain' => fn () => suspectEmailWithBody('Ihre Zahlung: https://www.paypal.com/myaccount'),
    'genuine brand sender' => fn () => suspectEmailFrom('service@paypal.de'),
    'genuine brand on a country-code domain' => fn () => suspectEmailWithBody('Bestellungen: https://www.amazon.co.uk/orders'),
    'brand combined with other words' => fn () => suspectEmailWithBody('Neu: https://www.ebay-kleinanzeigen.de/'),
    'domain with umlauts' => fn () => suspectEmailWithBody('Unser Angebot: https://www.müller-bäckerei.de/brot'),
    'domain with umlauts as punycode' => fn () => suspectEmailWithBody('Unser Angebot: https://www.xn--mller-bckerei-hfb76a.de/brot'),
    'word one letter away from a brand' => fn () => suspectEmailFrom('newsletter@bt-telecom.co.uk'),
    'plural of a brand inside a domain' => fn () => suspectEmailWithBody('Exposé: https://www.sparkassen-immobilien.de/objekt/123'),
    'other service one letter away from a brand' => fn () => suspectEmailWithBody('Bezahlen mit https://paypay.ne.jp/'),
    'word close to a short brand' => fn () => suspectEmailWithBody('Bewerben Sie sich: https://apply.example.com/jobs'),
    'domain that contains a brand name inside a word' => fn () => suspectEmailWithBody('Doku: https://storage.googleapis.com/bucket/file'),
    'newsletter with ordinary links' => fn () => suspectEmailFrom(
        'Example Shop Newsletter <newsletter@example-shop.de>',
        "Hallo,\n\nunsere Herbstaktion startet: https://www.example-shop.de/herbst\nAbmelden: https://www.example-shop.de/abmelden",
    ),
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
