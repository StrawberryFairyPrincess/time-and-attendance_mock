<?php

use Illuminate\Support\Facades\Route;

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

use App\Http\Controllers\AdminController;
use App\Http\Controllers\GeneralController;


// 管理者
use App\Http\Controllers\Admin;
Route::prefix('/admin')->group(function () {

    Route::prefix('/login')->group(function () {

        // ログイン画面の表示
        Route::get('', [Admin\LoginController::class, 'index'])->name('admin.login');
        // ログインする
        Route::post('', [Admin\LoginController::class, 'login']);

    });


    // ログアウトしてログイン画面にリダイレクト
    Route::get('/logout', [Admin\LoginController::class, 'logout']);

});
// 未認証の場合にログインフォームにリダイレクト
Route::prefix('/admin')->middleware('auth:administrators')->group(function () {

    // 勤怠一覧画面(管理者)の表示
    Route::get('/attendances',[Admin\DisplayController::class, 'index']);

});

// 一般ユーザ
use App\Http\Controllers;
Route::prefix('/')->group(function () {

    // ユーザ登録画面の表示
    Route::get('register', [Controllers\LoginController::class, 'register']);

    Route::prefix('login')->group(function () {

        // ログイン画面の表示
        Route::get('', [Controllers\LoginController::class, 'index'])->name('login');
        // ログインする
        Route::post('', [Controllers\LoginController::class, 'login']);

    });

    // ログアウトしてログイン画面にリダイレクト
    Route::get('logout', [Controllers\LoginController::class, 'logout']);

});
// 未認証の場合にログインフォームにリダイレクト
// Route::prefix('/')->middleware('auth.general:members')->group(function () {
Route::prefix('/')->middleware(['auth.general:members', 'verified'])->group(function () {

    // 出勤登録画面の表示
    Route::get('attendance', [Controllers\DisplayController::class, 'clock']);

    // 勤怠一覧画面(一般ユーザ)の表示
    Route::get('attendance/list', [Controllers\DisplayController::class, 'list']);

});

// メール認証
Route::prefix('/email')->group(function () {

    Route::prefix('/verify')->group(function () {

        // メール確認の通知
        Route::get('', function () {
            return view('general/auth/verify-email');
        // })->middleware('auth')->name('verification.notice');
        })->middleware('auth.general:members')->name('verification.notice');

        // メール確認のハンドラ
        Route::get('/{id}/{hash}', function (EmailVerificationRequest $request) {
            $request->fulfill();

            // メールアドレス検証後のリダイレクト先(出勤登録画面へ)
            return redirect('/attendance');
        // })->middleware(['auth', 'signed'])->name('verification.verify');
        })->middleware(['auth.general:members', 'signed'])->name('verification.verify');

    });

    // メール確認の再送信
    Route::post('/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('message', '認証メールを送信しました');
    // })->middleware(['auth', 'throttle:6,1'])->name('verification.send');
    })->middleware(['auth.general:members', 'throttle:6,1'])->name('verification.send');

});
