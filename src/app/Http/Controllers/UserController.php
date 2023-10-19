<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{

    // ログイン関連

    public function showLoginForm() {
        return view('auth.login');
    }

    public function login(UserRequest $request) {
        $accepts = $request->only('email', 'password');

        if (Auth::attempt($accepts)) {
            return redirect()->route('stamp');
        }
        else {
            $request->session()->flash('email', $request->input('email'));
            return back()->withErrors(['login' => '※メールアドレスまたはパスワードが誤っています']);
        }
    }

    // 会員登録関連

    public function showRegisterForm() {
        return view('auth.register');
    }

    public function register(UserRequest $request) {
        User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => bcrypt($request->input('password')),
        ]);

        return redirect()->route('stamp');
    }
}