<?php

namespace SportsScheduler\Exceptions;

use Exception;

final class TimeoutException extends Exception
{
    public function __construct(string $message, int $code, Exception|null $previous)
    {
        parent::__construct($message, $code, $previous);
    }
}
