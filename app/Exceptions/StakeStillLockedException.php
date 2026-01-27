<?php

namespace App\Exceptions;

use Carbon\Carbon;
use Exception;

class StakeStillLockedException extends Exception
{
    /**
     * @var Carbon
     */
    protected Carbon $unlockDate;

    /**
     * Constructor
     *
     * @param string $message
     * @param Carbon|null $unlockDate
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct(string $message = "Stake is still locked", Carbon $unlockDate = null, $code = 422, Exception $previous = null)
    {
        $this->unlockDate = $unlockDate;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get unlock date
     *
     * @return Carbon
     */
    public function getUnlockDate(): Carbon
    {
        return $this->unlockDate;
    }
}
