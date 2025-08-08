<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use Carbon\CarbonImmutable;
use Database\Seeders\MemberSeeder;
use Database\Seeders\AdministratorSeeder;
use Database\Seeders\ClockTableSeeder;
use Database\Seeders\CorrectionTableSeeder;
use App\Models\Administrator;
use App\Models\Member;
// use App\Models\Clock;
use App\Models\Correction;


class CorrectMemberTest extends TestCase
{
    // テスト後にデータベースをリセット
    use RefreshDatabase;

    // バリデーション
    public function test_fail_validation()
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

        // 入力項目(出勤時間が退勤時間より後)
        $requestParams = [
            'clockin' => '21:00',
            'clockout' => '19:00',
            'take1' => '13:00',
            'back1' => '15:00',
            'realout' => '19:00',
            'remarks' => 'PHPUnit test',
        ];

        // 入力項目送信
        $response = $this->post(
            '/attendance/detail/' . $date->isoFormat('YYYYMMDD'),
            $requestParams
        );
        $response->assertRedirect( '/attendance/detail/' . $date->isoFormat('YYYYMMDD') );
        $response->assertStatus(302);

        // バリデーションメッセージの確認
        $response->assertInvalid([
            'clockin' => '出勤時間が不適切な値です',
        ]);

        // 入力項目(休憩入時間が退勤時間より後)
        $requestParams = [
            'clockin' => '09:00',
            'clockout' => '19:00',
            'take1' => '21:00',
            'back1' => '15:00',
            'realout' => '19:00',
            'remarks' => 'PHPUnit test',
        ];

        // 入力項目送信
        $response = $this->post(
            '/attendance/detail/' . $date->isoFormat('YYYYMMDD'),
            $requestParams
        );
        $response->assertRedirect( '/attendance/detail/' . $date->isoFormat('YYYYMMDD') );
        $response->assertStatus(302);

        // バリデーションメッセージの確認
        $response->assertInvalid([
            'take1' => '休憩時間が不適切な値です',
        ]);

        // 入力項目(休憩戻時間が退勤時間より後)
        $requestParams = [
            'clockin' => '09:00',
            'clockout' => '19:00',
            'take1' => '13:00',
            'back1' => '21:00',
            'realout' => '19:00',
            'remarks' => 'PHPUnit test',
        ];

        // 入力項目送信
        $response = $this->post(
            '/attendance/detail/' . $date->isoFormat('YYYYMMDD'),
            $requestParams
        );
        $response->assertRedirect( '/attendance/detail/' . $date->isoFormat('YYYYMMDD') );
        $response->assertStatus(302);

        // バリデーションメッセージの確認
        $response->assertInvalid([
            'back1' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);

        // 入力項目(備考欄なし)
        $requestParams = [
            'clockin' => '09:00',
            'clockout' => '19:00',
            'take1' => '13:00',
            'back1' => '15:00',
            'realout' => '19:00',
            'remarks' => '',
        ];

        // 入力項目送信
        $response = $this->post(
            '/attendance/detail/' . $date->isoFormat('YYYYMMDD'),
            $requestParams
        );
        $response->assertRedirect( '/attendance/detail/' . $date->isoFormat('YYYYMMDD') );
        $response->assertStatus(302);

        // バリデーションメッセージの確認
        $response->assertInvalid([
            'remarks' => '備考を記入してください',
        ]);
    }

