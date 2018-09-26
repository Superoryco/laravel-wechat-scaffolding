<?php

namespace App\Exceptions;

use Exception;

class JSMSErrorException extends Exception
{
    public static $OK = 0;
    private $humanCode;
    private $errorCode;
    private $errorMessage;

    public function __construct(string $message = "", int $code = 50000)
    {
        $this -> errorCode = $code;
        $this -> errorMessage = $message;
        $this->humanCode = 'JIGUANG_ERROR:'.$code;
        parent::__construct($message, $code);
    }

    public function toResponse($request)
    {
        $response = [
            'error' => [
                'message' => $this->errorMessage,
                'code' => $this->errorCode,
                'text' => $this->humanCode,
            ]
        ];
        return response()->json($response, 400);
    }
}
