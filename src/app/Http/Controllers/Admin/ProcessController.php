<?php

// 管理者のpost処理用

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Date;
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
        $response = new StreamedResponse(function () use ($csvHeader, $csvData) {

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
