<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CorrectionRequest extends FormRequest
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
    public function rules()
    {
        $rules = [
            'clockin' => ['required', 'regex:/^(?:[0-3]?\d|4[0-7]):[0-5]\d$/'],
            'clockout' => ['regex:/^(?:[0-3]?\d|4[0-7]):[0-5]\d$/'],
            'remarks' => ['required', 'string', 'max:255'],
        ];

        $inputs = $this->all(); // リクエストデータを取得
        foreach ($inputs as $key => $value) {
            // 'take'から始まるか、'back'から始まるキーのとき
            if( strpos( $key, 'take' ) === 0 || strpos( $key, 'back' ) === 0 ) {
                $rules[ $key ] = ['regex:/^(?:[0-3]?\d|4[0-7]):[0-5]\d$/'];
            }
        }

        return $rules;
    }

    // 表示するエラー文の設定
    public function messages()
    {
        return [
            // バリデーションに引っかかったら$errorsに格納される
            'clockin.required' => '出勤時刻を入力してください',
            'clockin.regex' => '出勤時刻は「時:分」で入力してください',
            'clockout.regex' => '退勤時刻は「時:分」で入力してください',
            'take*.regex' => '休憩入時刻は「時:分」で入力してください',
            'back*.regex' => '休憩戻時刻は「時:分」で入力してください',
            'remarks.required' => '備考を入力してください',
            'remarks.string' => '備考は文字列で入力してください',
            'remarks.max' => '備考は255文字以内で入力してください',
        ];
    }
}
