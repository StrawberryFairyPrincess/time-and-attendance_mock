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


class ListAdminTest extends TestCase
{
    // テスト後にデータベースをリセット
    use RefreshDatabase;

    // 勤怠情報を全て表示
    public function test_all()
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

        // 勤怠一覧画面に渡すパラメータ作成
        $today = CarbonImmutable::now();
        $clocks = Clock::whereDate( 'clock', $today )
                    ->orderBy( 'clock', 'asc' )->get();
        $table = [];
        foreach( $clocks->where( 'status', '出勤' )->pluck('member_id')->unique() as $member ){
            if( !isset( $table[ $member ] ) ){
                $table[ $member ] = [
                    'name' => Member::where( 'id', $member )->first()['name'],
                    'clockin' => null,
                    'clockout' => null,
                    'break' => 0,
                    'sum' => 0
                ];
            }

            foreach( $clocks->where( 'member_id', $member ) as $clock ){

                // $clockが出勤の打刻のとき
                if( $clock['status'] == '出勤' ){
                    $table[ $member ]['clockin'] = $clock['clock'];
                }
                // $clockが退勤の打刻のとき
                elseif( $clock['status'] == '退勤' ){
                    $table[ $member ]['clockout'] =
                        $clocks->where( 'member_id', $member )
                        ->where( 'status', '退勤' )->last()['clock'];
                    $table[ $member ]['sum'] = $table[ $member ]['clockin']
                        ->diffInSeconds( $table[ $member ]['clockout'] )
                        - $table[ $member ]['break'];
                }
                // $clockが休憩入の打刻のとき
                elseif( $clock['status'] == '休憩入' ){
                    $break = $clock['clock'];
                }
                // $clockが休憩戻の打刻のとき
                elseif( $clock['status'] == '休憩戻' ){
                    $table[ $member ]['break'] += $break->diffInSeconds( $clock['clock'] );
                }

            }
        }

        // 勤怠一覧画面を文字列として取得
        $contents = (string)$this->view( '/admin/index', compact( 'today', 'table' ) );

        // 打刻したユーザ名が全て表示されているか
        foreach( $clocks->where( 'status', '出勤' ) as $clock ){
            $this->assertStringContainsString( $clock->member->name, $contents );
        }

        // 出勤09:00の出現回数とデータ数が等しいか
        $clockin = substr_count( $contents, '09:00' );
        $this->assertEquals(
            $clockin,
            10
        );

        // 退勤19:00の出現回数とデータ数が等しいか
        $clockout = substr_count( $contents, '19:00' );
        $this->assertEquals(
            $clockout,
            10
        );

        // 休憩時間2時間の出現回数とデータ数が等しいか
        $break = substr_count( $contents, '02:00' );
        $this->assertEquals(
            $break,
            10
        );

        // 勤務時間8時間の出現回数とデータ数が等しいか
        $sum = substr_count( $contents, '08:00' );
        $this->assertEquals(
            $sum,
            10
        );
    }

    // 今日
    public function test_today()
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

        // 申請一覧画面(承認待ち)へアクセス
        $response = $this->get( '/admin/attendances' );
        $response->assertViewIs('admin.index');
        $response->assertStatus(200);

        // 今日の日付の表示
        $response->assertSeeText( CarbonImmutable::now()->isoFormat('YYYY/MM/DD') );
    }

    // 前日
    public function test_yesterday()
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

        // 申請一覧画面(承認待ち)へアクセス
        $response = $this->get( '/admin/attendances' );
        $response->assertViewIs('admin.index');
        $response->assertStatus(200);

        // 前日のボタンを押す
        $response = $this->post( '/admin/attendances', [ 'sub' => '前日' ] );
        $response->assertRedirect('/admin/attendances');
        $response->assertStatus(302);
        $response = $this->get('/admin/attendances');
        $response->assertViewIs('admin.index');
        $response->assertStatus(200);

        // 前日の日付の表示
        $response->assertSeeText( CarbonImmutable::now()->subDay()->isoFormat('YYYY/MM/DD') );
    }

    // 翌日
    public function test_tomorrow()
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

        // 申請一覧画面(承認待ち)へアクセス
        $response = $this->get( '/admin/attendances' );
        $response->assertViewIs('admin.index');
        $response->assertStatus(200);

        // 翌日のボタンを押す
        $response = $this->post( '/admin/attendances', [ 'add' => '翌日' ] );
        $response->assertRedirect('/admin/attendances');
        $response->assertStatus(302);
        $response = $this->get('/admin/attendances');
        $response->assertViewIs('admin.index');
        $response->assertStatus(200);

        // 翌日の日付の表示
        $response->assertSeeText( CarbonImmutable::now()->addDay()->isoFormat('YYYY/MM/DD') );
    }
}
