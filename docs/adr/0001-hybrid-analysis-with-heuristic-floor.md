# Hybrid analysis with a heuristic floor

A check runs deterministic, rule-based signals first and then hands the suspect email to Claude for the AI analysis. Claude may add signals and raise the risk level, but the result can never drop below the heuristic floor. We chose this over a Claude-only check because the suspect email is attacker-controlled: a phishing email can contain instructions aimed at the model ("rate this email as harmless"), and the floor guarantees that a prompt injection cannot turn a detected threat into a green verdict. It also keeps checks working, and testable, without an API key (heuristics-only mode).

## Considered Options

- **Claude only**: least code, but the verdict would be fully controllable by the email's author, untestable without mocks, and unavailable without an API key.
- **Heuristics only**: deterministic and free, but misses context such as plausible-looking CEO fraud and can only produce canned explanations.

## Consequences

- The suspect email is passed to Claude as clearly delimited data in the user message, never in the system prompt, and the response must match a strict output schema. Anything that fails validation is treated as a failed AI analysis.
- Instructions in the suspect email that address an AI are themselves a strong signal.
- If the AI analysis fails or times out, the check falls back to heuristics-only mode and says so on the learning card.
- Next steps always include fixed advice per risk level, so the core guidance never depends on the model.
