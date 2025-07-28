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
    public function daily( Request $request )
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
        // 翌日が押されたとき
        elseif ($request->has('add')) {
            return redirect('/admin/attendances')
                ->with([ //セッションで値を渡す
                    'add' => $daily->addDay()
                ]);
        }
    }

    // スタッフ別勤怠一覧画面の月めくり
    public function monthly( Request $request )
    {
        // 勤怠一覧画面を最初に開いたときの日時
        $monthly = CarbonImmutable::parse($request['monthly']);

        // 前月が押されたとき
        if ($request->has('sub')) {
            return redirect('/admin/users/' . $request->member_id . '/attendances')
                ->with([ //セッションで値を渡す
                    'sub' => $monthly->subMonth()
                ]);
        }
        // 翌月が押されたとき
        elseif ($request->has('add')) {
            return redirect('/admin/users/' . $request->member_id . '/attendances')
                ->with([ //セッションで値を渡す
                    'add' => $monthly->addMonth()
                ]);
        }
    }




}
