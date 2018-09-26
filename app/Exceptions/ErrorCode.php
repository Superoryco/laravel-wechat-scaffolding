<?php
namespace App\Exceptions;

class ErrorCode
{
    public static $OK                       = 0;
    public static $PHONE_USED               = 70001;
    public static $PASSWORD_ERROR           = 70002;
    public static $READABILITY_PARSE_FAILED = 70003;

    public static $NOT_FOUND                = 60001;
    public static $METHOD_NOT_ALLOWED       = 60002;
    public static $ACTION_NOT_ALLOWED       = 60003;
    public static $INVALID_CREDENTIALS      = 60004;
    public static $UNAUTHORIZED_ACCESS      = 60005;
    public static $INTERNAL_ERROR           = 60006;
    public static $INVALID_PARAMETER        = 60007;

    public static $FETCHING_CONTENT_FAILED  = 80001;
}

?>
