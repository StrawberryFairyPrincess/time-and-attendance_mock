<?php

// 管理者のpost処理用

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\CarbonImmutable;
use App\Models\Member;
use App\Models\Clock;
use App\Models\Correction;


class ProcessController extends Controller
{
    // 勤怠一覧画面の日めくり
    public function page( Request $request )
    {
        // 勤怠一覧画面を最初に開いたときの日時
        $daily = CarbonImmutable::parse($request['daily']);

        // 前月が押されたとき
        if ($request->has('sub')) {
            return redirect('/admin/attendances')
                ->with([ //セッションで値を渡す
                    'sub' => $daily->subDay()
                ]);
        }
        // 翌月が押されたとき
        elseif ($request->has('add')) {
            return redirect('/admin/attendances')
                ->with([ //セッションで値を渡す
                    'add' => $daily->addDay()
                ]);
        }
    }




}
