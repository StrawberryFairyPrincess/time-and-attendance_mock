<?php

// 管理者の表示用(get処理)

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\CarbonImmutable;
use App\Models\Member;
use App\Models\Clock;
use App\Models\Correction;


class DisplayController extends Controller
{
    // 勤怠一覧画面の表示
    public function index( Request $request )
    {
        // 前日が押されたとき
        if( $request->session()->has('sub') ){
            $today = $request->session()->get('sub');
        }
        // 翌日が押されたとき
        elseif( $request->session()->has('add') ){
            $today = $request->session()->get('add');
        }
        // 画面を最初に表示したときの日時
        else{
            $today = CarbonImmutable::now();
        }

        // その日の打刻情報を全て取得
        $clocks = Clock::whereDate( 'clock', $today )->orderBy( 'clock', 'asc' )->get();
        $tomorrows = Clock::whereDate( 'clock', $today->addDay() )->orderBy( 'clock', 'asc' )->get();

        $table = [];
        $previous = null;
        foreach( $clocks->where( 'status', '出勤' )->pluck('member_id')->unique() as $member ){

            // 当日の出勤のデータがデータベースに存在して、
            // $memberの$tableのデータを作ってなかったら作る
            if( Clock::where( 'member_id', $member )->where( 'status', '出勤' )
                    ->orderBy( 'clock', 'asc' )->whereDate( 'clock', $today )->exists()
                && !isset( $table[ $member ] ) ){

                    $table[ $member ] = [
                        'name' => Member::where( 'id', $member )->first()['name'],
                        'clockin' => null,
                        'clockout' => null,
                        'break' => 0,
                        'sum' => 0
                    ];
            }

            foreach( $clocks->where( 'member_id', $member ) as $clock ){

                // $clockが出勤の打刻のとき
                if( $clock['status'] == '出勤' ){
                    $table[ $member ]['clockin'] = $clock['clock'];
                }
                // $clockが休憩入の打刻のとき
                elseif( $clock['status'] == '休憩入' ){
                    $break = $clock['clock'];
                }
                // $clockが休憩戻の打刻のとき
                elseif( $clock['status'] == '休憩戻' ){
                    $table[ $member ]['break'] += $break->diffInSeconds( $clock['clock'] );
                }

            }

            // 退勤の処理
            // この日に退勤のデータがあるとき
            if( $clocks->where( 'member_id', $member )
                ->where( 'status', '退勤' )->first() != NULL ){

                // その日最後の退勤のデータが当日の出勤に対する退勤のとき
                if( $clocks->where( 'member_id', $member )
                        ->where( 'status', '退勤' )->last()['clock']
                    ->isAfter( $clocks->where( 'member_id', $member )
                        ->where( 'status', '出勤' )->last()['clock'] ) ){

                    $table[ $member ]['clockout'] =
                        $clocks->where( 'member_id', $member )
                        ->where( 'status', '退勤' )->last()['clock'];

                    if( $table[ $member ]['clockin'] != null ){
                        $table[ $member ]['sum'] = $table[ $member ]['clockin']
                            ->diffInSeconds( $table[ $member ]['clockout'] )
                            - $table[ $member ]['break'];
                    }
                }
                // その日最後の退勤のデータが前日に対する退勤のとき
                else{
                    // 翌日に退勤のデータがあるとき
                    if( $tomorrows->where( 'member_id', $member )
                        ->where( 'status', '退勤' )->first() != NULL ){

                        $table[ $member ]['clockout'] =
                            $tomorrows->where( 'member_id', $member )
                            ->where( 'status', '退勤' )->first()['clock'];

                        if( $table[ $member ]['clockin'] != null ){
                            $table[ $member ]['sum'] = $table[ $member ]['clockin']
                                ->diffInSeconds( $table[ $member ]['clockout'] )
                                - $table[ $member ]['break'];
                        }
                    }
                    // 翌日に退勤のデータがないとき
                    else{
                        // $table[ $member ]['clockout']はnull
                    }
                }
            }
            // この日に退勤のデータがないとき
            else{
                // 翌日に退勤のデータがあるとき
                if( $tomorrows->where( 'member_id', $member )
                    ->where( 'status', '退勤' )->first() != NULL ){

                    $table[ $member ]['clockout'] =
                        $tomorrows->where( 'member_id', $member )
                        ->where( 'status', '退勤' )->first()['clock'];

                    if( $table[ $member ]['clockin'] != null ){
                        $table[ $member ]['sum'] = $table[ $member ]['clockin']
                            ->diffInSeconds( $table[ $member ]['clockout'] )
                            - $table[ $member ]['break'];
                    }
                }
                // 翌日に退勤のデータがないとき
                else{
                    // $table[ $member ]['clockout']はnull
                }
            }

        }

        return view( 'admin/index', compact( 'today', 'table' ) );
    }

