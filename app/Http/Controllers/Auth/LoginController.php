<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        // If already logged in
        if (Auth::check()) {
            return redirect('/home')->with('status', 'You are already logged in.');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return back()
                ->withErrors(['email' => 'Login failed! Incorrect credentials.'])
                ->withInput();
        }

        return redirect('/home')->with('status', 'Login successful! Welcome back.');
    }
}
