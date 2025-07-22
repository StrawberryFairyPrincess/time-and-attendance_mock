<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\CarbonImmutable;
use App\Models\Clock;
use App\Http\Controllers\DisplayController;


class ProcessController extends Controller
{
    // 勤怠登録
    public function clock( Request $request ){

        $clock = [
            'member_id' => Auth::id(),
            'clock' => CarbonImmutable::now(), //ボタンを押したときの時間
            'status' => null
        ];

        // 出勤ボタンが押されたとき
        if ($request->has('clockin')) {
            $clock['status'] = '出勤';
        }
        // 退勤ボタンが押されたとき
        elseif ($request->has('clockout')) {
            $clock['status'] = '退勤';
        }
        // 休憩入ボタンが押されたとき
        elseif ($request->has('break')) {
            $clock['status'] = '休憩入';
        }
        // 休憩戻ボタンが押されたとき
        elseif ($request->has('back')) {
            $clock['status'] = '休憩戻';
        }

        Clock::create( $clock );

        return redirect('/attendance');
    }

    // 勤怠一覧画面の月めくり
    public function page( Request $request )
    {
        // 勤怠一覧画面を最初に開いたときの日時
        $monthly = CarbonImmutable::parse($request['monthly']);

        // 前月が押されたとき
        if ($request->has('sub')) {
            return redirect('/attendance/list')
                ->with([ //セッションで値を渡す
                    'monthly' => $monthly,
                    'sub' => $monthly->subMonth()
                ]);
        }
        // 翌月が押されたとき
        elseif ($request->has('add')) {
            return redirect('/attendance/list')
                ->with([ //セッションで値を渡す
                    'monthly' => $monthly,
                    'add' => $monthly->addMonth()
                ]);
        }
    }

}
