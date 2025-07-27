<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory;
// use App\Models\Correction;
use Carbon\CarbonImmutable;


class CorrectionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Factory::create();
        $today = CarbonImmutable::today();

        for( $i=0; $i<20; $i++ ){

            $date = CarbonImmutable::parse(
                $faker->dateTimeBetween( $today->startOfMonth(), $today->endOfMonth() )
            );

            $param = [
                'id' => $i+1,
                'member_id' => $faker->randomElement([ 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 ]),
                'date' => $date,
                'clockin' => $date->setTime( 8, 30, 00 ),
                'clockout' => $date->setTime( 20, 30, 00 ),
                'breaks' => '{"back1": null, "take1": null}',
                'remarks' => 'シーディング',
                'approve' => '未',
                'created_at' => $date->addDay()
            ];

            DB::table('corrections')->insert( $param );
        }
    }
}
