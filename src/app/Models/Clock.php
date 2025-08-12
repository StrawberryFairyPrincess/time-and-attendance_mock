<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Member;

class Clock extends Model
{
    use HasFactory;

    // Clocksテーブルのカラムのうち操作可能にするもの
    protected $fillable = [
        'member_id',

        'clock',
        'status'
    ];

    // PHPで文字列として扱われないようにする(日時として扱う)
    protected $casts = [
        'clock' => 'datetime',
    ];

    // membersテーブルとのリレーション定義(多対1)
    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
