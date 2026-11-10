<?php

namespace App\Exceptions;

class ApiException extends \Exception
{
    private $status;
    private $errorCodeName;

    public function __construct($errorCodeName, $message, $status)
    {
        parent::__construct($message);

        $this->errorCodeName = $errorCodeName;
        $this->status = (int) $status;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getErrorCodeName()
    {
        return $this->errorCodeName;
    }
}
