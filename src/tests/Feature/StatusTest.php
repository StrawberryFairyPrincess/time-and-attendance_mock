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
use App\Models\Clock;


class StatusTest extends TestCase
{
    // テスト後にデータベースをリセット
    use RefreshDatabase;

    // 勤務外
    public function test_off()
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

        // 表示ステータスを確認
        $response->assertSeeText( '勤務外' );
    }

    // 出勤中
    public function test_working()
    {
        // 一般ユーザのデータを作成
        $this->seed( MemberSeeder::class );
        $this->assertDatabaseHas('members', [
            'email' => 'member001@example.com',
        ]);

        // 最新の打刻データを作成
        $clock = [
            'member_id' => 1,
            'clock' => CarbonImmutable::now(),
            'status' => '出勤',
        ];
        Clock::create( $clock );

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

        // 表示ステータスを確認
        $response->assertSeeText( '出勤中' );
    }

    // 休憩中
    public function test_break()
    {
        // 一般ユーザのデータを作成
        $this->seed( MemberSeeder::class );
        $this->assertDatabaseHas('members', [
            'email' => 'member001@example.com',
        ]);

        // 最新の打刻データを作成
        $clock = [
            'member_id' => 1,
            'clock' => CarbonImmutable::now(),
            'status' => '休憩入',
        ];
        Clock::create( $clock );

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

        // 表示ステータスを確認
        $response->assertSeeText( '休憩中' );
    }

    // 退勤済
    public function test_finished()
    {
        // 一般ユーザのデータを作成
        $this->seed( MemberSeeder::class );
        $this->assertDatabaseHas('members', [
            'email' => 'member001@example.com',
        ]);

        // 最新の打刻データを作成
        $clock = [
            'member_id' => 1,
            'clock' => CarbonImmutable::now()->subHour(),
            'status' => '出勤',
        ];
        Clock::create( $clock );
        $clock = [
            'member_id' => 1,
            'clock' => CarbonImmutable::now(),
            'status' => '退勤',
        ];
        Clock::create( $clock );

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

        // 表示ステータスを確認
        $response->assertSeeText( '退勤済' );
    }
}
