<?php

namespace App\Check\Signals;

/**
 * Finds keywords and phrases from config/check.php in the text of a suspect
 * email: whole words only, ignoring case, and with spaces and hyphens inside
 * a phrase interchangeable or left out, so "e-mail" also finds "E Mail" and
 * "Email".
 */
final class Phrases
{
    /**
     * @param  list<string>  $phrases
     */
    public static function anyIn(string $text, array $phrases): bool
    {
        foreach ($phrases as $phrase) {
            if (preg_match(self::pattern($phrase), $text) === 1) {
                return true;
            }
        }

        return false;
    }

    private static function pattern(string $phrase): string
    {
        $words = preg_split('/[\s-]+/u', trim($phrase), flags: PREG_SPLIT_NO_EMPTY) ?: [];

        return '/(?<![\p{L}\p{N}])'
            .implode('[\s-]*', array_map(fn (string $word): string => preg_quote($word, '/'), $words))
            .'(?![\p{L}\p{N}])/iu';
    }
}
