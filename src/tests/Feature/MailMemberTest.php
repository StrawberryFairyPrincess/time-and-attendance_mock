<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;
use Mail;
use App\Models\Member;


class MailMemberTest extends TestCase
{
    // テスト後にデータベースをリセット
    use RefreshDatabase;

    public function test_mail()
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
        $response->assertValid(['name', 'email', 'password', 'password_confirmation']);

        // データベースに登録されているか
        $this->assertDatabaseHas('members', [
            'name' => 'コーチテック',
            'email' => 'test@coachtech',
        ]);

        // ユーザがログインしたか
        $this->assertTrue( Auth::check() );

        // メール認証用の確認メールが送られたか
        $this->assertNotNull( Mail::to( $requestParams['email'] ) );

        // メールリンクのURLを生成
        $member = Member::where( 'name', $requestParams['name'] )->first();
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $member->id, 'hash' => sha1($member->getEmailForVerification())]
        );

        // 現時点でメール認証していないことを確認
        $this->assertFalse( Auth::user()->hasVerifiedEmail() );

        // メールリンクをクリック
        $response = $this->get( $verificationUrl );
        $response->assertRedirect('/attendance');
        $response->assertStatus(302);

        // ユーザがメール認証できたか
        $this->assertTrue( Auth::user()->hasVerifiedEmail() );

        // ログインしているのが登録したユーザか
        $this->assertEquals( $member->id, Auth::id() );
    }
}
