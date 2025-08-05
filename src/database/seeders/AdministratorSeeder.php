<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Faker\Factory;
use App\Models\Administrator;


class AdministratorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Administrator::truncate();

        $administrators_data = [];
        for( $i = 1; $i <= 10; $i++ ){
            $administrators_data[] = [
                'id' => $i,
                'email' => sprintf('admin%03d@example.com', $i),
                'password' => sprintf('pass%04d', $i),
            ];
        }

        $faker = Factory::create('ja_JP');
        foreach( $administrators_data as $data ){
            $administrator = new Administrator();
            $administrator->id = $data['id'];
            $administrator->name = $faker->name();
            $administrator->email = $data['email'];
            $administrator->password = Hash::make($data['password']);
            $administrator->save();
        }
    }
}
