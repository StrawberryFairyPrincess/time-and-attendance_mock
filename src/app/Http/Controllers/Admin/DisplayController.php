<?php

// 管理者の表示用(get処理)

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DisplayController extends Controller
{
    // 退勤一覧画面の表示
    public function index()
    {
        return view('admin/index');
    }
}
