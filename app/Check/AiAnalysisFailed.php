<?php

namespace App\Check;

use Illuminate\Http\Client\ConnectionException;
use RuntimeException;
use Throwable;

/**
 * The AI analysis produced no usable assessment, so the check falls back to
 * heuristics-only mode (ADR-0001). It carries the reason only: nothing from
 * the suspect email, and not the exception that caused it, whose message or
 * stack trace might contain the email (ADR-0002).
 */
final class AiAnalysisFailed extends RuntimeException
{
    private function __construct(public readonly AiFailureReason $reason)
    {
        parent::__construct("AI analysis failed: {$reason->value}");
    }

    public static function invalidOutput(): self
    {
        return new self(AiFailureReason::InvalidOutput);
    }

    /**
     * cURL error 28 is a request that ran into the #[Timeout] of the AI
     * analysis; laravel/ai reports it like any other connection error.
     */
    public static function causedBy(Throwable $exception): self
    {
        for ($cause = $exception; $cause !== null; $cause = $cause->getPrevious()) {
            if ($cause instanceof ConnectionException && str_contains($cause->getMessage(), 'cURL error 28')) {
                return new self(AiFailureReason::Timeout);
            }
        }

        return new self(AiFailureReason::ApiError);
    }
}
