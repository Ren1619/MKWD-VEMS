<?php

namespace App\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use RuntimeException;

class HrisUnavailableException extends RuntimeException implements ShouldntReport
{
    public function __construct()
    {
        parent::__construct('HRIS is not connected or is temporarily unavailable. Employee selection and refresh are unavailable; saved driver details remain accessible.');
    }
}
