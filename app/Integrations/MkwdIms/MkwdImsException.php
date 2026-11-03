<?php

namespace App\Integrations\MkwdIms;

use Illuminate\Contracts\Debug\ShouldntReport;
use RuntimeException;

final class MkwdImsException extends RuntimeException implements ShouldntReport
{
    public static function missingConfiguration(): self
    {
        return new self('The MKWD IMS API token is not configured.');
    }

    public static function connectionFailed(): self
    {
        return new self('The MKWD IMS vehicle service could not be reached.');
    }

    public static function authenticationFailed(): self
    {
        return new self('The MKWD IMS rejected the configured API credentials.');
    }

    public static function requestFailed(int $status): self
    {
        return new self("The MKWD IMS vehicle request failed with HTTP status {$status}.");
    }

    public static function invalidResponse(string $reason): self
    {
        return new self("The MKWD IMS vehicle response is invalid: {$reason}");
    }
}
