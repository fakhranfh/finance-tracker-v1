<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AuthPageController extends Controller
{
    public function login(): View
    {
        return view('auth.login');
    }

    public function register(): View
    {
        return view('auth.register');
    }

    public function forgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function resetPassword(string $token): View
    {
        return view('auth.reset-password', ['token' => $token]);
    }
}
