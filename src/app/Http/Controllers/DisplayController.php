<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\CarbonImmutable;
use App\Models\Clock;


class DisplayController extends Controller
{
    // 勤怠登録画面の表示
    public function clock()
    {
        // 画面を表示したときの日時
        $date = CarbonImmutable::now();

        // データベースにログインユーザのデータがあるとき
        if( Clock::where('id', Auth::id())->exists() ){
            // 最後の登録が「出勤」か「休憩戻」だったときのステータス
            if( Auth::user()->clocks()->latest()->first()->status == '出勤' ||
                Auth::user()->clocks()->latest()->first()->status == '休憩戻' ){
                $status = '出勤中';
            }
            // 最後の登録が「退勤」だったときのステータス
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
            // 最後の登録が「休憩入」だったときのステータス
            elseif( Auth::user()->clocks()->latest()->first()->status == '休憩入' ){
                $status = '休憩中';
            }
        }
        //データベースへの登録が初めてのとき
        else{
            $status = '勤務外';
        }

        return view( '/general/clock', compact( 'date', 'status' ) );
    }

    // 勤怠一覧画面の表示
    public function list( Request $request )
    {
        // 前月が押されたとき
        if( $request->session()->has('sub') ){
            $today = $request->session()->get('sub');
        }
        // 翌月が押されたとき
        elseif( $request->session()->has('add') ){
            $today = $request->session()->get('add');
        }
        // 画面を最初に表示したときの日時
        else{
            $today = CarbonImmutable::now();
        }

        // 自分の打刻情報を全て取得
        $clocks = Clock::where('member_id', Auth::id())->get();

        $table = [];
        $previous = null;
        foreach( $clocks as $clock ){
            // 前のデータがないか、前のデータが違う日付のとき
            if( $previous == null ||
                !$clock['clock']->isSameDay( $previous['clock'] ) ){

                $table[ $clock['clock']->isoFormat('YYYY/MM/DD') ] = [
                    'clockin' => null,
                    'clockout' => null,
                    'break' => 0,
                    'sum' => 0
                ];
            }

            // &をつければ元の配列も編集される↓↓↓
            // $date: $tableのキー(日付)、$rowは配列の中身4項目
            foreach( $table as $date => &$row ){
                if( $date == $clock['clock']->isoFormat('YYYY/MM/DD') ){
                    if( $clock['status'] == '出勤' ){
                        $row['clockin'] = $clock['clock'];
                    }
                    elseif( $clock['status'] == '退勤' ){
                        $row['clockout'] = $clock['clock'];
                        $row['sum'] = $row['clockin']->diffInSeconds( $row['clockout'] );
                    }
                    elseif( $clock['status'] == '休憩入' ){
                        $break = $clock['clock'];
                    }
                    elseif( $clock['status'] == '休憩戻' ){
                        $row['break'] += $break->diffInSeconds( $clock['clock'] );
                    }
                    break;
                }
            }

            $previous = $clock;
        }

        return view( '/general/index', compact( 'today', 'table' ) );
    }

    // 勤怠詳細画面の表示
    public function detail()
    {








        return view( '/general/detail' );
    }
}
