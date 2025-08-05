<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class LoginController extends Controller
{
    // ログイン画面を表示
    public function index()
    {
        return view('/general/auth/login');
    }

    // ログイン処理
    public function login( LoginRequest $request )
    {
        $credentials = $request->only([
            'email',
            'password'
        ]);
        $guard = $request->guard;

        if( Auth::guard('members')->attempt( $credentials ) ){
            // ログインしたら出勤登録画面にリダイレクト
            return redirect('/attendance')->with([
                'login_msg' => 'ログインしました', // ビューの{{ $message }}に展開
            ]);
        }

        return back()->withErrors([
            // 'login' => ['ログインに失敗しました'], // ビューの{{ $error }}に展開
            'password' => 'ログインに失敗しました',
        ]);
    }

    // ログアウト処理
    public function logout( Request $request )
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // ログアウトしたらログインフォームにリダイレクト
        return redirect('/login')->with([
            'auth' => ['ログアウトしました'], // ビューの{{ $message }}に展開
        ]);
    }

    // 会員登録画面の表示
    public function register()
    {
        return view('/general/auth/register');
    }
}
