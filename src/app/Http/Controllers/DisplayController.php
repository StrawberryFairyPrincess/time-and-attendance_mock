<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\CarbonImmutable;
use App\Models\Clock;


class DisplayController extends Controller
{
    // 勤怠一覧画面の表示
    public function list()
    {
        return view('/general/index');
    }

    // 勤怠登録画面の表示
    public function clock()
    {
        $date = CarbonImmutable::now();

        if( Clock::where('id', Auth::id())->exists() ){
            if( Auth::user()->clocks()->latest()->first()->status == '出勤' ||
                Auth::user()->clocks()->latest()->first()->status == '休憩戻' ){
                $status = '出勤中';
            }
            elseif( Auth::user()->clocks()->latest()->first()->status == '退勤' ){
                //最後の登録が当日
                if( $date->isSameDay( Auth::user()->clocks()->latest()->first()->clock ) ){
                    $status = '退勤済';
                }
                //最後の登録が前日以前
                else{
                    $status = '勤務外';
                }
            }
            elseif( Auth::user()->clocks()->latest()->first()->status == '休憩入' ){
                $status = '休憩中';
            }
        }
        else{ //データベースの登録が初めてのとき
            $status = '勤務外';
        }

        return view( '/general/clock', compact('date', 'status') );
    }
}
