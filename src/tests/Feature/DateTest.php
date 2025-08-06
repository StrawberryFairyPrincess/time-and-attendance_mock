<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use Carbon\CarbonImmutable;
use Database\Seeders\MemberSeeder;
// use Database\Seeders\AdministratorSeeder;
// use Database\Seeders\ClockTableSeeder;
// use Database\Seeders\CorrectionTableSeeder;
use App\Models\Member;


class DateTest extends TestCase
{
    // テスト後にデータベースをリセット
    use RefreshDatabase;

    public function test_date()
    {
        // 一般ユーザのデータを作成
        $this->seed( MemberSeeder::class );
        $this->assertDatabaseHas('members', [
            'email' => 'member001@example.com',
        ]);

        // ログインしていない
        $this->assertFalse( Auth::guard('members')->check() );

        // ログインする(id=1の人)
        $member = Member::where( 'id', 1 )->first();
        $member['email_verified_at'] = CarbonImmutable::now();
        $this->actingAs( $member, 'members' );

        // ログインしている
        $this->assertTrue( Auth::guard('members')->check() );

        // メール認証している
        $this->assertTrue( Auth::user()->hasVerifiedEmail() );

        // 出勤登録画面へのアクセス
        $response = $this->get('/attendance');
        $response->assertViewIs('.general.clock');
        $response->assertStatus(200);

        // 表示時刻の確認
        $date = CarbonImmutable::now();
        $response->assertSeeText( $date->isoFormat('YYYY年MM月DD日(ddd)') );
        $response->assertSeeText( $date->format('H:i') );
    }
}
