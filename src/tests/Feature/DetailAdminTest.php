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
// use Database\Seeders\CorrectionTableSeeder;
use App\Models\Administrator;
use App\Models\Member;
use App\Models\Clock;
// use App\Models\Correction;


class DetailAdminTest extends TestCase
{
    // テスト後にデータベースをリセット
    use RefreshDatabase;

    // 選択したデータの表示
    public function test_display()
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
        $this->assertFalse( Auth::guard('administrators')->check() );

        // ログインする(id=1の人)
        $administrator = Administrator::where( 'id', 1 )->first();
        $this->actingAs( $administrator, 'administrators' );

        // ログインしている
        $this->assertTrue( Auth::guard('administrators')->check() );

        // 勤怠詳細画面へアクセス
        $response =
            $this->get( '/admin/attendances/1/' . CarbonImmutable::now()->isoFormat('YYYYMMDD') );
        $response->assertViewIs('.admin.detail');
        $response->assertStatus(200);

        // 表示したデータの表示
        $response->assertSeeText( Member::where( 'id', 1 )->first()['name'] );
        $response->assertSeeText( CarbonImmutable::now()->isoFormat('YYYY年') );
        $response->assertSeeText( CarbonImmutable::now()->isoFormat('MM月DD日') );
        $response->assertSee( '09:00' );
        $response->assertSee( '19:00' );
        $response->assertSee( '13:00' );
        $response->assertSee( '15:00' );
    }

    // バリデーション
    public function test_validation()
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
        $this->assertFalse( Auth::guard('administrators')->check() );

        // ログインする(id=1の人)
        $administrator = Administrator::where( 'id', 1 )->first();
        $this->actingAs( $administrator, 'administrators' );

        // ログインしている
        $this->assertTrue( Auth::guard('administrators')->check() );

        // 勤怠詳細画面へアクセス
        $response =
            $this->get( '/admin/attendances/1/' . CarbonImmutable::now()->isoFormat('YYYYMMDD') );
        $response->assertViewIs('.admin.detail');
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
            '/admin/attendances/1/' . CarbonImmutable::now()->isoFormat('YYYYMMDD'),
            $requestParams
        );
        $response->assertRedirect( '/admin/attendances/1/' . CarbonImmutable::now()->isoFormat('YYYYMMDD') );
        $response->assertStatus(302);

        // バリデーションメッセージの確認
        $response->assertInvalid([
            'clockin' => '出勤時間もしくは退勤時間が不適切な値です',
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
            '/admin/attendances/1/' . CarbonImmutable::now()->isoFormat('YYYYMMDD'),
            $requestParams
        );
        $response->assertRedirect( '/admin/attendances/1/' . CarbonImmutable::now()->isoFormat('YYYYMMDD') );
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
            '/admin/attendances/1/' . CarbonImmutable::now()->isoFormat('YYYYMMDD'),
            $requestParams
        );
        $response->assertRedirect( '/admin/attendances/1/' . CarbonImmutable::now()->isoFormat('YYYYMMDD') );
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
            '/admin/attendances/1/' . CarbonImmutable::now()->isoFormat('YYYYMMDD'),
            $requestParams
        );
        $response->assertRedirect( '/admin/attendances/1/' . CarbonImmutable::now()->isoFormat('YYYYMMDD') );
        $response->assertStatus(302);

        // バリデーションメッセージの確認
        $response->assertInvalid([
            'remarks' => '備考を記入してください',
        ]);
    }
}
