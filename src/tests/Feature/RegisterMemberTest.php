<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;


class RegisterMemberTest extends TestCase
{
    // テスト後にデータベースをリセット
    use RefreshDatabase;

    // 名前のバリデーション
    public function test_fail_name()
    {
        // 会員登録画面へのアクセス
        $response = $this->get('/register');
        $response->assertViewIs('.general.auth.register');
        $response->assertStatus(200);

        // 入力項目（名前未入力）
        $requestParams = [
            'name' => '',
            'email' => 'test@coachtech',
            'password' => '123456789',
            'password_confirmation' => '123456789'
        ];

        // 入力項目送信
        $response = $this->post( '/register', $requestParams );
        $response->assertRedirect('/register');
        $response->assertStatus(302);

        // 名前のバリデーションがあるか
        $response->assertInvalid([
            'name' => 'お名前を入力してください',
        ]);
    }

    // メールアドレスのバリデーション
    public function test_fail_email()
    {
        // 会員登録画面へのアクセス
        $response = $this->get('/register');
        $response->assertViewIs('.general.auth.register');
        $response->assertStatus(200);

        // 入力項目（メールアドレス未入力）
        $requestParams = [
            'name' => 'コーチテック',
            'email' => '',
            'password' => '123456789',
            'password_confirmation' => '123456789'
        ];

        // 入力項目送信
        $response = $this->post( '/register', $requestParams );
        $response->assertRedirect('/register');
        $response->assertStatus(302);

        // メールアドレスのバリデーションがあるか
        $response->assertInvalid([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    // パスワードのバリデーション
    public function test_fail_password()
    {
        // 会員登録画面へのアクセス
        $response = $this->get('/register');
        $response->assertViewIs('.general.auth.register');
        $response->assertStatus(200);

        // 入力項目（パスワード未入力）
        $requestParams = [
            'name' => 'コーチテック',
            'email' => 'test@coachtech',
            'password' => '',
            'password_confirmation' => ''
        ];

        // 入力項目送信
        $response = $this->post( '/register', $requestParams );
        $response->assertRedirect('/register');
        $response->assertStatus(302);

        // パスワードのバリデーションがあるか
        $response->assertInvalid([
            'password' => 'パスワードを入力してください',
        ]);

        // 入力項目（パスワード8文字未満）
        $requestParams = [
            'name' => 'コーチテック',
            'email' => 'test@coachtech',
            'password' => '123',
            'password_confirmation' => '123'
        ];

        // 入力項目送信
        $response = $this->post( '/register', $requestParams );
        $response->assertRedirect('/register');
        $response->assertStatus(302);

        // パスワードのバリデーションがあるか
        $response->assertInvalid([
            'password' => 'パスワードは8文字以上で入力してください',
        ]);

        // 入力項目（パスワード不一致）
        $requestParams = [
            'name' => 'コーチテック',
            'email' => 'test@coachtech',
            'password' => '123456789',
            'password_confirmation' => '987654321'
        ];

        // 入力項目送信
        $response = $this->post( '/register', $requestParams );
        $response->assertRedirect('/register');
        $response->assertStatus(302);

        // パスワードのバリデーションがあるか
        $response->assertInvalid([
            'password' => 'パスワードと一致しません',
        ]);
    }

    // バリデーションを通過したとき
    public function test_success()
    {
        // 会員登録画面へのアクセス
        $response = $this->get('/register');
        $response->assertViewIs('.general.auth.register');
        $response->assertStatus(200);

        // 入力項目
        $requestParams = [
            'name' => 'コーチテック',
            'email' => 'test@coachtech',
            'password' => '123456789',
            'password_confirmation' => '123456789'
        ];

        // 入力項目送信
        $response = $this->post( '/register', $requestParams );
        $response->assertRedirect('/attendance');
        $response->assertStatus(302);
        $response = $this->get('/attendance');
        $response->assertRedirect('/email/verify');
        $response->assertStatus(302);

        // バリデーションエラーなし
        $response->assertValid( ['name', 'email', 'password', 'password_confirmation'] );

        // データベースに登録されているか
        $this->assertDatabaseHas('members', [
            'name' => 'コーチテック',
            'email' => 'test@coachtech',
        ]);
    }
}
