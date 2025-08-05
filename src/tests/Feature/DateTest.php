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

        // 表示時刻の確認
        $date = CarbonImmutable::now();
        $response->assertSeeText( $date->isoFormat('YYYY年MM月DD日(ddd)') );
        $response->assertSeeText( $date->format('H:i') );
    }
}
