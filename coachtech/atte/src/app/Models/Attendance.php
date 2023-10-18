<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'start_time',
        'end_time',
        'start_break',
        'end_break',
        'total_hours'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function scopeGetByDate($query, $date) {
        return $query->whereDate('date', $date);
    }
}