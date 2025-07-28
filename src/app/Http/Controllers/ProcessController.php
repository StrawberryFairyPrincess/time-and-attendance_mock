<?php

// 一般ユーザのpost処理用

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\CorrectionRequest;
use Illuminate\Support\Facades\Auth;
use Carbon\CarbonImmutable;
use App\Models\Clock;
use App\Models\Correction;


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
                    'sub' => $monthly->subMonth()
                ]);
        }
        // 翌月が押されたとき
        elseif ($request->has('add')) {
            return redirect('/attendance/list')
                ->with([ //セッションで値を渡す
                    'add' => $monthly->addMonth()
                ]);
        }
    }

    // 修正依頼
    public function correct( CorrectionRequest $request )
    {
        // クエリパラメータから日付を取得
        $year = (int)str_split( $request->date, 4 )[0];
        $month = (int)str_split( str_split($request->date, 4)[1], 2 )[0];
        $day = (int)str_split( str_split($request->date, 4)[1], 2 )[1];
        $date = CarbonImmutable::parse( $year . '-' . $month . '-' . $day );

        // takeとbackの最後の要素を見るために抽出
        $takekeys = [];
        $backkeys = [];
        foreach( array_keys( $request->validated() ) as $key ){
            if ( strpos( $key, 'take' ) !== false) {
                $takekeys[] = $key;
            }
            elseif ( strpos( $key, 'back' ) !== false) {
                $backkeys[] = $key;
            }
        }

        $breaks = [];
        $i = 1; // キーtakeとbackの振り番号をみる
        foreach( $request->validated() as $key => $value ) {
            // キーが'clockin'のとき
            if( $key == 'clockin' ){
                // 申請された時間が登録されている時間と同じとき
                if( $value ==
                    Clock::where( 'member_id', Auth::id() )->whereDate( 'clock', $date )
                        ->where( 'status', '出勤' )->first()['clock']->format('H:i') ){
                    $clockin = NULL;
                }
                // 申請された時間が登録されている時間から変更されているとき
                else{
                    $clockin = CarbonImmutable::parse( $year . '-' . $month . '-' . $day .
                                ' ' . $value );
                }
            }
            // キーが'clockout'のとき
            elseif( $key == 'clockout' ){
                // $valueが00:00のとき
                if( $value == '00:00' ){
                    $clockout = NULL;
                }
                // $valueが00:00じゃないとき
                else{
                    $h = (int)explode( ':', $value )[0];
                    $m = (int)explode( ':', $value )[1];

                    // 日を跨いだ退勤時間を申請するとき
                    if( $h > 24 ){
                        $h -= 24;

                        // 実際の打刻も日を跨いでいる
                        if( (int)explode( ':', $request['realout'] )[0] >= 24 ){
                            // 申請された時間が登録されている時間と同じとき
                            if( $value ==
                                Clock::where( 'member_id', Auth::id() )
                                    ->whereDate( 'clock', $date->addDay() )->where( 'status', '退勤' )
                                    ->first()['clock']->format('H:i') ){
                                $clockout = NULL;
                            }
                            // 申請された時間が登録されている時間から変更されているとき
                            else{
                                $clockout = CarbonImmutable::parse( $year . '-' . $month . '-' . $day .
                                            ' ' . $h . ':' . $m );
                            }
                        }
                        // 実際の打刻はその日中
                        else{
                            $next = $day + 1;
                            $clockout = CarbonImmutable::parse( $year . '-' . $month . '-' . $next .
                                        ' ' . $h . ':' . $m );
                        }
                    }
                    // その日中の退勤時間を申請するとき
                    else{
                        // 実際の打刻もその日中
                        if( (int)explode( ':', $request['realout'] )[0] < 24 ){
                            // 申請された時間が登録されている時間と同じとき
                            if( $value ==
                                Clock::where( 'member_id', Auth::id() )->whereDate( 'clock', $date )
                                    ->where( 'status', '退勤' )->latest()->first()['clock']->format('H:i') ){
                                $clockout = NULL;
                            }
                            // 申請された時間が登録されている時間から変更されているとき
                            else{
                                $clockout = CarbonImmutable::parse( $year . '-' . $month . '-' . $day .
                                            ' ' . $value );
                            }
                        }
                        // 実際の打刻は日を跨いでいる
                        else{
                            $clockout = CarbonImmutable::parse( $year . '-' . $month . '-' . $day .
                                        ' ' . $value );
                        }
                    }
                }
            }
            // 'take'から始まるキーのとき
            elseif( strpos( $key, 'take' ) === 0 ){
                // takeの$valueが00:00のとき
                if( $value == '00:00' ){
                    // 最後のキーのとき
                    if( $key == end( $takekeys ) ){
                        // backが00:00のとき
                        if( $request->validated()[ end( $backkeys ) ] == '00:00' ){
                        }
                        // backに時間の登録があるとき
                        else{
                            $breaks[ $key ] = NULL;
                        }
                    }
                    // 最後のキーじゃなければNULL扱い
                    else{
                        $breaks[ $key ] = NULL;
                    }
                }
                // $valueが00:00じゃないとき
                else{
                    // Clockに登録ののある休憩
                    foreach( Clock::where( 'member_id', Auth::id() )->whereDate( 'clock', $date )
                            ->where( 'status', '休憩入' )->get() as $j => $take ){
                        // キーに振られた番号を見て(最後の文字)foreachと同じ番目の時だけ
                        if( substr( $key, -1 ) == $i && ( $i == $j+1 ) ){
                            // 申請された時間が登録されている時間と同じとき
                            if( $value == $take['clock']->format('H:i') ){
                                $breaks[ $key ] = NULL;
                            }
                            // 申請された時間が登録されている時間から変更されているとき
                            else{
                                $breaks[ $key ] = $year . '-' . $month . '-' . $day . ' ' . $value . ':00';
                            }
                        }
                    }
                    // Clockに登録のない新しい休憩
                    if( $key == end( $takekeys ) && $value != NULL && !isset( $breaks[ $key ] ) ){
                        $breaks[ $key ] = $year . '-' . $month . '-' . $day . ' ' . $value . ':00';
                    }
                }
            }
            // 'back'から始まるキーのとき
            elseif( strpos( $key, 'back' ) === 0 ){
                // backの$valueが00:00のとき
                if( $value == '00:00' ){
                    // 最後のキーのとき
                    if( $key == end( $backkeys ) ){
                        // takeが00:00のとき
                        if( $request->validated()[ end( $takekeys ) ] == '00:00' ){
                        }
                        // takeに時間の登録があるとき
                        else{
                            $breaks[ $key ] = NULL;
                        }
                    }
                    // 最後のキーじゃなければNULL扱い
                    else{
                        $breaks[ $key ] = NULL;
                    }
                }
                // $valueが00:00じゃないとき
                else{
                    // Clockに登録ののある休憩
                    foreach( Clock::where( 'member_id', Auth::id() )->whereDate( 'clock', $date )
                            ->where( 'status', '休憩戻' )->get() as $j => $back ){
                        // キーに振られた番号を見て(最後の文字)foreachと同じ番目の時だけ
                        if( substr( $key, -1 ) == $i && ( $i == $j+1 ) ){
                            // 申請された時間が登録されている時間と同じとき
                            if( $value == $back['clock']->format('H:i') ){
                                $breaks[ $key ] = NULL;
                            }
                            // 申請された時間が登録されている時間から変更されているとき
                            else{
                                $breaks[ $key ] = $year . '-' . $month . '-' . $day . ' ' . $value . ':00';
                            }
                        }
                    }
                    // Clockに登録のない新しい休憩
                    if( $key == end( $backkeys ) && $value != NULL && !isset( $breaks[ $key ] ) ){
                        $breaks[ $key ] = $year . '-' . $month . '-' . $day . ' ' . $value . ':00';
                    }

                    $i++;
                }
            }
        }

        // Correctionsデータベースに追加
        $correction = [
            'member_id' => Auth::id(),
            'date' => $date,
            'clockin' => $clockin,
            'clockout' => $clockout,
            'breaks' => $breaks,
            'remarks' => $request->validated()['remarks'],
            'approve' => '未'
        ];
        Correction::create( $correction );

        return redirect()->back();
    }
}
