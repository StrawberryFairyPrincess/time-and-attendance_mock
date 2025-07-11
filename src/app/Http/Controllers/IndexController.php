<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class IndexController extends Controller
{
    // 勤怠一覧画面の表示
    public function list()
    {
        return view('/general/index');
    }

    // 出勤登録画面の表示
    public function clock()
    {
        return view('/general/clock');
    }
}
