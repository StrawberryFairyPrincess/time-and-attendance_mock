<?php

use Illuminate\Support\Facades\Route;

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;


// 管理者
use App\Http\Controllers\Admin;
// 認証不要
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
// 認証必要：未認証の場合にログインフォームにリダイレクト
Route::prefix('/admin')->middleware('auth:administrators')->group(function () {

    Route::prefix('/attendances')->group(function () {

        // 勤怠一覧画面(管理者)の表示
        Route::get('', [Admin\DisplayController::class, 'index']);
        // 日めくり
        Route::post('', [Admin\ProcessController::class, 'daily']);

        // 勤怠詳細画面の表示
        Route::get('/{id}/{date}', [Admin\DisplayController::class, 'detail']);
    });

    Route::prefix('/requests')->group(function () {

        // 申請一覧画面の表示
        Route::get('', [Admin\DisplayController::class, 'request']);

        Route::prefix('/{id}/{date}')->group(function () {

            // 修正申請承認画面の表示
            Route::get('', [Admin\DisplayController::class, 'approve']);
            // 修正申請を承認
            Route::post('', [Admin\ProcessController::class, 'approve']);
        });
    });

    Route::prefix('/users')->group(function () {

        // スタッフ一覧画面の表示
        Route::get('', [Admin\DisplayController::class, 'staff']);

        Route::prefix('/{member_id}/attendances')->group(function () {

            // スタッフ別勤怠一覧画面の表示
            Route::get('', [Admin\DisplayController::class, 'individual']);
            // 月めくり
            Route::post('', [Admin\ProcessController::class, 'monthly']);
        });


    });

    // CSV出力
    Route::post('/download', [Admin\ProcessController::class, 'download']);
});

// 一般ユーザ
use App\Http\Controllers;
// 認証不要
Route::prefix('')->group(function () {

    // ユーザ登録画面の表示
    Route::get('/register', [Controllers\LoginController::class, 'register']);

    Route::prefix('/login')->group(function () {

        // ログイン画面の表示
        Route::get('', [Controllers\LoginController::class, 'index'])->name('login');
        // ログインする
        Route::post('', [Controllers\LoginController::class, 'login']);
    });

    // ログアウトしてログイン画面にリダイレクト
    Route::get('/logout', [Controllers\LoginController::class, 'logout']);

});
// 認証必要：未認証の場合にログインフォームにリダイレクト
Route::prefix('')->middleware(['auth.general:members', 'verified'])->group(function () {

    Route::prefix('/attendance')->group(function () {

        // 勤怠登録画面の表示
        Route::get('', [Controllers\DisplayController::class, 'clock']);
        // 勤怠登録
        Route::post('', [Controllers\ProcessController::class, 'clock']);

        Route::prefix('/list')->group(function () {

            // 勤怠一覧画面(一般ユーザ)の表示
            Route::get('', [Controllers\DisplayController::class, 'list']);
            // 月めくり
            Route::post('', [Controllers\ProcessController::class, 'page']);
        });

        Route::prefix('/detail/{date}')->group(function () {

            // 勤怠詳細画面の表示
            Route::get('', [Controllers\DisplayController::class, 'detail']);
            // 修正依頼
            Route::post('', [Controllers\ProcessController::class, 'correct']);
        });
    });

    // 申請一覧画面の表示
    Route::get('/stamp_correction_request/list', [Controllers\DisplayController::class, 'request']);

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
