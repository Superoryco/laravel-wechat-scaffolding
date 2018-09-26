<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\WechatInfo;
use App\WechatSession;
use App\User;
use App\Library\Wechat\Wxxcx;
use App\Http\Resources\WechatSessionResource as WechatSessionResource;
use App\Http\Resources\UserResource as UserResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\WechatErrorException;
use App\Library\Wechat\ErrorCode;
use App\Library\JiGuang\JSMS as JSMS;
use Illuminate\Support\Facades\Hash;

class WechatController extends Controller
{
    protected $wxxcx;

    function __construct(Wxxcx $wxxcx) {
        $this->wxxcx = $wxxcx;
    }

    public function wechatLogin(Request $request) {
        $this->validate($request, [
            'code' => 'bail|required',
        ],[
            'required' => 'The :attribute is required!',
        ]);
        //code 在小程序端使用 wx.login 获取
        $code = $request->input('code');

        // 根据 code 获取用户 session_key 等信息, 返回用户openid 和 session_key
        $loginInfo = $this->wxxcx->getLoginInfo($code);

        if (array_key_exists("unionID", $loginInfo)) {
            $session = WechatSession::where('unionId', '=', $loginInfo["unionid"])->first();
        } else {
            $session = WechatSession::where('openId', '=', $loginInfo["openid"])->first();
        }

        if (!$session) {
            $session = DB::transaction(function() use ($loginInfo){
                $newUser = User::create();
                $newSession = $newUser -> wechatSessions() -> create([
                    'id' => $this->randomFromDev(16),
                    'openId' => $loginInfo["openid"],
                    'sessionKey' => $loginInfo["session_key"],
                    'unionId' => array_key_exists("unionid",$loginInfo) ? $loginInfo["unionid"]:null
                ]);
                return $newSession;
            });
        } else {
            //如果新的sessionKey与库中不同才创建，否则直接返回库中的session避免重复保存
            if ($session->sessionKey !== $loginInfo["session_key"]) {
                $session = $session -> user -> wechatSessions() -> create([
                    'id' => $this->randomFromDev(16),
                    'openId' => $loginInfo["openid"],
                    'sessionKey' => $loginInfo["session_key"],
                    'unionId' => array_key_exists("unionid",$loginInfo) ? $loginInfo["unionid"]:null
                ]);
            }
        }
        return new WechatSessionResource($session);
    }


    //根据openID或unionID补全用户信息

    /**
     * @param Request $request
     * @return UserResource
     * @throws WechatErrorException
     * @throws \Exception
     */
    public function getUserInfo(Request $request) {
        $this->validate($request, [
            'rawData' => 'bail|required',
            'signature' => 'bail|required',
            'encryptedData' => 'bail|required',
            'iv' => 'bail|required',
        ],[
            'required' => 'The :attribute is required!',
        ]);
        $rawData = $request -> input('rawData');
        $signature = $request -> input('signature');
        $encryptedData = $request -> input('encryptedData');
        $iv = $request -> input('iv');

        $sessionKey = $this->getSessionKeyFor3rdSession($request->header('3rdSession'));

        $signature2 = sha1($rawData.$sessionKey);

        if ($signature2 !== $signature) {
            throw new WechatErrorException('Signature not match!',ErrorCode::$SignatureNotMatch);
        }

        //获取解密后的用户信息
        $userInfo = json_decode($this->wxxcx->getUserInfo($encryptedData, $iv, $sessionKey),true);

        //如果返回数据中包括unionID，则说明公众开放平台下具有多个应用，为保持用户体系的一致性使用unionID作为唯一查询条件
        if (array_key_exists("unionID", $userInfo)) {
            $wechatInfo = WechatInfo::where('unionID', '=', $userInfo["unionID"])->first();
        } else {
            $wechatInfo = WechatInfo::where('openId', '=', $userInfo["openId"])->first();
        }

        $user = Auth::user();

        if (!$wechatInfo) {

            $user = DB::transaction(function() use ($userInfo, $user){
                $user -> update([
                    'name' => $userInfo["nickName"],
                    'gender' => $userInfo["gender"],
                    'avatar' => $userInfo["avatarUrl"],
                ]);
                $user -> wechatInfos() -> create([
                    "openId" => $userInfo["openId"],
                    "nickName" => $userInfo["nickName"],
                    "gender" => $userInfo["gender"],
                    "language" => $userInfo["language"],
                    "city" => $userInfo["city"],
                    "province" => $userInfo["province"],
                    "country" => $userInfo["country"],
                    "avatarUrl" => $userInfo["avatarUrl"]
                ]);
                return $user;
            });
        }
        return new UserResource($user);
    }

    /**
     * 读取/dev/urandom获取随机数
     * @param $len
     * @return mixed|string
     */
    private function randomFromDev($len) {
        $fp = @fopen('/dev/urandom','rb');
        $result = '';
        if ($fp !== FALSE) {
            $result .= @fread($fp, $len);
            @fclose($fp);
        }
        else
        {
            trigger_error('Can not open /dev/urandom.');
        }
        // convert from binary to string
        $result = base64_encode($result);
        // remove none url chars
        $result = strtr($result, '+/', '-_');
        return substr($result, 0, $len);
    }


    public function getSessionKeyFor3rdSession($session) {
        return WechatSession::find($session)->sessionKey;
    }

    // 绑定手机号码及密码
    public function updateAccountWX(Request $request) {
        $this->validate($request, [
            'msg_id' => 'bail|required',
            'code' => 'bail|required',
            'phone' => 'bail|required|unique:users,phone',
            'password' => 'bail|required'
        ]);
        $msg_id = $request->input('msg_id');
        $code = $request->input('code');
        $phone = $request->input('phone');
        $password = $request->input('password');
        $client = new JSMS([ 'disable_ssl' => true ]);
        $is_valid = $client->checkCode($msg_id, $code);
        if ($is_valid) {
            $user = Auth::user();
            DB::transaction(function() use ($phone, $password, $user){
                $user -> update([
                    'phone' => $phone,
                    'password' => Hash::make($password),
                ]);
                return $user;
            });
        }
        return response()->json(['phone' => $phone]);
    }
}
