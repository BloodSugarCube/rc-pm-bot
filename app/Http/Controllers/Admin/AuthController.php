<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $u = (string) config('bot.admin.username', '');
        $p = (string) config('bot.admin.password', '');

        if ($u === '' || $p === '') {
            return back()->withErrors(['username' => 'Учётные данные администратора не заданы в .env (BOT_ADMIN_*).']);
        }

        if (! hash_equals($u, $request->input('username')) || ! hash_equals($p, $request->input('password'))) {
            return back()->withErrors(['username' => 'Неверный логин или пароль.']);
        }

        $request->session()->put('admin_auth', true);

        return redirect()->route('admin.channels');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('admin_auth');

        return redirect()->route('admin.login');
    }
}
