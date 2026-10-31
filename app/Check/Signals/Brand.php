<?php

namespace App\Check\Signals;

/**
 * A well-known brand that phishing emails impersonate, e.g. "deutsche bank".
 */
final readonly class Brand
{
    public function __construct(public string $name) {}

    /**
     * The name as written in domains, without spaces: "deutschebank".
     */
    public function compact(): string
    {
        return str_replace(' ', '', $this->name);
    }

    /**
     * Whether the name appears as a whole word in the text, with spaces,
     * hyphens or nothing between its words ("Deutsche Bank", deutsche-bank).
     */
    public function isNamedIn(string $text): bool
    {
        return Phrases::anyIn($text, [$this->name]);
    }
}
