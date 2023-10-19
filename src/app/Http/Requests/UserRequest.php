<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
    public function rules() {
        if ($this->routeIs('login.submit')) {
            return [
                'email' => ['required', 'string', 'email'],
                'password' => ['required', 'string', 'min:8'],
            ];
        }
        if ($this->routeIs('register.submit')) {
            return [
                'name' => ['required', 'string', 'max:100'],
                'email' => ['required', 'string', 'unique:users', 'email'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ];
        }
        return [];
    }

    public function messages() {
        return [
            'email.required' => '※メールアドレスを入力してください',
            'email.string' => '※メールアドレスを文字列で入力してください',
            'email.email' => '※有効なメールアドレス形式を入力してください',
            'password.required' => '※パスワードを入力してください',
            'password.string' => '※パスワードを文字列で入力してください',
            'password.min' => '※パスワードを8文字以上で入力してください',

            'name.required' => '※名前を入力してください',
            'name.string' => '※名前を文字列で入力してください',
            'name.max' => '※名前を100文字以下で入力してください',
            'email.required' => '※メールアドレスを入力してください',
            'email.string' => '※メールアドレスを文字列で入力してください',
            'email.unique' => '※このメールアドレスは既に登録されています',
            'email.email' => '※有効なメールアドレス形式を入力してください',
            'password.required' => '※パスワードを入力してください',
            'password.string' => '※パスワードを文字列で入力してください',
            'password.min' => '※パスワードを8文字以上で入力してください',
            'password.confirmed' => '※パスワードが確認用パスワードと一致しません',
        ];
    }
}
