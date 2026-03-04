<?php

namespace App\Exceptions;

use Exception;

class ApiException extends Exception
{
    protected string $errorCode;
    protected int $statusCode;
    protected mixed $data;

    public function __construct(string $message, string $errorCode = 'INTERNAL_ERROR', int $statusCode = 500, mixed $data = null)
    {
        parent::__construct($message);
        
        $this->errorCode = $errorCode;
        $this->statusCode = $statusCode;
        $this->data = $data;
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render($request)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            $response = [
                'success' => false,
                'error_code' => $this->errorCode,
                'message' => $this->getMessage(),
                'request_id' => (string) \Illuminate\Support\Str::uuid()
            ];

            if ($this->data !== null) {
                $response['data'] = $this->data;
            }

            return response()->json($response, $this->statusCode);
        }

        return false;
    }
}
