<?php

namespace App\Exceptions;

use Illuminate\Contracts\Support\Responsable;
use App\Library\Wechat\ErrorCode;

class WechatErrorException extends \Exception implements Responsable
{
    private $humanCode;
    private $errorCode;
    private $errorMessage;

    public function __construct(string $message = "", int $code = 0)
    {
        $this->humanCode = 'WX_ERROR:'.$this->errorCodeToHumanCode($code);
        $this -> errorMessage = $message;
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

    private function errorCodeToHumanCode($errorCode)
    {
        $this -> errorCode = $errorCode;
        switch ($errorCode) {
            case ErrorCode::$DecodeBase64Error:
                return 'DECODE_BASE64_ERROR';
            case ErrorCode::$IllegalAesKey:
                return 'ILLEGAL_AES_KEY';
            case ErrorCode::$IllegalBuffer:
                return 'ILLEGAL_BUFFER';
            case ErrorCode::$IllegalIv:
                return 'ILLEGAL_IV';
            case ErrorCode::$InvalidCode:
                return 'IVALID_CODE';
            case ErrorCode::$SignatureNotMatch:
                return 'SIGNATURE_NOT_MATCH';
        }
    }
}
