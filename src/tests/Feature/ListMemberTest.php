<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use Carbon\CarbonImmutable;
use Database\Seeders\MemberSeeder;
// use Database\Seeders\AdministratorSeeder;
use Database\Seeders\ClockTableSeeder;
// use Database\Seeders\CorrectionTableSeeder;
use App\Models\Member;
use App\Models\Clock;


class ListMemberTest extends TestCase
{
    // テスト後にデータベースをリセット
    use RefreshDatabase;

    // 勤怠情報を全て表示
    public function test_all()
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

        // 勤怠一覧画面に渡すパラメータ作成
        $today = CarbonImmutable::now();
        $clocks = Clock::where( 'member_id', Auth::id() )
                    ->orderBy( 'clock', 'asc' )->get();
        $table = [];
        foreach( $clocks as $clock ){
            if( !isset( $table[ $clock['clock']->isoFormat('YYYY/MM/DD') ] ) ){
                $table[ $clock['clock']->isoFormat('YYYY/MM/DD') ] = [
                    'clockin' => null,
                    'clockout' => null,
                    'break' => 0,
                    'sum' => 0
                ];
            }

            foreach( $table as $date => &$row ){
                if( $date == $clock['clock']->isoFormat('YYYY/MM/DD') ){
                    if( $clock['status'] == '出勤' ){
                        $row['clockin'] = $clock['clock'];
                    }
                    elseif( $clock['status'] == '退勤' && $row['clockin'] != null ){
                            $row['clockout'] = $clock['clock'];
                            $row['sum'] = $row['clockin']->diffInSeconds( $row['clockout'] )
                                - $row['break'];
                    }
                    elseif( $clock['status'] == '休憩入' ){
                        $break = $clock['clock'];
                    }
                    elseif( $clock['status'] == '休憩戻' ){
                        $row['break'] += $break->diffInSeconds( $clock['clock'] );
                    }
                    break;
                }
            }
        }

        // 勤怠一覧画面を文字列として取得
        $contents = (string)$this->view( '/general/index', compact( 'today', 'table' ) );

        // 出勤09:00の出現回数とデータ数が等しいか
        $clockin = substr_count( $contents, '09:00' );
        $this->assertEquals(
            $clockin,
            Clock::where( 'member_id', Auth::id() )->where( 'status', '出勤' )
                ->whereYear( 'clock', CarbonImmutable::now()->year )
                ->whereMonth( 'clock', CarbonImmutable::now()->month )
                ->count()
        );

        // 退勤19:00の出現回数とデータ数が等しいか
        $clockout = substr_count( $contents, '19:00' );
        $this->assertEquals(
            $clockout,
            Clock::where( 'member_id', Auth::id() )->where( 'status', '退勤' )
                ->whereYear( 'clock', CarbonImmutable::now()->year )
                ->whereMonth( 'clock', CarbonImmutable::now()->month )
                ->count()
        );

        // 休憩時間2時間の出現回数とデータ数が等しいか
        $break = substr_count( $contents, '02:00' );
        $this->assertEquals(
            $break,
            Clock::where( 'member_id', Auth::id() )->where( 'status', '出勤' )
                ->whereYear( 'clock', CarbonImmutable::now()->year )
                ->whereMonth( 'clock', CarbonImmutable::now()->month )
                ->count()
        );

        // 勤務時間8時間の出現回数とデータ数が等しいか
        $sum = substr_count( $contents, '08:00' );
        $this->assertEquals(
            $sum,
            Clock::where( 'member_id', Auth::id() )->where( 'status', '出勤' )
                ->whereYear( 'clock', CarbonImmutable::now()->year )
                ->whereMonth( 'clock', CarbonImmutable::now()->month )
                ->count()
        );
    }

    // 今月の表示
    public function test_this()
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

        // 勤怠一覧画面へのアクセス
        $response = $this->get('/attendance/list');
        $response->assertViewIs('.general.index');
        $response->assertStatus(200);

        // 今の月が表示されているか
        $date = CarbonImmutable::now();
        $response->assertSeeText( $date->isoFormat('YYYY/MM') );
    }

    // 前月の表示
    public function test_last()
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

        // 勤怠一覧画面へのアクセス
        $response = $this->get('/attendance/list');
        $response->assertViewIs('.general.index');
        $response->assertStatus(200);

        // 前月ボタンを押す
        $response = $this->post( '/attendance/list', [ 'sub' => '前月' ] );
        $response->assertRedirect('/attendance/list');
        $response->assertStatus(302);
        $response = $this->get('/attendance/list');
        $response->assertViewIs('.general.index');
        $response->assertStatus(200);

        // 前の月が表示されているか
        $date = CarbonImmutable::now()->subMonth();
        $response->assertSeeText( $date->isoFormat('YYYY/MM') );
    }

    // 翌月の表示
    public function test_next()
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

        // 勤怠一覧画面へのアクセス
        $response = $this->get('/attendance/list');
        $response->assertViewIs('.general.index');
        $response->assertStatus(200);

        // 翌月ボタンを押す
        $response = $this->post( '/attendance/list', [ 'add' => '翌月' ] );
        $response->assertRedirect('/attendance/list');
        $response->assertStatus(302);
        $response = $this->get('/attendance/list');
        $response->assertViewIs('.general.index');
        $response->assertStatus(200);

        // 次の月が表示されているか
        $date = CarbonImmutable::now()->addMonth();
        $response->assertSeeText( $date->isoFormat('YYYY/MM') );
    }

    // 勤怠詳細画面の表示
    public function test_detail()
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

        // 勤怠一覧画面へのアクセス
        $response = $this->get('/attendance/list');
        $response->assertViewIs('.general.index');
        $response->assertStatus(200);

        // 詳細ボタンを押す
        $date = CarbonImmutable::now();
        $response = $this->get( '/attendance/detail/' . $date->isoFormat('YYYYMMDD') );
        $response->assertViewIs('.general.detail');
        $response->assertStatus(200);

        // その日が表示されているか
        $response->assertSeeText( $date->isoFormat('YYYY年') );
        $response->assertSeeText( $date->isoFormat('MM月DD日') );
    }
}
