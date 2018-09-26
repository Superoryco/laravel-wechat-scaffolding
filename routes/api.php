<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// 发送手机验证码
Route::post('/send_code', 'UserController@sendCode');

// 登录
Route::post('/login', 'UserController@login');

// 使用手机号码注册
Route::post('/register', 'UserController@signUp');

// 梦想超级联赛获取抽奖数据
Route::get('/raffle_list', 'DreamLandController@raffleList');

// 微信小程序
Route::prefix('wx')->group(function () {
    // 微信小程序登录
    Route::post('/signin_wechat', 'WechatController@wechatLogin');

    Route::group(['middleware' => 'auth:wx'], function () {
        // 小程序验证手机号码
        Route::post('/check_code', 'UserController@checkCode');

        // 小程序获取微信用户信息
        Route::post('/user_info', 'WechatController@getUserInfo');

        // 更新手机号码
        Route::post('/update_phone', 'UserController@updatePhone');

        // 梦想超级联赛抽奖专用记录未验证的手机号码
        Route::post('/update_phone_record', 'UserController@updatePhoneRecord');

        // 梦想超级联赛小程序专用返回所需基本信息Info
        Route::get('/dream_land_basic_info', 'DreamLandController@miniAppBasicInfo');

        // 绑定手机号码及密码
        Route::post('/update_account', 'WechatController@updateAccountWX');

        // 获取表单
        Route::get('/form/{id}', 'FormController@show');
    });
});

Route::group(['middleware' => 'auth:api'], function () {
    Route::prefix('user')->group(function () {
        // 获取用户信息
        Route::get('/', 'UserController@getUserInfo');

        // 更新手机号码
        Route::post('/update_phone', 'UserController@updatePhone');

        // 更新密码, 会废除之前所有的token
        Route::post('/reset_password', 'UserController@resetPassword');

        ///////////////////////TEST///////////////////////
        // 更新密码, 会废除之前所有的token
        Route::get('/person/{id}', 'PersonController@show');

        Route::get('/field/{id}', 'FieldController@show');
    });

    Route::prefix('admin')->group(function () {
        Route::post('/section', 'SectionController@store');

        Route::post('/field', 'FieldController@store');
    });

    Route::prefix('readability')->group(function () {
        Route::get('/parse','ReadabilityController@getPageInfo');
    });

    // 获取表单
    Route::get('/form/{id}', 'FormController@show');
});



