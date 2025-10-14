<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class InputValidationException extends Exception
{
    protected $errors;

    public function __construct($errors, $message = "Error validating input", $code = 0, Throwable $previous = null)
    {
        $this->errors = $errors;
        parent::__construct($message, $code, $previous);
    }

    public function getErrors() {
        return $this->errors;
    }
}
