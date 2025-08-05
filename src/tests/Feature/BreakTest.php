<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;
use Carbon\CarbonImmutable;
use Database\Seeders\MemberSeeder;
// use Database\Seeders\AdministratorSeeder;
// use Database\Seeders\ClockTableSeeder;
// use Database\Seeders\CorrectionTableSeeder;
use App\Models\Member;
use App\Models\Clock;


class BreakTest extends TestCase
{
    // テスト後にデータベースをリセット
    use RefreshDatabase;

    // 休憩入ボタン機能
    public function test_take_button()
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

        // ログインしていない
        $this->assertFalse( Auth::guard('members')->check() );

        // ログイン画面へのアクセス
        $response = $this->get('/login');
        $response->assertViewIs('.general.auth.login');
        $response->assertStatus(200);

        // ログインする(id=1の人)
        $requestParams = [
            'email' => 'member001@example.com',
            'password' => 'pass0001',
            'user_type' => 'general'
        ];
        $response = $this->post( '/login', $requestParams );
        $response->assertRedirect('/attendance');
        $response->assertStatus(302);

        // ログインしている
        $this->assertTrue( Auth::guard('members')->check() );

        // メールリンクのURLを生成
        $member = Member::where( 'email', $requestParams['email'] )->first();
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [ 'id' => $member->id, 'hash' => sha1( $member->getEmailForVerification() ) ]
        );

        // メールリンクをクリック
        $response = $this->get( $verificationUrl );
        $response->assertRedirect('/attendance');
        $response->assertStatus(302);

        // ユーザがメール認証できたか
        $this->assertTrue( Auth::user()->hasVerifiedEmail() );

        // 出勤登録画面へのアクセス
        $response = $this->get('/attendance');
        $response->assertViewIs('.general.clock');
        $response->assertStatus(200);

        // 表示ステータスを確認
        $response->assertSeeText( '出勤中' );

        // 表示ボタンを確認
        $response->assertSee( '休憩入' );

        // ボタンを押す
        $response = $this->post( '/attendance', [ 'break' => '休憩入' ] );
        $response->assertRedirect('/attendance');
        $response->assertStatus(302);
        $response = $this->get('/attendance');
        $response->assertViewIs('.general.clock');
        $response->assertStatus(200);

        // 表示ステータスを確認
        $response->assertSeeText( '休憩中' );
    }

    // 休憩入は何回でも
    public function test_many_take()
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

        // ログインしていない
        $this->assertFalse( Auth::guard('members')->check() );

        // ログイン画面へのアクセス
        $response = $this->get('/login');
        $response->assertViewIs('.general.auth.login');
        $response->assertStatus(200);

        // ログインする(id=1の人)
        $requestParams = [
            'email' => 'member001@example.com',
            'password' => 'pass0001',
            'user_type' => 'general'
        ];
        $response = $this->post( '/login', $requestParams );
        $response->assertRedirect('/attendance');
        $response->assertStatus(302);

        // ログインしている
        $this->assertTrue( Auth::guard('members')->check() );

        // メールリンクのURLを生成
        $member = Member::where( 'email', $requestParams['email'] )->first();
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [ 'id' => $member->id, 'hash' => sha1( $member->getEmailForVerification() ) ]
        );

        // メールリンクをクリック
        $response = $this->get( $verificationUrl );
        $response->assertRedirect('/attendance');
        $response->assertStatus(302);

        // ユーザがメール認証できたか
        $this->assertTrue( Auth::user()->hasVerifiedEmail() );

        // 出勤登録画面へのアクセス
        $response = $this->get('/attendance');
        $response->assertViewIs('.general.clock');
        $response->assertStatus(200);

        // 表示ステータスを確認
        $response->assertSeeText( '出勤中' );

        // 表示ボタンを確認
        $response->assertSee( '休憩入' );

        // ボタンを押す
        $response = $this->post( '/attendance', [ 'break' => '休憩入' ] );
        $response->assertRedirect('/attendance');
        $response->assertStatus(302);
        $response = $this->get('/attendance');
        $response->assertViewIs('.general.clock');
        $response->assertStatus(200);

        // 表示ステータスを確認
        $response->assertSeeText( '休憩中' );

        // 表示ボタンを確認
        $response->assertSee( '休憩戻' );

        // ボタンを押す
        $response = $this->post( '/attendance', [ 'back' => '休憩戻' ] );
        $response->assertRedirect('/attendance');
        $response->assertStatus(302);
        $response = $this->get('/attendance');
        $response->assertViewIs('.general.clock');
        $response->assertStatus(200);

        // 表示ボタンを確認
        $response->assertSee( '休憩入' );
    }

    // 休憩戻ボタン機能
    public function test_back_button()
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

        // ログインしていない
        $this->assertFalse( Auth::guard('members')->check() );

        // ログイン画面へのアクセス
        $response = $this->get('/login');
        $response->assertViewIs('.general.auth.login');
        $response->assertStatus(200);

        // ログインする(id=1の人)
        $requestParams = [
            'email' => 'member001@example.com',
            'password' => 'pass0001',
            'user_type' => 'general'
        ];
        $response = $this->post( '/login', $requestParams );
        $response->assertRedirect('/attendance');
        $response->assertStatus(302);

        // ログインしている
        $this->assertTrue( Auth::guard('members')->check() );

        // メールリンクのURLを生成
        $member = Member::where( 'email', $requestParams['email'] )->first();
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [ 'id' => $member->id, 'hash' => sha1( $member->getEmailForVerification() ) ]
        );

        // メールリンクをクリック
        $response = $this->get( $verificationUrl );
        $response->assertRedirect('/attendance');
        $response->assertStatus(302);

        // ユーザがメール認証できたか
        $this->assertTrue( Auth::user()->hasVerifiedEmail() );

        // 出勤登録画面へのアクセス
        $response = $this->get('/attendance');
        $response->assertViewIs('.general.clock');
        $response->assertStatus(200);

        // 表示ステータスを確認
        $response->assertSeeText( '出勤中' );

        // ボタンを押す
        $response = $this->post( '/attendance', [ 'break' => '休憩入' ] );
        $response->assertRedirect('/attendance');
        $response->assertStatus(302);
        $response = $this->get('/attendance');
        $response->assertViewIs('.general.clock');
        $response->assertStatus(200);

        // 表示ステータスを確認
        $response->assertSeeText( '休憩中' );

        // 表示ボタンを確認
        $response->assertSee( '休憩戻' );

        // ボタンを押す
        $response = $this->post( '/attendance', [ 'back' => '休憩戻' ] );
        $response->assertRedirect('/attendance');
        $response->assertStatus(302);
        $response = $this->get('/attendance');
        $response->assertViewIs('.general.clock');
        $response->assertStatus(200);

        // 表示ステータスを確認
        $response->assertSeeText( '出勤中' );
    }

    // 休憩戻は何回でも
    public function test_many_back()
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

        // ログインしていない
        $this->assertFalse( Auth::guard('members')->check() );

        // ログイン画面へのアクセス
        $response = $this->get('/login');
        $response->assertViewIs('.general.auth.login');
        $response->assertStatus(200);

        // ログインする(id=1の人)
        $requestParams = [
            'email' => 'member001@example.com',
            'password' => 'pass0001',
            'user_type' => 'general'
        ];
        $response = $this->post( '/login', $requestParams );
        $response->assertRedirect('/attendance');
        $response->assertStatus(302);

        // ログインしている
        $this->assertTrue( Auth::guard('members')->check() );

        // メールリンクのURLを生成
        $member = Member::where( 'email', $requestParams['email'] )->first();
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [ 'id' => $member->id, 'hash' => sha1( $member->getEmailForVerification() ) ]
        );

        // メールリンクをクリック
        $response = $this->get( $verificationUrl );
        $response->assertRedirect('/attendance');
        $response->assertStatus(302);

        // ユーザがメール認証できたか
        $this->assertTrue( Auth::user()->hasVerifiedEmail() );

        // 出勤登録画面へのアクセス
        $response = $this->get('/attendance');
        $response->assertViewIs('.general.clock');
        $response->assertStatus(200);

        // 表示ステータスを確認
        $response->assertSeeText( '出勤中' );

        // 表示ボタンを確認
        $response->assertSee( '休憩入' );

        // ボタンを押す
        $response = $this->post( '/attendance', [ 'break' => '休憩入' ] );
        $response->assertRedirect('/attendance');
        $response->assertStatus(302);
        $response = $this->get('/attendance');
        $response->assertViewIs('.general.clock');
        $response->assertStatus(200);

        // 表示ステータスを確認
        $response->assertSeeText( '休憩中' );

        // 表示ボタンを確認
        $response->assertSee( '休憩戻' );

        // ボタンを押す
        $response = $this->post( '/attendance', [ 'back' => '休憩戻' ] );
        $response->assertRedirect('/attendance');
        $response->assertStatus(302);
        $response = $this->get('/attendance');
        $response->assertViewIs('.general.clock');
        $response->assertStatus(200);

        // 表示ボタンを確認
        $response->assertSee( '休憩入' );

        // ボタンを押す
        $response = $this->post( '/attendance', [ 'break' => '休憩入' ] );
        $response->assertRedirect('/attendance');
        $response->assertStatus(302);
        $response = $this->get('/attendance');
        $response->assertViewIs('.general.clock');
        $response->assertStatus(200);

        // 表示ボタンを確認
        $response->assertSee( '休憩戻' );
    }

    // 一覧画面への表示
    public function test_list()
    {
        // 一般ユーザのデータを作成
        $this->seed( MemberSeeder::class );
        $this->assertDatabaseHas('members', [
            'email' => 'member001@example.com',
        ]);

        // 最新の打刻データを作成
        $clock = [
            'member_id' => 1,
            'clock' => CarbonImmutable::now()->subHours( 2 ),
            'status' => '出勤',
        ];
        Clock::create( $clock );

        // ログインしていない
        $this->assertFalse( Auth::guard('members')->check() );

        // ログイン画面へのアクセス
        $response = $this->get('/login');
        $response->assertViewIs('.general.auth.login');
        $response->assertStatus(200);

        // ログインする(id=1の人)
        $requestParams = [
            'email' => 'member001@example.com',
            'password' => 'pass0001',
            'user_type' => 'general'
        ];
        $response = $this->post( '/login', $requestParams );
        $response->assertRedirect('/attendance');
        $response->assertStatus(302);

        // ログインしている
        $this->assertTrue( Auth::guard('members')->check() );

        // メールリンクのURLを生成
        $member = Member::where( 'email', $requestParams['email'] )->first();
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [ 'id' => $member->id, 'hash' => sha1( $member->getEmailForVerification() ) ]
        );

        // メールリンクをクリック
        $response = $this->get( $verificationUrl );
        $response->assertRedirect('/attendance');
        $response->assertStatus(302);

        // ユーザがメール認証できたか
        $this->assertTrue( Auth::user()->hasVerifiedEmail() );

        // 出勤登録画面へのアクセス
        $response = $this->get('/attendance');
        $response->assertViewIs('.general.clock');
        $response->assertStatus(200);

        // 表示ステータスを確認
        $response->assertSeeText( '出勤中' );

        // 表示ボタンを確認
        $response->assertSee( '休憩入' );

        // 一時間前に休憩入にする
        CarbonImmutable::setTestNow( CarbonImmutable::now()->subHour() );

        // ボタンを押す
        $response = $this->post( '/attendance', [ 'break' => '休憩入' ] );
        $response->assertRedirect('/attendance');
        $response->assertStatus(302);
        $response = $this->get('/attendance');
        $response->assertViewIs('.general.clock');
        $response->assertStatus(200);

        // 表示ステータスを確認
        $response->assertSeeText( '休憩中' );

        // 表示ボタンを確認
        $response->assertSee( '休憩戻' );

        // 現在時刻に休憩戻にする
        CarbonImmutable::setTestNow( null );

        // ボタンを押す
        $response = $this->post( '/attendance', [ 'back' => '休憩戻' ] );
        $response->assertRedirect('/attendance');
        $response->assertStatus(302);

        // 勤怠一覧画面にアクセス
        $response = $this->get('/attendance/list');
        $response->assertViewIs('.general.index');
        $response->assertStatus(200);

        // 休憩時間の確認(一時間休憩している)
        $response->assertSeeText( '01:00' );
    }
}
