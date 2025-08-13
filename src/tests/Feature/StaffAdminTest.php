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


class StaffAdminTest extends TestCase
{
    // テスト後にデータベースをリセット
    use RefreshDatabase;

    // スタッフ一覧画面におけるおける全一般ユーザの表示
    public function test_staff()
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

        // スタッフ一覧画面へアクセス
        $response = $this->get( '/admin/users' );
        $response->assertViewIs('.admin.staff');
        $response->assertStatus(200);

        // 全一般ユーザの名前とメールアドレスの表示
        foreach( Member::all() as $member ){
            $response->assertSeeText( $member['name'] );
            $response->assertSeeText( $member['email'] );
        }
    }

    // スタッフ別勤怠一覧画面
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

        // ログインしていない
        $this->assertFalse( Auth::guard('administrators')->check() );

        // ログインする(id=1の人)
        $administrator = Administrator::where( 'id', 1 )->first();
        $this->actingAs( $administrator, 'administrators' );

        // ログインしている
        $this->assertTrue( Auth::guard('administrators')->check() );

        // スタッフ別勤怠一覧画面へアクセス
        $response = $this->get( '/admin/users/1/attendances' );
        $response->assertViewIs('.admin.individual');
        $response->assertStatus(200);

        // 選択したスタッフの名前が表示されているか
        $response->assertSeeText( Member::where( 'id', 1 )->first()['name'] );

        // ビューを文字列として取得(タグ除去)
        $contents = strip_tags( $response->getContent() );

        // 出勤09:00の出現回数とデータ数が等しいか
        $clockin = substr_count( $contents, '09:00' );
        $this->assertEquals(
            $clockin,
            Clock::where( 'member_id', 1 )->where( 'status', '出勤' )
                ->whereYear( 'clock', CarbonImmutable::now()->year )
                ->whereMonth( 'clock', CarbonImmutable::now()->month )
                ->count()
        );

        // 退勤19:00の出現回数とデータ数が等しいか
        $clockout = substr_count( $contents, '19:00' );
        $this->assertEquals(
            $clockout,
            Clock::where( 'member_id', 1 )->where( 'status', '退勤' )
                ->whereYear( 'clock', CarbonImmutable::now()->year )
                ->whereMonth( 'clock', CarbonImmutable::now()->month )
                ->count()
        );

        // 休憩時間2時間の出現回数とデータ数が等しいか
        $break = substr_count( $contents, '02:00' );
        $this->assertEquals(
            $break,
            Clock::where( 'member_id', 1 )->where( 'status', '出勤' )
                ->whereYear( 'clock', CarbonImmutable::now()->year )
                ->whereMonth( 'clock', CarbonImmutable::now()->month )
                ->count()
        );

        // 勤務時間8時間の出現回数とデータ数が等しいか
        $sum = substr_count( $contents, '08:00' );
        $this->assertEquals(
            $sum,
            Clock::where( 'member_id', 1 )->where( 'status', '出勤' )
                ->whereYear( 'clock', CarbonImmutable::now()->year )
                ->whereMonth( 'clock', CarbonImmutable::now()->month )
                ->count()
        );
    }

    // スタッフ別勤怠一覧画面(前月)
    public function test_last()
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

        // スタッフ別勤怠一覧画面へアクセス
        $response = $this->get( '/admin/users/1/attendances' );
        $response->assertViewIs('.admin.individual');
        $response->assertStatus(200);

        // 前月ボタンを押す
        $response = $this->post( '/admin/users/1/attendances', [ 'sub' => '前月' ] );
        $response->assertRedirect( '/admin/users/1/attendances' );
        $response->assertStatus(302);
        $response = $this->get( '/admin/users/1/attendances' );
        $response->assertViewIs('.admin.individual');
        $response->assertStatus(200);

        // 前の月が表示されているか
        $date = CarbonImmutable::now()->subMonth();
        $response->assertSeeText( $date->isoFormat('YYYY/MM') );
    }

    // スタッフ別勤怠一覧画面(翌月)
    public function test_next()
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

        // スタッフ別勤怠一覧画面へアクセス
        $response = $this->get( '/admin/users/1/attendances' );
        $response->assertViewIs('.admin.individual');
        $response->assertStatus(200);

        // 翌月ボタンを押す
        $response = $this->post( '/admin/users/1/attendances', [ 'add' => '翌月' ] );
        $response->assertRedirect( '/admin/users/1/attendances' );
        $response->assertStatus(302);
        $response = $this->get( '/admin/users/1/attendances' );
        $response->assertViewIs('.admin.individual');
        $response->assertStatus(200);

        // 次の月が表示されているか
        $date = CarbonImmutable::now()->addMonth();
        $response->assertSeeText( $date->isoFormat('YYYY/MM') );
    }

    // 勤怠詳細画面
    public function test_detail()
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

        // 詳細ボタンをクリック(勤怠詳細画面へアクセス)
        $response =
            $this->get( '/admin/attendances/1/' . CarbonImmutable::now()->isoFormat('YYYYMMDD') );
        $response->assertViewIs('.admin.detail');
        $response->assertStatus(200);

        // クリックした日付に遷移するか
        $response->assertSeeText( Member::where( 'id', 1 )->first()['name'] );
        $response->assertSeeText( CarbonImmutable::now()->isoFormat('YYYY年') );
        $response->assertSeeText( CarbonImmutable::now()->isoFormat('MM月DD日') );
    }
}
