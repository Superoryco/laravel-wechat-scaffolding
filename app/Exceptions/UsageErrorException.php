<?php

namespace App\Exceptions;

use Exception;
use App\Exceptions\ErrorCode as ErrorCode;

class UsageErrorException extends Exception
{
    private $humanCode;
    private $errorCode;
    private $errorMessage;

    public function __construct(int $code = 0)
    {
        $this->initWithErrorCode($code);
        parent::__construct($this->message, $code);
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

    private function initWithErrorCode($errorCode)
    {
        $this -> errorCode = $errorCode;
        switch ($errorCode) {
            case ErrorCode::$PHONE_USED:
                $this -> humanCode = 'PHONE_USED';
                $this -> errorMessage = 'The phone number has been used';
                break;
            case ErrorCode::$PASSWORD_ERROR:
                $this -> humanCode = 'PASSWORD_ERROR';
                $this -> errorMessage = 'Old password invalid';
                break;
            case ErrorCode::$FETCHING_CONTENT_FAILED:
                $this -> humanCode = 'FETCHING_CONTENT_FAILED';
                $this -> errorMessage = 'Can not fetching this content';
                break;
            case ErrorCode::$PHONE_RECORD_EXIST:
                $this -> humanCode = 'PHONE_RECORD_EXIST';
                $this -> errorMessage = 'User already has Phone record';
                break;
        }
    }
}
