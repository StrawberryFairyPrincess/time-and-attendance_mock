<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;


class ClockTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $today = CarbonImmutable::today();

        $k = 1;
        for( $i=1; $i<=$today->endOfMonth()->day; $i++ ){

            $date = $today->startOfMonth()->addDays($i-1);

            for( $j=1; $j<=10 ; $j++ ){
                $param = [
                    'id' => $k++,
                    'member_id' => $j,
                    'clock' => $date->setTime( 9, 00, 00 ),
                    'status' => '出勤',
                ];
                DB::table('clocks')->insert( $param );

                $param = [
                    'id' => $k++,
                    'member_id' => $j,
                    'clock' => $date->setTime( 13, 00, 00 ),
                    'status' => '休憩入',
                ];
                DB::table('clocks')->insert( $param );

                $param = [
                    'id' => $k++,
                    'member_id' => $j,
                    'clock' => $date->setTime( 15, 00, 00 ),
                    'status' => '休憩戻',
                ];
                DB::table('clocks')->insert( $param );

                $param = [
                    'id' => $k++,
                    'member_id' => $j,
                    'clock' => $date->setTime( 19, 00, 00 ),
                    'status' => '退勤',
                ];
                DB::table('clocks')->insert( $param );
            }
        }
    }
}
