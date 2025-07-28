<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Member;


class Correction extends Model
{
    use HasFactory;

    // Correctionsテーブルのカラムのうち操作可能にするもの
    protected $fillable = [
        'member_id',

        'date',
        'clockin',
        'clockout',
        'breaks',
        'remarks',
        'approve'
    ];

    protected $casts = [
        'date' => 'datetime',
        'clockin' => 'datetime',
        'clockout' => 'datetime',
        'breaks' => 'array'
    ];

    // membersテーブルとのリレーション定義(多対1)
    public function member() {
        return $this->belongsTo(Member::class);
    }
}
