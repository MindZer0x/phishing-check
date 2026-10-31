<?php

namespace App\Check;

/**
 * Why an AI analysis failed: the only thing logged about it (ADR-0002).
 */
enum AiFailureReason: string
{
    case ApiError = 'api_error';
    case Timeout = 'timeout';
    case InvalidOutput = 'invalid_output';
}
