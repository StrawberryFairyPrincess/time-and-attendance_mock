<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // ユーザのシーディング(10人ずつ)
        $this->call([
            // 管理者
            AdministratorSeeder::class,
            // 一般ユーザ
            MemberSeeder::class,
        ]);

        // Clocksテーブルへのシーディング処理
        $this->call(ClockTableSeeder::class);

        // Correctionsテーブルへのシーディング処理
        $this->call(CorrectionTableSeeder::class);
    }
}
