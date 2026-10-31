# Three risk levels, no numeric score

A check results in one of three risk levels (Unauffällig, Verdächtig, Gefährlich) and never shows a number. A score like "73/100" suggests a precision that neither the heuristics nor the AI analysis have, and employees need a decision ("don't click, report it"), not a percentage.

The heuristic floor follows from the signal weights: no signals means Unauffällig, at least one weak signal means Verdächtig, and at least one strong signal or at least two weak signals means Gefährlich.

| Weight | Signals |
| ------ | ------- |
| strong | link text and link target point to different domains; lookalike domain (typosquatting, IDN homograph, a brand domain placed in front of another domain such as `paypal.com.example.ru`); request for credentials, payment or gift cards; dangerous attachment type mentioned; instructions addressed to an AI |
| weak   | urgency or pressure language; URL shortener or raw IP address as link target; sender display name does not match the sender domain; brand name in a domain that does not belong to the brand |

A brand name inside an unrelated domain (`paypal-secure.example.ru`, `paypalsecure.example.ru`) is only weak: genuine companies use such domains too (`sparkasse-koelnbonn.de`), and the heuristic analysis has no list of every brand's official domains. We accept that genuine emails using them come out Verdächtig, preferring a false positive to a missed phishing attempt. Only domains brands use in nearly every genuine email, such as `paypalobjects.com` for images, are exempt via a list in `config/check.php`. The remaining gaps and accepted false positives of the sender and link signals are collected in #13.

The content signals work on keyword lists in `config/check.php` and cannot understand context, so they also prefer a false positive to a missed phishing attempt. A request for credentials or payment needs a sensitive-data word and a request word in the same sentence; "Ihr Passwort wurde geändert" does not count, but "We will send the payment next week" does. Any mention of a dangerous file extension counts, including technology names such as `Node.js`. Everyday uses of pressure words ("ab sofort") make an email Verdächtig. Requests spread over two sentences, abbreviations such as "z. B." that end a sentence early, and wording not on the lists are missed; the AI analysis is meant to catch these.
