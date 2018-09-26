<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use Notifiable, HasApiTokens;

    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    // Laravel 默认使用 email 字段来认证。如果你想用其他字段认证，可以在 LoginController 里面定义一个 username 方法：
    public function username() {
        return 'phone';
    }

    public function wechatInfos() {
        return $this->hasMany(WechatInfo::class);
    }

    public function wechatSessions() {
        return $this->hasMany(WechatSession::class);
    }

    // 以Email 或 Phone作为验证凭证 https://laracasts.com/discuss/channels/laravel/findforpassport-pass-different-credentials
    public function findForPassport($username) {
        return $this->orWhere('email', $username)->orWhere('phone', $username)->first();
    }

    public function phoneNumber()
    {
        return $this->hasOne('App\Phone');
    }
}
