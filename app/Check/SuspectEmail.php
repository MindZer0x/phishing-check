<?php

namespace App\Check;

/**
 * The email an employee submits for checking. Always untrusted data.
 */
final readonly class SuspectEmail
{
    public function __construct(
        public string $sender,
        public string $subject,
        public string $body,
    ) {}
}
