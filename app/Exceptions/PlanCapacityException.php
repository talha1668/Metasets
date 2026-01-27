<?php

namespace App\Exceptions;

use Exception;

class PlanCapacityException extends Exception
{
    /**
     * Constructor
     *
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct(string $message = "Plan capacity reached", int $code = 422, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
