# Three risk levels, no numeric score

A check results in one of three risk levels (Unauffällig, Verdächtig, Gefährlich) and never shows a number. A score like "73/100" suggests a precision that neither the heuristics nor the AI analysis have, and employees need a decision ("don't click, report it"), not a percentage.

The heuristic floor follows from the signal weights: no signals means Unauffällig, at least one weak signal means Verdächtig, and at least one strong signal or at least two weak signals means Gefährlich.

| Weight | Signals |
| ------ | ------- |
| strong | link text and link target point to different domains; lookalike domain (typosquatting, IDN homograph); request for credentials, payment or gift cards; dangerous attachment type mentioned; instructions addressed to an AI |
| weak   | urgency or pressure language; URL shortener or raw IP address as link target; sender display name does not match the sender domain |
