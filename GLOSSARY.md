# Phishing-Check

Helps employees judge whether an email they received is a phishing attempt, and teaches them which warning signs to look for.

## Checking an email

**Suspect email**:
The email an employee submits for checking, made up of sender, subject and body. Always treated as untrusted data, never as instructions.
_Avoid_: Mail, message, input

**Check**:
A single evaluation of one suspect email, producing a risk level and a learning card. Checks are not stored.
_Avoid_: Scan, analysis, request

**Example email**:
A built-in, fictional suspect email that can be loaded into the form to try out a check.
_Avoid_: Sample, demo mail, fixture

## Assessing risk

**Signal**:
A specific, explainable warning sign found in a suspect email, such as a link whose visible text points to a different domain than its target.
_Avoid_: Indicator, flag, red flag, finding

**Signal weight**:
How much a signal counts towards the risk level: either _weak_ or _strong_.
_Avoid_: Severity, score

**Risk level**:
The verdict of a check, one of exactly three values: _Unauffällig_, _Verdächtig_ or _Gefährlich_. There is no numeric score.
_Avoid_: Score, rating, threat level

**Heuristic floor**:
The risk level determined by the rule-based signals alone. The AI analysis may raise the risk level above it but never lower it below it.
_Avoid_: Minimum score, baseline

**AI analysis**:
The part of a check in which Claude assesses the suspect email, contributes additional signals and writes the explanations.
_Avoid_: LLM check, KI-Check

**Heuristics-only mode**:
A check without AI analysis, used when no API key is configured or the AI analysis fails. The user is told when a check ran in this mode.
_Avoid_: Offline mode, fallback mode

## Teaching

**Learning card**:
The result shown to the employee: the risk level, a short summary, each signal with an explanation of why it is a warning sign, and what to do now.
_Avoid_: Result, report, verdict card

**Next steps**:
The "Was jetzt tun?" section of a learning card: fixed advice for the risk level plus up to two tips specific to the suspect email.
_Avoid_: Recommendations, actions, tips
