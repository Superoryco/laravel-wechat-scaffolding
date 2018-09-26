<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WechatSession extends Model
{
    protected $guarded = [];
    public $incrementing = false;

    public function user() {
        return $this->belongsTo(User::class);
    }
}
