<?php

namespace App\Check;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use RuntimeException;
use Throwable;

/**
 * The AI analysis produced no usable assessment, so the check falls back to
 * heuristics-only mode (ADR-0001). It carries the reason and, for an API
 * error or a timeout, the class and HTTP status of its cause, but never
 * that exception itself, whose message or stack trace might contain the
 * suspect email (ADR-0002).
 */
final class AiAnalysisFailed extends RuntimeException
{
    /**
     * @param  class-string<Throwable>|null  $cause
     */
    private function __construct(
        public readonly AiFailureReason $reason,
        public readonly ?string $cause = null,
        public readonly ?int $status = null,
    ) {
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
        $reason = AiFailureReason::ApiError;
        $status = null;

        for ($cause = $exception; $cause !== null; $cause = $cause->getPrevious()) {
            if ($cause instanceof ConnectionException && str_contains($cause->getMessage(), 'cURL error 28')) {
                $reason = AiFailureReason::Timeout;
            }

            if ($cause instanceof RequestException) {
                $status = $cause->response->status();
            }
        }

        return new self($reason, $exception::class, $status);
    }

    /**
     * What gets logged about the failure: nothing from the suspect email.
     *
     * @return array{reason: string, exception?: class-string<Throwable>, status?: int}
     */
    public function logContext(): array
    {
        return array_filter([
            'reason' => $this->reason->value,
            'exception' => $this->cause,
            'status' => $this->status,
        ], fn (string|int|null $value): bool => $value !== null);
    }
}
