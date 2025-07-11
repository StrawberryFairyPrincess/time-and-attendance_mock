<?php

use Illuminate\Support\Facades\Route;

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

use App\Http\Controllers\AdminController;
use App\Http\Controllers\GeneralController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/





// Route::middleware(['auth', 'verified'])->group(function () {

//     // Route::prefix('/mypage')->group(function () {

//     //     // ヘッダーのリンク表示（マイページ(プロフィール画面)）
//     //     Route::get('', [UserController::class, 'mypage']);

//     //     Route::prefix('/profile')->group(function () {

//     //         // プロフィール編集画面(設定画面)を表示
//     //         Route::get('', [UserController::class, 'profile']);

//     //         // プロフィール編集画面(設定画面)を更新
//     //         Route::post('', [UserController::class, 'update']);

//     //     });

//     // });


//     // Route::prefix('/purchase/{item_id}')->group(function () {

//     //     // 商品購入画面の表示
//     //     Route::get('', [ItemController::class, 'purchase']);

//     //     // 商品の購入
//     //     Route::post('', [UserController::class, 'purchase']);

//     // });


// });



// 管理者
use App\Http\Controllers\Admin;
Route::prefix('/admin')->group(function () {

    // ログインフォームの表示
    Route::get('/login', [Admin\LoginController::class, 'index']);
    // ログインを試みる
    Route::post('/login', [Admin\LoginController::class, 'login']);
    // ログアウトを行ってログインフォームにリダイレクト
    Route::get('/logout', [Admin\LoginController::class, 'logout']);

});
// 未認証の場合にログインフォームにリダイレクト
Route::prefix('/admin')->middleware('auth:administrators')->group(function () {

    // 勤怠一覧画面(管理者)の表示
    Route::get('/attendances',[Admin\IndexController::class, 'index']);

});


// 一般ユーザ
use App\Http\Controllers;
Route::get('/register', [Controllers\LoginController::class, 'register']);
Route::get('login', [Controllers\LoginController::class, 'index']);
Route::post('login', [Controllers\LoginController::class, 'login']);
Route::get('logout', [Controllers\LoginController::class, 'logout']);
// 未認証の場合にログインフォームにリダイレクト
Route::prefix('/')->middleware('auth.general:members')->group(function () {

    // 出勤登録画面の表示
    Route::get('attendance', [Controllers\IndexController::class, 'clock']);

    // 勤怠一覧画面(一般ユーザ)の表示
    Route::get('attendance/list', [Controllers\IndexController::class, 'list']);

});



// メール認証
Route::prefix('/email')->group(function () {

    Route::prefix('/verify')->group(function () {

        // メール確認の通知
        Route::get('', function () {
            return view('general/auth/verify-email');
        })->middleware('auth')->name('verification.notice');

        // メール確認のハンドラ
        Route::get('/{id}/{hash}', function (EmailVerificationRequest $request) {
            $request->fulfill();

            // メールアドレス検証後のリダイレクト先(出勤登録画面へ)
            return redirect('/attendance');
        })->middleware(['auth', 'signed'])->name('verification.verify');

    });

    // メール確認の再送信
    Route::post('/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('message', '認証メールを送信しました');
    })->middleware(['auth', 'throttle:6,1'])->name('verification.send');

});
