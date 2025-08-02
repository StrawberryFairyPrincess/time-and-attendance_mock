<?php

// 管理者のpost処理用

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\CorrectionRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;
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

    public function approve( Request $request ){

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

        // 出勤の申請があるとき更新
        if( $correction['clockin'] != NULL ){
            $clock = $clocks->where( 'status', '出勤' )->first();
            $clock['clock'] = $correction['clockin'];
            $clock['updated_at'] = CarbonImmutable::now();
            $clock->save();
        }

        // 退勤の申請があるとき更新
        if( $correction['clockout'] != NULL ){

            // 実際の退勤があるとき
            if( isset( $request['realout'] ) ){
                // 実際の退勤を取得(退勤の申請があるときだけ取得される)
                $realout = CarbonImmutable::parse( $request['realout'] );

                // 実際の退勤が当日
                if( $realout->isSameDay( $date ) ){
                    $clock = $clocks->where( 'status', '退勤' )->last();
                }
                // 実際の退勤が翌日
                else{
                    $clock = $tomorrows->where( 'status', '退勤' )->first();
                }

                $clock['clock'] = $correction['clockout'];
                $clock['updated_at'] = CarbonImmutable::now();
                $clock->save();
            }
            // 実際の退勤がないとき
            else{
                // 新しくレコードを作成
                $clock = [
                    'member_id' => $request->id,
                    'clock' => $correction['clockout'],
                    'status' => '退勤',
                    'created_at' => CarbonImmutable::now()
                ];
                Clock::create( $clock );
            }
        }

        $takes =
            Clock::where( 'member_id', $request->id )->where( 'status', '休憩入' )
                ->whereDate( 'clock', $date )->orderBy( 'clock', 'asc' )->get();
        $backs =
            Clock::where( 'member_id', $request->id )->where( 'status', '休憩戻' )
                ->whereDate( 'clock', $date )->orderBy( 'clock', 'asc' )->get();
        $i = 0;
        $j = 0;
        foreach( $correction['breaks'] as $key => $time ){

            // キーが休憩入
            if( strpos( $key, 'take' ) === 0 ){

                // 休憩入の申請があるとき
                if( $time != NULL ){

                    // 実際の休憩入があるとき
                    if( isset( $takes[ $i ] ) ){
                        $clock = $takes[ $i ];
                        $clock['clock'] = CarbonImmutable::parse( $correction['breaks'][ $key ] );
                        $clock['updated_at'] = CarbonImmutable::now();
                        $clock->save();
                    }
                    // 実際の休憩入がないとき(新しく登録する休憩)
                    else{
                        // 新しくレコードを作成
                        $clock = [
                            'member_id' => $request->id,
                            'clock' => CarbonImmutable::parse( $correction['breaks'][ $key ] ),
                            'status' => '休憩入',
                            'created_at' => CarbonImmutable::now()
                        ];
                        Clock::create( $clock );
                    }
                }

                $i++;
            }

            // キーが休憩戻
            if( strpos( $key, 'back' ) === 0 ){

                // 休憩戻の申請があるとき
                if( $time != NULL ){

                    // 実際の休憩戻があるとき
                    if( isset( $backs[ $j ] ) ){
                        $clock = $backs[ $j ];
                        $clock['clock'] = CarbonImmutable::parse( $correction['breaks'][ $key ] );
                        $clock['updated_at'] = CarbonImmutable::now();
                        $clock->save();
                    }
                    // 実際の休憩戻がないとき(新しく登録する休憩)
                    else{
                        // 新しくレコードを作成
                        $clock = [
                            'member_id' => $request->id,
                            'clock' => CarbonImmutable::parse( $correction['breaks'][ $key ] ),
                            'status' => '休憩戻',
                            'created_at' => CarbonImmutable::now()
                        ];
                        Clock::create( $clock );
                    }
                }

                $j++;
            }
        }

        // Correctionsテーブルを済にする
        $correction['approve'] = '済';
        $correction['updated_at'] = CarbonImmutable::now();
        $correction->save();

        return redirect()->back();
    }

    // 勤怠修正
    public function correct( CorrectionRequest $request ){

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

        // takeとbackの最後の要素を見るためにキーを抽出
        $takekeys = [];
        $backkeys = [];
        foreach( array_keys( $request->validated() ) as $key ){
            if ( strpos( $key, 'take' ) !== false ){
                $takekeys[] = $key;
            }
            elseif ( strpos( $key, 'back' ) !== false ){
                $backkeys[] = $key;
            }
        }

        $breaks = [];
        $i = 1; // キーtakeとbackの振り番号をみる
        foreach( $request->validated() as $key => $value ){

            // キーが'clockin'のとき
            if( $key == 'clockin' ){
                // 申請された時間が登録されている時間から変更されているとき
                if( $value !=
                    Clock::where( 'member_id', $request->id )
                        ->orderBy( 'clock', 'asc' )->whereDate( 'clock', $date )
                        ->where( 'status', '出勤' )->first()['clock']->format('H:i') ){

                    // Clockテーブル
                    $clock = $clocks->where( 'status', '出勤' )->first();
                    $clock['clock'] =
                        CarbonImmutable::parse( $year . '-' . $month . '-' . $day . ' ' . $value );
                    $clock['updated_at'] = CarbonImmutable::now();
                    $clock->save();

                    // Correctionテーブル
                    $clockin =
                        CarbonImmutable::parse( $year . '-' . $month . '-' . $day . ' ' . $value );
                }
                // 申請された時間が登録されている時間と同じとき
                else{
                    $clockin = NULL;
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

                        // 実際の退勤のデータがないとき
                        if( empty( $request['realout'] ) ){
                            // Clockテーブル
                            $clock = [
                                'member_id' => $request->id,
                                'clock' => CarbonImmutable::parse( $year . '-' . $month . '-' . $day . ' ' . $h . ':' . $m ),
                                'status' => '退勤',
                                'created_at' => CarbonImmutable::now()
                            ];
                            Clock::create( $clock );

                            // Correctionテーブル
                            $clockout =
                                CarbonImmutable::parse( $year . '-' . $month . '-' . $day . ' ' . $h . ':' . $m );
                        }
                        // 実際の打刻も日を跨いでいる
                        elseif( (int)explode( ':', $request['realout'] )[0] >= 24 ){
                            // 申請された時間が登録されている時間から変更されているとき
                            if( $value !=
                                Clock::where( 'member_id', $request->id )->where( 'status', '退勤' )
                                ->whereDate( 'clock', $date->addDay() )->orderBy( 'clock', 'asc' )
                                ->first()['clock']->format('H:i') ){

                                // Clockテーブル
                                $clock = $tomorrows->where( 'status', '退勤' )->first();
                                $clock['clock'] = CarbonImmutable::parse( $year . '-' . $month . '-' . $day . ' ' . $h . ':' . $m );
                                $clock['updated_at'] = CarbonImmutable::now();
                                $clock->save();

                                // Correctionテーブル
                                $clockout =
                                    CarbonImmutable::parse( $year . '-' . $month . '-' . $day .
                                    ' ' . $h . ':' . $m );
                            }
                            // 申請された時間が登録されている時間と同じとき
                            else{
                                $clockout = NULL;
                            }
                        }
                        // 実際の打刻はその日中
                        else{
                            $next = $day + 1;

                            // Clockテーブル
                            $clock = $clocks->where( 'status', '退勤' )->last();
                            $clock['clock'] = CarbonImmutable::parse( $year . '-' . $month . '-' . $next . ' ' . $h . ':' . $m );
                            $clock['updated_at'] = CarbonImmutable::now();
                            $clock->save();

                            // Correctionテーブル
                            $clockout = CarbonImmutable::parse( $year . '-' . $month . '-' . $next .
                                                                ' ' . $h . ':' . $m );
                        }
                    }
                    // その日中の退勤時間を申請するとき
                    else{
                        // 実際の退勤のデータがないとき
                        if( empty( $request['realout'] ) ){
                            // Clockテーブル
                            $clock = [
                                'member_id' => $request->id,
                                'clock' => CarbonImmutable::parse( $year . '-' . $month . '-' . $day . ' ' . $h . ':' . $m ),
                                'status' => '退勤',
                                'created_at' => CarbonImmutable::now()
                            ];
                            Clock::create( $clock );

                            // Correctionテーブル
                            $clockout =
                                CarbonImmutable::parse( $year . '-' . $month . '-' . $day .
                                                        ' ' . $h . ':' . $m );
                        }
                        // 実際の打刻もその日中
                        elseif( (int)explode( ':', $request['realout'] )[0] < 24 ){
                            // 申請された時間が登録されている時間から変更されているとき
                            if( $value !=
                                Clock::where( 'member_id', $request->id )->where( 'status', '退勤' )
                                    ->whereDate( 'clock', $date )->orderBy( 'clock', 'desc' )
                                    ->first()['clock']->format('H:i') ){

                                // Clockテーブル
                                $clock = $clocks->where( 'status', '退勤' )->last();
                                $clock['clock'] = CarbonImmutable::parse( $year . '-' . $month . '-' . $day . ' ' . $value );
                                $clock['updated_at'] = CarbonImmutable::now();
                                $clock->save();

                                // Correctionテーブル
                                $clockout =
                                    CarbonImmutable::parse( $year . '-' . $month . '-' . $day .
                                                            ' ' . $value );
                            }
                            // 申請された時間が登録されている時間と同じとき
                            else{
                                $clockout = NULL;
                            }
                        }
                        // 実際の打刻は日を跨いでいる
                        else{
                            // Clockテーブル
                            $clock = $tomorrows->where( 'status', '退勤' )->first();
                            $clock['clock'] = CarbonImmutable::parse( $year . '-' . $month . '-' . $day . ' ' . $value );
                            $clock['updated_at'] = CarbonImmutable::now();
                            $clock->save();

                            // Correctionテーブル
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
                    // Clockに登録のある休憩
                    foreach( Clock::where( 'member_id', $request->id )->where( 'status', '休憩入' )
                                ->whereDate( 'clock', $date )->orderBy( 'clock', 'asc' )->get()
                            as $j => $take ){

                        // キーに振られた番号を見て(最後の文字)foreachと同じ番目の時だけ
                        if( substr( $key, -1 ) == $i && ( $i == $j+1 ) ){
                            // 申請された時間が登録されている時間から変更されているとき
                            if( $value != $take['clock']->format('H:i') ){
                                // Clockテーブル
                                $clock = $take;
                                $clock['clock'] = CarbonImmutable::parse( $year . '-' . $month . '-' . $day . ' ' . $value );
                                $clock['updated_at'] = CarbonImmutable::now();
                                $clock->save();

                                // Correctionテーブル
                                $breaks[ $key ] =
                                    $year . '-' . $month . '-' . $day . ' ' . $value . ':00';
                            }
                            // 申請された時間が登録されている時間と同じとき
                            else{
                                $breaks[ $key ] = NULL;
                            }
                        }
                    }
                    // Clockに登録のない新しい休憩
                    if( $key == end( $takekeys ) &&
                        empty( Clock::where( 'member_id', $request->id )->where( 'status', '休憩入' )
                        ->whereDate( 'clock', $date )->orderBy( 'clock', 'asc' )->get()[ $j+1 ] ) ){

                        // Clockテーブル
                        $clock = [
                            'member_id' => $request->id,
                            'clock' => CarbonImmutable::parse( $year . '-' . $month . '-' . $day . ' ' . $value ),
                            'status' => '休憩入',
                            'created_at' => CarbonImmutable::now()
                        ];
                        Clock::create( $clock );

                        // Correctionテーブル
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
                    foreach( Clock::where( 'member_id', $request->id )->where( 'status', '休憩戻' )
                                ->whereDate( 'clock', $date )->orderBy( 'clock', 'asc' )->get()
                            as $j => $back ){

                        // キーに振られた番号を見て(最後の文字)foreachと同じ番目の時だけ
                        if( substr( $key, -1 ) == $i && ( $i == $j+1 ) ){
                            // 申請された時間が登録されている時間から変更されているとき
                            if( $value != $back['clock']->format('H:i') ){
                                // Clockテーブル
                                $clock = $back;
                                $clock['clock'] = CarbonImmutable::parse( $year . '-' . $month . '-' . $day . ' ' . $value );
                                $clock['updated_at'] = CarbonImmutable::now();
                                $clock->save();

                                // Correctionテーブル
                                $breaks[ $key ] =
                                    $year . '-' . $month . '-' . $day . ' ' . $value . ':00';
                            }
                            // 申請された時間が登録されている時間と同じとき
                            else{
                                $breaks[ $key ] = NULL;
                            }
                        }
                    }
                    // Clockに登録のない新しい休憩
                    if( $key == end( $backkeys ) &&
                        empty( Clock::where( 'member_id', $request->id )->where( 'status', '休憩戻' )
                        ->whereDate( 'clock', $date )->orderBy( 'clock', 'asc' )->get()[ $j+1 ] ) ){

                        // Clockテーブル
                        $clock = [
                            'member_id' => $request->id,
                            'clock' => CarbonImmutable::parse( $year . '-' . $month . '-' . $day . ' ' . $value ),
                            'status' => '休憩戻',
                            'created_at' => CarbonImmutable::now()
                        ];
                        Clock::create( $clock );

                        // Correctionテーブル
                        $breaks[ $key ] = $year . '-' . $month . '-' . $day . ' ' . $value . ':00';
                    }

                    $i++;
                }
            }
        }

        // Correctionsデータベースに追加
        $correction = [
            'member_id' => $request->id,
            'date' => $date,
            'clockin' => $clockin,
            'clockout' => $clockout,
            'breaks' => $breaks,
            'remarks' => $request->validated()['remarks'],
            'approve' => '済'
        ];
        Correction::create( $correction );

        return redirect( '/admin/users/' . $request->id . '/attendances' );
    }

    // CSVエクスポート機能
    public function download( Request $request ){

        $csvHeader = [
            'date',
            'clock_in',
            'clock_out',
            'break',
            'sum',
        ];

        $csvData = [];
        foreach( json_decode( $request->table ) as $date => $row ){
            $csvData[] = [
                'date' => $date,
                'clock_in' => $row->clockin,
                'clock_out' => $row->clockout,
                'break' => gmdate( "H:i", $row->break ),
                'sum' => gmdate( "H:i", $row->sum ),
            ];
        }

        $date = CarbonImmutable::parse( $request->date )->format('Y-m');
        $response = new StreamedResponse( function () use ($csvHeader, $csvData) {

            $createCsvFile = fopen('php://output', 'w');

            mb_convert_variables('SJIS-win', 'UTF-8', $csvHeader);
            mb_convert_variables('SJIS-win', 'UTF-8', $csvData);

            fputcsv( $createCsvFile, $csvHeader );
            foreach( $csvData as $csv ){
                fputcsv( $createCsvFile, $csv );
            }

            fclose( $createCsvFile );

        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' =>
                'attachment; filename="id_' . $request->id . '_month_' . $date . '.csv"',
        ]);

        return $response;
    }
}
