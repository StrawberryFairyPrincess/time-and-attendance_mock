<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Faker\Factory;
use App\Models\Member;


class MemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Member::truncate();

        $member_data = [];
        for( $i = 1; $i <= 10; $i++ ){
            $member_data[] = [
                'id' => $i,
                'email' => sprintf('member%03d@example.com', $i),
                'password' => sprintf('pass%04d', $i),
            ];
        }

        $faker = Factory::create('ja_JP');
        foreach( $member_data as $data ){
            $member = new Member();
            $member->id = $data['id'];
            $member->name = $faker->name();
            $member->email = $data['email'];
            $member->password = Hash::make($data['password']);
            $member->save();
        }
    }
}
