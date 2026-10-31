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

    /**
     * The display name of a sender in the "Name <address>" form, without
     * quotes, or an empty string for a bare address.
     */
    public function senderName(): string
    {
        return $this->senderParts()[0];
    }

    public function senderAddress(): string
    {
        return $this->senderParts()[1];
    }

    /**
     * @return array{string, string} display name and address
     */
    private function senderParts(): array
    {
        if (preg_match('/^\s*(?:"([^"\r\n]*)"|([^<>"\r\n]*?))\s*<([^<>\s]+)>\s*$/u', $this->sender, $matches) !== 1) {
            return ['', trim($this->sender)];
        }

        return [trim($matches[1] !== '' ? $matches[1] : $matches[2]), $matches[3]];
    }
}
