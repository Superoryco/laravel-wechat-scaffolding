<?php
namespace App\Library\Wechat;

class ErrorCode
{
	public static $OK = 0;
	public static $IllegalAesKey = -41001;
	public static $IllegalIv = -41002;
	public static $IllegalBuffer = -41003;
    public static $DecodeBase64Error = -41004;
    public static $InvalidCode = 40029;
    public static $SignatureNotMatch = -41999;
}

?>
