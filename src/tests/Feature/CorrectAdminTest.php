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
use App\Models\Clock;
use App\Models\Correction;


class CorrectAdminTest extends TestCase
{
    // テスト後にデータベースをリセット
    use RefreshDatabase;

    // 修正申請一覧画面
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

        // 修正申請一覧画面(承認待ち)へアクセス
        $response = $this->get( '/admin/requests?tab=yet' );
        $response->assertViewIs('.admin.request');
        $response->assertStatus(200);

        // 承認待ちタブに全ての未承認データが表示されているか
        foreach( Correction::where( 'approve', '未' )->get() as $correction ){
            $response->assertSeeText( $correction->member['name'] );
            $response->assertSeeText( $correction['date']->isoFormat('YYYY/MM/DD') );
        }

        // 初めの5件を承認済みにする
        foreach( Correction::where( 'approve', '未' )->get() as $correction ){
            if( $correction['id'] > 5 ){
                break;
            }
            $correction['approve'] = '済';
            $correction->save();
        }

        // 修正申請一覧画面(承認済み)へアクセス
        $response = $this->get( '/admin/requests?tab=done' );
        $response->assertViewIs('.admin.request');
        $response->assertStatus(200);

        // 承認済みタブに全ての承認後データが表示されているか
        foreach( Correction::where( 'approve', '済' )->get() as $correction ){
            $response->assertSeeText( $correction->member['name'] );
            $response->assertSeeText( $correction['date']->isoFormat('YYYY/MM/DD') );
        }
    }

    // 修正申請承認画面
    public function test_approve()
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

        // 修正申請承認画面へアクセス
        $correction = Correction::where( 'approve', '未' )->first();
        $response = $this->get( '/admin/requests/' . $correction['member_id'] . '/' . $correction['date']->isoFormat('YYYYMMDD') );
        $response->assertViewIs('.admin.approve');
        $response->assertStatus(200);

        // 申請内容が表示されているか
        $response->assertSeeText( $correction->member['name'] );
        $response->assertSeeText( $correction['date']->isoFormat('YYYY年') );
        $response->assertSeeText( $correction['date']->isoFormat('MM月DD日') );
        $response->assertSeeText( '08:30' );
        $response->assertSeeText( '20:30' );
        $response->assertSeeText( $correction['remarks'] );

        // 承認ボタンを押す
        $response = $this->post(
            '/admin/requests/' . $correction['member_id'] . '/' . $correction['date']->isoFormat('YYYYMMDD'),
            [ 'realout' =>  $correction['date']->setTimeFromTimeString( 19, 00, 00 )->format('Y-m-d H:i:s') ]
        );
        $response->assertRedirect( '/admin/requests/' . $correction['member_id'] . '/' . $correction['date']->isoFormat('YYYYMMDD') );
        $response->assertStatus(302);
        $response = $this->get( '/admin/requests/' . $correction['member_id'] . '/' . $correction['date']->isoFormat('YYYYMMDD') );
        $response->assertViewIs('.admin.approve');
        $response->assertStatus(200);

        // 承認されているか
        $response->assertSeeText( '承認済み' );
        $this->assertEquals( '済', Correction::first()['approve'] );
        $this->assertEquals(
            '08:30',
            Clock::where( 'member_id', $correction['member_id'] )->where( 'status', '出勤' )
            ->whereDate( 'clock', $correction['date'] )->first()['clock']->format('H:i')
        );
        $this->assertEquals(
            '20:30',
            Clock::where( 'member_id', $correction['member_id'] )->where( 'status', '退勤' )
            ->whereDate( 'clock', $correction['date'] )->first()['clock']->format('H:i')
        );
    }
}
