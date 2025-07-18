<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\CarbonImmutable;
use App\Models\Clock;


class ProcessController extends Controller
{
    // 勤怠登録
    public function clock( Request $request ){

        $clock = [
            'member_id' => Auth::id(),
            'clock' => CarbonImmutable::now(),
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

    
}
