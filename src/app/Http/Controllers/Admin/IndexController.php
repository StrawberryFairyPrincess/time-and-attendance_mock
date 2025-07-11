<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    // 退勤一覧画面の表示
    public function index()
    {
        return view('admin/index');
    }
}
