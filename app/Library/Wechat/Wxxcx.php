<?php
namespace App\Library\Wechat;

use App\Exceptions\WechatErrorException;

class Wxxcx
{
    /**
     * @var string
     */
    private $appId;
    private $secret;
    private $code2session_url;

    /**
     * Wxxcx constructor.
     */
    function __construct()
    {
        $this->appId = config('wxxcx.appid', '');
        $this->secret = config('wxxcx.secret', '');
        $this->code2session_url = config('wxxcx.code2session_url', '');
    }

    /**
     * @return mixed
     * @throws
     */
    public function getLoginInfo($code){
        return $this->authCodeAndCode2session($code);
    }

    /**
     * @param $encryptedData
     * @param $iv
     * @return string
     * @throws \Exception
     */
    public function getUserInfo($encryptedData, $iv, $sessionKey){
        $pc = new WXBizDataCrypt($this->appId, $sessionKey);
        $decodeData = "";
        $errCode = $pc->decryptData($encryptedData, $iv, $decodeData);
        if ($errCode !=0 ) {
            throw new WechatErrorException('encryptedData 解密失败',$errCode);
        }
        return $decodeData;
    }

    /**
     * 根据 code 获取 session_key 等相关信息
     * @throws \Exception
     */
    private function authCodeAndCode2session($code){
        $code2session_url = sprintf($this->code2session_url,$this->appId,$this->secret,$code);
        $userInfo = $this->httpRequest($code2session_url);
        if(!isset($userInfo['session_key'])){
            throw new WechatErrorException($userInfo['errmsg'],$userInfo['errcode']);
        }
        $this->sessionKey = $userInfo['session_key'];
        return $userInfo;
    }


    /**
     * 请求小程序api
     * @author 晚黎
     * @date   2017-05-27T11:51:10+0800
     * @param  [type]                   $url  [description]
     * @param  [type]                   $data [description]
     * @return [type]                         [description]
     */
    private function httpRequest($url, $data = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        if($output === FALSE ){
            return false;
        }
        curl_close($curl);
        return json_decode($output,JSON_UNESCAPED_UNICODE);
    }

}