    // 勤怠詳細画面の表示
    public function detail( Request $request )
    {
        // クエリパラメータから一般ユーザIDを取得
        $member = Member::where( 'id', $request->id )->first();

        // クエリパラメータから日付を取得
        $year = (int)str_split( $request->date, 4 )[0];
        $month = (int)str_split( str_split( $request->date, 4 )[1], 2 )[0];
        $day = (int)str_split( str_split( $request->date, 4 )[1], 2 )[1];
        $date = CarbonImmutable::parse( $year . '-' . $month . '-' . $day );

        // その日と次の日の打刻を取得
        $clocks =
            Clock::where( 'member_id', $request->id )
                ->whereDate( 'clock', $date )->orderBy( 'clock', 'asc' )->get();
        $tomorrows =
            Clock::where( 'member_id', $request->id )
                ->whereDate( 'clock', $date->addDay() )->orderBy( 'clock', 'asc' )->get();

        // その日の修正申請を取得
        $correction =
            Correction::where( 'member_id', $request->id )
                ->whereDate( 'date', $date )->orderBy( 'date', 'desc' )->first();

        return view( '/admin/detail',
            compact( 'member', 'date', 'clocks', 'tomorrows', 'correction' ) );
    }

    // 修正申請承認画面の表示
    public function approve( Request $request )
    {
        // クエリパラメータから一般ユーザIDを取得
        $member = Member::where( 'id', $request->id )->first();

        // クエリパラメータから日付を取得
        $year = (int)str_split( $request->date, 4 )[0];
        $month = (int)str_split( str_split( $request->date, 4 )[1], 2 )[0];
        $day = (int)str_split( str_split( $request->date, 4 )[1], 2 )[1];
        $date = CarbonImmutable::parse( $year . '-' . $month . '-' . $day );

        // その日と次の日の打刻を取得
        $clocks =
            Clock::where( 'member_id', $request->id )
                ->whereDate( 'clock', $date )->orderBy( 'clock', 'asc' )->get();
        $tomorrows =
            Clock::where( 'member_id', $request->id )
                ->whereDate( 'clock', $date->addDay() )->orderBy( 'clock', 'asc' )->get();

        // その日の修正申請を取得
        $correction =
            Correction::where( 'member_id', $request->id )
                ->whereDate( 'date', $date )->orderBy( 'date', 'desc' )->first();

        return view( '/admin/approve',
            compact( 'member', 'date', 'clocks', 'tomorrows', 'correction' ) );
    }

    // 申請一覧画面の表示
    public function request( Request $request ){

        // ?tab=doneだったとき
        if( $request->tab == 'done' ){
            $corrections =
                Correction::where( 'approve', '済' )->orderBy( 'date', 'asc' )->get();
        }
        // ?tab=yetかtabなしで表示したとき
        else{
            $corrections =
                Correction::where( 'approve', '未' )->orderBy( 'date', 'asc' )->get();
        }

        return view( '/admin/request', compact( 'corrections' ) );
    }

    // スタッフ一覧画面の表示
    public function staff()
    {
        $members = Member::all();

        return view( '/admin/staff', compact( 'members' ) );
    }

    // スタッフ別勤怠一覧画面の表示
    public function individual( Request $request )
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

        $member = Member::where( 'id', $request->member_id )->first();

        // 自分の打刻情報を全て取得
        $clocks = Clock::where( 'member_id', $request->member_id )->orderBy( 'clock', 'asc' )->get();

        $table = [];
        $previous = null;
        foreach( $clocks as $clock ){
            // 前のデータがないか、前のデータが違う日付のときか、その日の出勤statusが存在しないとき
            if( $previous == null ||
                !$clock['clock']->isSameDay( $previous['clock'] ) ||
                Clock::where( 'member_id', $request->member_id )
                    ->whereDate( 'clock', $clock['clock'] )->orderBy( 'clock', 'asc' )
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

        return view( '/admin/individual', compact( 'member', 'today', 'table' ) );
    }
}
