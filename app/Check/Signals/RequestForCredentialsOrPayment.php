<?php

namespace App\Check\Signals;

use App\Check\Signal;
use App\Check\SignalWeight;
use App\Check\SuspectEmail;

final readonly class RequestForCredentialsOrPayment implements Signal
{
    /**
     * @param  list<string>  $sensitiveData  credentials, payment details and means of payment
     * @param  list<string>  $requestWords  words that ask the reader to hand them over
     */
    public function __construct(private array $sensitiveData, private array $requestWords) {}

    public function title(): string
    {
        return 'Abfrage von Zugangsdaten oder Zahlung';
    }

    public function explanation(): string
    {
        return 'Die E-Mail fordert Sie auf, Zugangsdaten wie Passwort, PIN oder TAN preiszugeben, Zahlungsdaten zu bestätigen, Geld zu überweisen oder Gutscheinkarten zu kaufen. '
            .'Seriöse Unternehmen und Kolleg:innen fragen so etwas nie per E-Mail ab. Klären Sie solche Bitten immer über einen bekannten, anderen Weg.';
    }

    public function weight(): SignalWeight
    {
        return SignalWeight::Strong;
    }

    /**
     * Sensitive data and a request word have to appear in the same sentence,
     * so that notices such as "Ihr Passwort wurde geändert" do not count.
     */
    public function isFoundIn(SuspectEmail $email): bool
    {
        foreach (preg_split('/(?<=[.!?])\s+|\R+/u', $email->text()) ?: [] as $sentence) {
            if (Phrases::anyIn($sentence, $this->sensitiveData) && Phrases::anyIn($sentence, $this->requestWords)) {
                return true;
            }
        }

        return false;
    }
}
