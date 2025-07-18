<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;


class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(Request $request)
    {
        // 管理者ログインからのバリデーションはadministratorsテーブルを確認
        if ( request('user_type') == 'admin' ) {
            return [
                'email' => [
                    'required',
                    'email',
                    // 入力された値がadministratorsテーブルのemailカラムに存在するか
                    Rule::exists('administrators', 'email'),
                ],
                'password' => [
                    'required',
                    'min:8',
                ],
            ];
        }
        // 一般ユーザログインからのバリデーションはmembersテーブルを確認
        else if ( request('user_type') == 'general' ) {
            return [
                'email' => [
                    'required',
                    'email',
                    // 入力された値がmembersテーブルのemailカラムに存在するか
                    Rule::exists('members', 'email'),
                ],
                'password' => [
                    'required',
                    'min:8',
                ],
            ];
        }
    }

    // 表示するエラー文の設定
    public function messages()
    {
        return [
            // バリデーションに引っかかったら$errorsに格納される
            'email.required' => 'メールアドレスを入力してください',
            'email.email' => 'メール形式で入力してください',
            'email.exists' => 'ログイン情報が登録されていません',
            'password.required' => 'パスワードを入力してください',
            'password.min' => 'パスワードは8文字以上で入力してください',
        ];
    }
}
