<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use Carbon\CarbonImmutable;
use Database\Seeders\MemberSeeder;
// use Database\Seeders\AdministratorSeeder;
use Database\Seeders\ClockTableSeeder;
// use Database\Seeders\CorrectionTableSeeder;
use App\Models\Member;
// use App\Models\Clock;


class DetailMemberTest extends TestCase
{
    // テスト後にデータベースをリセット
    use RefreshDatabase;

    // 表示内容の確認
    public function test_display()
    {
        // 一般ユーザのデータを作成
        $this->seed( MemberSeeder::class );
        $this->assertDatabaseHas('members', [
            'email' => 'member001@example.com',
        ]);

        // 打刻データを作成
        $this->seed( ClockTableSeeder::class );

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

        // 今日の勤怠詳細画面へアクセス
        $date = CarbonImmutable::now();
        $response = $this->get( '/attendance/detail/' . $date->isoFormat('YYYYMMDD') );
        $response->assertViewIs('.general.detail');
        $response->assertStatus(200);

        // ログインユーザが表示されているか
        $response->assertSeeText( Auth::guard('members')->user()->name );

        // その日が表示されているか
        $response->assertSeeText( $date->isoFormat('YYYY年') );
        $response->assertSeeText( $date->isoFormat('MM月DD日') );

        // 表示時刻が正しいか
        $response->assertSee( '09:00' );
        $response->assertSee( '19:00' );
        $response->assertSee( '13:00' );
        $response->assertSee( '15:00' );
    }
}
