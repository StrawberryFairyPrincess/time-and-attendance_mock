<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class LoginController extends Controller
{
    // ログインフォームの表示
    public function index()
    {
        return view('/admin/auth/login');
    }

    // ログインを試みる
    public function login(Request $request)
    {
        $credentials = $request->only([
            'email',
            'password'
        ]);

        if (Auth::guard('administrators')->attempt($credentials)) {
            // ログインしたら勤怠一覧画面にリダイレクト
            return redirect('/admin/attendances')->with([
                'login_msg' => 'ログインしました。',
            ]);
        }

        return back()->withErrors([
            // ビューの{{ $message }}に展開
            'login' => ['ログインに失敗しました'],
        ]);
    }

    // ログアウトを行ってログインフォームにリダイレクト
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // ログアウトしたらログインフォームにリダイレクト
        return redirect('/admin/login')->with([
            'logout_msg' => 'ログアウトしました',
        ]);
    }
}
