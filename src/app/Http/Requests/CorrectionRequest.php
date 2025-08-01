<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\CarbonImmutable;


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
            'clockin' => ['required', 'regex:/(?:[01]\d|2[0-3]):[0-5]\d/'],
            'clockout' => ['regex:/^(?:[0-3]?\d|4[0-7]):[0-5]\d$/'],
            'remarks' => ['required', 'string', 'max:255'],
        ];

        $inputs = $this->all(); // リクエストデータを取得
        $i = 1;
        foreach ($inputs as $key => $value) {
            // 'take'から始まるキーのとき
            if( strpos( $key, 'take' ) === 0 ) {
                $rules[ $key ] = [
                    'regex:/(?:[01]\d|2[0-3]):[0-5]\d/',
                    // 'required_with: back' . $i
                ];
            }
            // 'back'から始まるキーのとき
            elseif( strpos( $key, 'back' ) === 0 ){
                $rules[ $key ] = [
                    'regex:/(?:[01]\d|2[0-3]):[0-5]\d/',
                    // 'required_with: take' . $i++
                ];
            }
        }

        return $rules;
    }

    /**
     * バリデーションルールにカスタムルールを追加
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     */
    public function withValidator( $validator )
    {
        $validator->after( function( $validator ){

            // clockinの時間を取得
            $clockin = CarbonImmutable::parse( $this->input('clockin') );

            // clockoutの時間を取得
            if( explode( ':', $this->input('clockout') )[0] < 24 ){
                $clockout = CarbonImmutable::parse( $this->input('clockout') );
            }
            else{
                $h = explode( ':', $this->input('clockout') )[0] - 24;
                $m = explode( ':', $this->input('clockout') )[1];

                $clockout = CarbonImmutable::parse( $h . ':' . $m )->addDay();
            }

            // take*、back*の時間を取得
            $inputs = $this->all();
            $take = [];
            $back = [];
            $i = 1;
            $j = 1;
            foreach( $inputs as $key => $value ){
                // 'take'から始まるキーのとき
                if( strpos( $key, 'take' ) === 0 && $value != '00:00' ){
                    $take[ $i++ ] = CarbonImmutable::parse( $value );
                }
                // 'back'から始まるキーのとき
                elseif( strpos( $key, 'back' ) === 0 && $value != '00:00' ) {
                    $back[ $j++ ] = CarbonImmutable::parse( $value );
                }
            }

            // clockinがclockoutより未来だとエラー
            if( $clockin != '00:00' && $clockout != '00:00' &&
                $clockin->greaterThan( $clockout ) ){
                $validator->errors()->add( 'clockin', '出勤時間が不適切な値です' );
            }

            foreach( $take as $index => $t ){
                // take*がback*より未来だとエラー
                if( isset( $back[ $index ] ) && $t->greaterThan( $back[ $index ] ) ){
                    $validator->errors()->add( 'take'. $index , '休憩入時間は休憩戻時間より前に設定してください' );
                }

                // take*が前のback*より過去だとエラー
                if( isset( $back[ $index-1 ] ) && $t->lessThan( $back[ $index-1 ] ) ){
                    $validator->errors()->add( 'take'. $index , '休憩入時間は前の休憩戻時間より後に設定してください' );
                }

                // take*がclockoutより未来だとエラー
                if( $clockout != '00:00' && $t->greaterThan( $clockout ) ){
                    $validator->errors()->add( 'take'. $index, '休憩時間が不適切な値です' );
                }
            }

            foreach( $back as $index => $b ){
                // back*がclockoutより未来だとエラー
                if( $clockout != '00:00' && $b->greaterThan( $clockout ) ){
                    $validator->errors()->add( 'back'. $index, '休憩時間もしくは退勤時間が不適切な値です' );
                }
            }

            $i--;
            $j--;
            // 追加の休憩はペアで入力しないとエラー
            if( ( isset( $take[$i] ) && !isset( $back[$i] ) ) ||
                ( !isset( $take[$j] ) && isset( $back[$j] ) ) ){
                $validator->errors()->add( 'take'. $i, '休憩時間の追加は休憩入と休憩戻の両方入力してください' );
            }
        });
    }

    /**
     * バリデーションエラー時に表示するメッセージ
     *
     * @return array
     */
    // 表示するエラー文の設定
    public function messages()
    {
        return [
            // バリデーションに引っかかったら$errorsに格納される
            'clockin.required' => '出勤時刻を入力してください',
            'clockin.regex' => '出勤時刻は00:00〜23:59で入力してください',
            'clockout.regex' => '退勤時刻は00:00〜27:59で入力してください',
            'take*.regex' => '休憩入時刻は00:00〜23:59で入力してください',
            'back*.regex' => '休憩戻時刻は00:00〜23:59で入力してください',
            // 'take*.required_with' => '休憩入と休憩戻は両方入力してください',
            // 'back*.required_with' => '休憩入と休憩戻は両方入力してください',
            'remarks.required' => '備考を入力してください',
            'remarks.string' => '備考は文字列で入力してください',
            'remarks.max' => '備考は255文字以内で入力してください',
        ];
    }
}
