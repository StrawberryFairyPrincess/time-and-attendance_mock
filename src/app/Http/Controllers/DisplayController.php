<?php

// 一般ユーザの表示用(get処理)

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\CarbonImmutable;
use App\Models\Clock;
use App\Models\Correction;


class DisplayController extends Controller
{
    // 勤怠登録画面の表示
    public function clock()
    {
        // 画面を表示したときの日時
        $date = CarbonImmutable::now();

        // データベースにログインユーザのデータがあるとき
        if( Clock::where( 'member_id', Auth::id() )->exists() ){
            // 最後の登録が「出勤」か「休憩戻」だったときのステータス
            if( Auth::user()->clocks()->latest()->first()['status'] == '出勤' ||
                Auth::user()->clocks()->latest()->first()['status'] == '休憩戻' ){
                $status = '出勤中';
            }
            // 最後の登録が「退勤」だったときのステータス
            elseif( Auth::user()->clocks()->latest()->first()['status'] == '退勤' ){
                //最後の「退勤」の打刻が当日
                if( $date->isSameDay( Auth::user()->clocks()->latest()->first()->clock ) ){
                    // 同日に「出勤」の打刻があるとき
                    if( Clock::whereDate( 'clock', $date )->where( 'status', '出勤' )->first()
                        != NULL ){
                        $status = '退勤済';
                    }
                    // 同日に「出勤」の打刻がないとき（前日の打刻）
                    else{
                        $status = '勤務外';
                    }
                }
                //最後の登録が前日以前
                else{
                    $status = '勤務外';
                }
            }
            // 最後の登録が「休憩入」だったときのステータス
            elseif( Auth::user()->clocks()->latest()->first()['status'] == '休憩入' ){
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
        $clocks = Clock::where( 'member_id', Auth::id() )->get();

        $table = [];
        $previous = null;
        foreach( $clocks as $clock ){
            // 前のデータがないか、前のデータが違う日付のときか、その日の出勤statusが存在しないとき
            if( $previous == null ||
                !$clock['clock']->isSameDay( $previous['clock'] ) ||
                Clock::where( 'member_id', Auth::id() )
                    ->whereDate( 'clock', $clock['clock'] )
                    ->where( 'status', '出勤' )->get()->isEmpty() ){

                $table[ $clock['clock']->isoFormat('YYYY/MM/DD') ] = [
                    'clockin' => null,
                    'clockout' => null,
                    'break' => 0,
                    'sum' => 0
                ];
            }

            // &をつければ元の配列$tableも編集される
            // $date: $tableのキー(日付)
            // $row: 分解した1個の['clockin', 'clockout', 'break', 'sum']
            foreach( $table as $date => &$row ){
                // $clockの日付と$rowの$dateが同じときだけデータを入れる
                if( $date == $clock['clock']->isoFormat('YYYY/MM/DD') ){
                    // $clockが出勤の打刻のとき
                    if( $clock['status'] == '出勤' ){
                        $row['clockin'] = $clock['clock'];
                    }
                    // $clockが退勤の打刻のとき
                    elseif( $clock['status'] == '退勤' ){
                        // 同日に出勤の打刻があるとき
                        if( $row['clockin'] != null ){
                            $row['clockout'] = $clock['clock'];
                            $row['sum'] = $row['clockin']->diffInSeconds( $row['clockout'] )
                                - $row['break'];
                        }
                        // 退勤の打刻が日付をまたぐとき、前日(出勤の打刻した日)のデータに入れる
                        else{
                            $table[ $clock['clock']->subDay()->isoFormat('YYYY/MM/DD') ]['clockout']
                                = $clock['clock'];
                            $table[ $clock['clock']->subDay()->isoFormat('YYYY/MM/DD') ]['sum']
                                = $table[ $clock['clock']->subDay()->isoFormat('YYYY/MM/DD') ]['clockin']
                                ->diffInSeconds( $table[ $clock['clock']->subDay()->isoFormat('YYYY/MM/DD') ]['clockout'] )
                                - $table[ $clock['clock']->subDay()->isoFormat('YYYY/MM/DD') ]['break'];
                        }
                    }
                    // $clockが休憩入の打刻のとき
                    elseif( $clock['status'] == '休憩入' ){
                        $break = $clock['clock'];
                    }
                    // $clockが休憩戻の打刻のとき
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
    public function detail( $date )
    {
        // クエリパラメータから日付を取得
        $year = (int)str_split( $date, 4 )[0];
        $month = (int)str_split( str_split($date, 4)[1], 2 )[0];
        $day = (int)str_split( str_split($date, 4)[1], 2 )[1];
        $date = CarbonImmutable::parse( $year . '-' . $month . '-' . $day );

        // その日と次の日の打刻を取得
        $clocks = Clock::where( 'member_id', Auth::id() )->whereDate( 'clock', $date )->get();
        $tomorrows = Clock::where( 'member_id', Auth::id() )->whereDate( 'clock', $date->addDay() )->get();

        // その日の修正申請を取得
        $correction =
            Correction::where( 'member_id', Auth::id() )->whereDate( 'date', $date )
                ->latest()->first();

        return view( '/general/detail', compact( 'date', 'clocks', 'tomorrows', 'correction' ) );
    }

    // 申請一覧画面の表示
    public function request( Request $request ){

        // ?tab=doneだったとき
        if( $request->tab == 'done' ){
            $corrections =
                Correction::where( 'member_id', Auth::id() )->where( 'approve', '済' )->get();
        }
        // ?tab=yetかtabなしで表示したとき
        else{
            $corrections =
                Correction::where( 'member_id', Auth::id() )->where( 'approve', '未' )->get();
        }

        return view( '/general/request', compact( 'corrections' ) );
    }
}
