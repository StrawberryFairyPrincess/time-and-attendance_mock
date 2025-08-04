<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use Database\Seeders\MemberSeeder;
// use Database\Seeders\AdministratorSeeder;
// use Database\Seeders\ClockTableSeeder;
// use Database\Seeders\CorrectionTableSeeder;



class LoginMemberTest extends TestCase
{
    // テスト後にデータベースをリセット
    use RefreshDatabase;

    // メールアドレスのバリデーション
    public function test_fail_email()
    {
        // ログイン画面へのアクセス
        $response = $this->get('/login');
        $response->assertViewIs('.general.auth.login');
        $response->assertStatus(200);

        // 入力項目（メールアドレス未入力）
        $requestParams = [
            'email' => '',
            'password' => 'pass0001',
            'user_type' => 'general'
        ];

        // 入力項目送信
        $response = $this->post( '/login', $requestParams );
        $response->assertRedirect('/login');
        $response->assertStatus(302);

        // メールアドレスのバリデーションがあるか
        $response->assertInvalid([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    // パスワードのバリデーション
    public function test_fail_password()
    {
        // ログイン画面へのアクセス
        $response = $this->get('/login');
        $response->assertViewIs('.general.auth.login');
        $response->assertStatus(200);

        // 入力項目（パスワード未入力）
        $requestParams = [
            'email' => 'member001@example.com',
            'password' => '',
            'user_type' => 'general'
        ];

        // 入力項目送信
        $response = $this->post( '/login', $requestParams );
        $response->assertRedirect('/login');
        $response->assertStatus(302);

        // パスワードのバリデーションがあるか
        $response->assertInvalid([
            'password' => 'パスワードを入力してください',
        ]);
    }

    // 未登録情報入力のバリデーション
    public function test_fail_typo()
    {
        // ログイン画面へのアクセス
        $response = $this->get('/login');
        $response->assertViewIs('.general.auth.login');
        $response->assertStatus(200);

        // 入力項目（未登録情報）
        $requestParams = [
            'email' => 'member001@example.com',
            'password' => 'pass0001',
            'user_type' => 'general'
        ];

        // 入力項目送信
        $response = $this->post( '/login', $requestParams );
        $response->assertRedirect('/login');
        $response->assertStatus(302);

        // 未登録のバリデーションがあるか
        $response->assertInvalid([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }

    // バリデーションを通過したとき
    public function test_success()
    {
        // 一般ユーザのデータを作成
        $this->seed( MemberSeeder::class );
        $this->assertDatabaseHas('members', [
            'email' => 'member001@example.com',
        ]);

        // ログイン画面へのアクセス
        $response = $this->get('/login');
        $response->assertViewIs('.general.auth.login');
        $response->assertStatus(200);

        // ユーザが現時点でログインしていないか
        $this->assertFalse( Auth::guard('members')->check() );

        // 入力項目（id=1の人）
        $requestParams = [
            'email' => 'member001@example.com',
            'password' => 'pass0001',
            'user_type' => 'general'
        ];

        // 入力項目送信
        $response = $this->post( '/login', $requestParams );
        $response->assertRedirect('/attendance');
        $response->assertStatus(302);

        // バリデーションエラーなし
        $response->assertValid( ['email', 'password'] );

        // ユーザがログインできたか
        $this->assertTrue( Auth::guard('members')->check() );
    }
}
