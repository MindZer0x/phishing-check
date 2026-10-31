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
        $words = array_map(fn (string $word): string => preg_quote($word, '/'), explode(' ', $this->name));

        return preg_match('/(?<![\p{L}\p{N}])'.implode('[\s-]*', $words).'(?![\p{L}\p{N}])/iu', $text) === 1;
    }
}
