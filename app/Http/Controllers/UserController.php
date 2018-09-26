<?php

namespace App\Http\Controllers;

use App\Exceptions\ErrorCode;
use App\Exceptions\UsageErrorException;
use Illuminate\Http\Request;
use App\Library\JiGuang\JSMS as JSMS;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\UserResource as UserResource;
use Illuminate\Support\Facades\Hash;
use App\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    // 发送验证码
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @throws \App\Exceptions\JSMSErrorException
     */
    public function sendCode(Request $request) {
        $this->validate($request, [
            'phone' => 'bail|required',
        ]);
        $phone = $request->input('phone');
        $client = new JSMS([ 'disable_ssl' => true ]);
        $msg_id = $client->sendCode($phone, 1);
        return response()->json(['msg_id' => $msg_id]);
    }

    //检验验证码
    public function checkCode(Request $request) {
        $this->validate($request, [
            'msg_id' => 'bail|required',
            'code' => 'bail|required',
        ]);
        $msg_id = $request->input('msg_id');
        $code = $request->input('code');
        $client = new JSMS([ 'disable_ssl' => true ]);
        $is_valid = $client->checkCode($msg_id, $code);
        return response()->json(['is_valid' => $is_valid]);
    }

    // 绑定手机号码
    public function updatePhone(Request $request) {
        $this->validate($request, [
            'msg_id' => 'bail|required',
            'code' => 'bail|required',
            'phone' => 'bail|required|unique:users,phone',
        ]);
        $msg_id = $request->input('msg_id');
        $code = $request->input('code');
        $phone = $request->input('phone');
        $client = new JSMS([ 'disable_ssl' => true ]);
        $is_valid = $client->checkCode($msg_id, $code);
        if ($is_valid) {
            $user = Auth::user();
            DB::transaction(function() use ($phone, $user){
                $user -> update([
                    'phone' => $phone,
                ]);
                return $user;
            });
        }
        return response()->json(['phone' => $phone]);
    }

    public function updatePhoneRecord(Request $request) {
        $this->validate($request, [
            'phone' => 'bail|required|unique:phones,number',
        ]);
        $phone = $request->input('phone');
        $user = Auth::user();
        if ($user->phoneNumber) {
            throw new UsageErrorException(ErrorCode::$PHONE_RECORD_EXIST);
        }else {
            $user->phoneNumber()->create([
                'number' => $phone,
            ]);
        }
        return response()->json(['phoneNumber' => $phone]);
    }

    //获取用户信息
    public function getUserInfo(Request $request) {
        $user = Auth::user();
        return new UserResource($user);
    }

    //注册用户
    public function signUp(Request $request){
        $this->validate($request, [
            'msg_id' => 'bail|required',
            'code' => 'bail|required',
            'phone' => 'bail|required|unique:users,phone',
            'password' => 'bail|required|string|min:6'
        ]);
        $msg_id = $request->input('msg_id');
        $code = $request->input('code');
        $phone = $request->input('phone');
        $password = $request->input('password');
        $client = new JSMS([ 'disable_ssl' => true ]);
        $is_valid = $client->checkCode($msg_id, $code);
        if ($is_valid) {
            User::create([
                'phone' => $phone,
                'password' => Hash::make($password),
            ]);
            $request -> replace([
                'username' => $phone,
                'password' => $password,
            ]);
        }
        return $this->login($request);
    }

    // 用户登录
    public function login(Request $request)
    {
        $this->validate($request, [
            'password' => 'bail|required',
            'username' => 'bail|required',
        ]);
        // https://laravel-china.org/articles/5548/brief-summary-of-laravel-passport-api-certification?order_by=created_at&
        $http = new Client;
        try {
            $response = $http->post($request->root() . '/oauth/token', [
                'form_params' => [
                    'username' => $request->input('username'),
                    'password' => $request->input('password'),
                    'grant_type' => env('OAUTH_GRANT_TYPE'),
                    'client_id' => env('OAUTH_CLIENT_ID'),
                    'client_secret' => env('OAUTH_CLIENT_SECRET'),
                    'scope' => env('OAUTH_SCOPE', '*'),
                ],
            ]);
        } catch (RequestException $e) {
            throw  new UnauthorizedHttpException('', '账号验证失败');
        }
        return json_decode((string) $response->getBody(), true);
    }

    /**
     * @param Request $request
     * @return UserResource
     * @throws UsageErrorException
     * @throws \Exception
     */
    public function resetPassword(Request $request)
    {
        $this->validate($request, [
            'old_password' => 'bail|required',
            'new_password' => 'bail|required|string|min:6',
        ]);
        $old_password = $request->input('old_password');
        $new_password = $request->input('new_password');
        $user = Auth::user();
        $current_password = $user -> getAuthPassword();
        // 检验密码是否正确
        if ($current_password !== Hash::make($old_password)) {
            throw new UsageErrorException(ErrorCode::$PASSWORD_ERROR);
        }
        $accessTokens = $user -> tokens;
        DB::transaction(function() use ($accessTokens, $user, $new_password) {
            $user -> update([
                'password' => Hash::make($new_password),
            ]);
            // 更新密码后应当revoke 或删除之前所有的 AccessToken 及 RefreshToken
            foreach($accessTokens as $token) {
                DB::table('oauth_refresh_tokens')
                    ->where('access_token_id', $token->id)
                    ->delete();
                $token->delete();
            }
        });
        $request -> replace([
            'username' => $user -> phone,
            'password' => $new_password,
        ]);
        return $this->login($request);
    }
}
