<?php

namespace App\Domain\Exceptions;

use Exception;

class ApiException extends Exception
{
    protected $statusCode;
    protected $errors;

    public function __construct(string $message, int $statusCode = 400, array $errors = [])
    {
        parent::__construct($message, $statusCode);
        $this->statusCode = $statusCode;
        $this->errors = $errors;
    }

    public function render()
    {
        return response()->json([
            'success' => false,
            'message' => $this->message,
            'errors' => $this->errors,
            'code' => $this->statusCode,
        ], $this->statusCode);
    }
}
