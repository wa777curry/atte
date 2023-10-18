<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'password'];

    public function attendance() {
        return $this->hasMany(Attendance::class);
    }

    // 最終ログイン日時を更新するメソッド（いらないかも）
    public function updateLastLogin() {
        $this->update(['last_login_at' => now()]);
    }
}