    // バリデーション通過
    public function test_pass_validation()
    {
        // 管理者のデータを作成
        $this->seed( AdministratorSeeder::class );
        $this->assertDatabaseHas('administrators', [
            'email' => 'admin001@example.com',
        ]);

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

        // 入力項目(休憩戻時間が退勤時間より後)
        $requestParams = [
            'clockin' => '07:00',
            'clockout' => '19:00',
            'take1' => '13:00',
            'back1' => '15:00',
            'realout' => '19:00',
            'remarks' => 'PHPUnit test',
        ];

        // 入力項目送信
        $response = $this->post(
            '/attendance/detail/' . $date->isoFormat('YYYYMMDD'),
            $requestParams
        );
        $response->assertRedirect( '/attendance/detail/' . $date->isoFormat('YYYYMMDD') );
        $response->assertStatus(302);

        // バリデーションエラーなし
        $response->assertValid( ['clockin', 'clockout', 'take1', 'back1', 'remarks'] );

        // ログアウトする
        Auth::logout();

        // ログアウトしている
        $this->assertFalse( Auth::guard('members')->check() );

        // ログインしていない
        $this->assertFalse( Auth::guard('administrators')->check() );

        // ログインする(id=1の人)
        $administrator = Administrator::where( 'id', 1 )->first();
        $this->actingAs( $administrator, 'administrators' );

        // ログインしている
        $this->assertTrue( Auth::guard('administrators')->check() );

        // 申請一覧画面へアクセス
        $response = $this->get( '/admin/requests' );
        $response->assertViewIs('.admin.request');
        $response->assertStatus(200);

        // 一般ユーザの申請内容が表示されているか
        $response->assertSeeText( Member::where( 'id', 1 )->first()->name );
        $response->assertSeeText( $date->isoFormat('YYYY/MM/DD') );

        // 修正申請承認画面へアクセス
        $response = $this->get( '/admin/requests/1/' . $date->isoFormat('YYYYMMDD') );
        $response->assertViewIs('.admin.approve');
        $response->assertStatus(200);

        // 一般ユーザの申請内容が表示されているか
        $response->assertSeeText( Member::where( 'id', 1 )->first()->name );
        $response->assertSeeText( $date->isoFormat('YYYY年') );
        $response->assertSeeText( $date->isoFormat('MM月DD日') );
        $response->assertSeeText( '07:00' );
        $response->assertSeeText( '19:00' );
        $response->assertSeeText( '13:00' );
        $response->assertSeeText( '15:00' );
        $response->assertSeeText( 'PHPUnit test' );
    }

    // 申請一覧画面
    public function test_list()
    {
        // 管理者のデータを作成
        $this->seed( AdministratorSeeder::class );
        $this->assertDatabaseHas('administrators', [
            'email' => 'admin001@example.com',
        ]);

        // 一般ユーザのデータを作成
        $this->seed( MemberSeeder::class );
        $this->assertDatabaseHas('members', [
            'email' => 'member001@example.com',
        ]);

        // 打刻データを作成
        $this->seed( ClockTableSeeder::class );

        // 修正申請データを作成
        $this->seed( CorrectionTableSeeder::class );

        // ログインしていない
        $this->assertFalse( Auth::guard('administrators')->check() );

        // ログインする(id=1の人)
        $administrator = Administrator::where( 'id', 1 )->first();
        $this->actingAs( $administrator, 'administrators' );

        // ログインしている
        $this->assertTrue( Auth::guard('administrators')->check() );

        // 申請一覧画面(承認待ち)へアクセス
        $response = $this->get( '/admin/requests?tab=yet' );
        $response->assertViewIs('.admin.request');
        $response->assertStatus(200);

        // 申請した一般ユーザの名前と対象日時が表示されているか
        $corrections = Correction::all();
        foreach( $corrections as $correction ){
            $response->assertSeeText( $correction->member['name'] );
            $response->assertSeeText( $correction->date->isoFormat('YYYY/MM/DD') );
        }

        // 申請された初めの5件を承認済みにする
        for( $i=0; $i<5; $i++ ){
            // 修正申請承認画面へアクセス(詳細ボタン)
            $response = $this->get( '/admin/requests/' . $corrections[$i]->member['id'] . '/' . $corrections[$i]['date']->isoFormat('YYYYMMDD') );
            $response->assertViewIs('.admin.approve');
            $response->assertStatus(200);

            // 入力項目送信
            $response = $this->post(
                '/admin/requests/' . $corrections[$i]->member['id'] . '/' . $corrections[$i]['date']->isoFormat('YYYYMMDD'),
                [ 'realout' =>
                    $corrections[$i]['date']->setTime( 19, 00 )->format('Y-m-d H:i:s') ]
            );
            $response->assertRedirect( '/admin/requests/' . $corrections[$i]->member['id'] . '/' . $corrections[$i]['date']->isoFormat('YYYYMMDD'), );
            $response->assertStatus(302);
        }

        // 申請一覧画面(承認済)へアクセス
        $response = $this->get( '/admin/requests?tab=done' );
        $response->assertViewIs('.admin.request');
        $response->assertStatus(200);

        // 申請した一般ユーザの名前と対象日時が表示されているか
        $corrections = Correction::where( 'approve', '済' )->get();
        foreach( $corrections as $correction ){
            $response->assertSeeText( $correction->member['name'] );
            $response->assertSeeText( $correction->date->isoFormat('YYYY/MM/DD') );
        }
    }
}
