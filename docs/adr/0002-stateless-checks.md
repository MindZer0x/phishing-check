# Checks are stateless

Checks are not stored: no database table for suspect emails or results, no history, no user accounts, and logs never contain the suspect email's content. Suspect emails routinely contain personal data (names, addresses, internal details) of people who never agreed to it being processed, and for a security awareness tool the most defensible way to handle that data is not to keep it. The cost is that there is no history view or statistics; if those are ever needed, they should store only anonymised results (risk level and signal types), never email content.

## Consequences

- The authentication scaffolding of the Laravel Vue starter kit is removed.
- Error logging for a failed AI analysis records the failure reason and its cause only, never the suspect email.
