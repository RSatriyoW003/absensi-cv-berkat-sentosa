<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class LoginController extends Controller
{
    public function showLogin()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withErrors([
                    'email' => 'Email atau password salah.',
                ])
                ->withInput($request->only('email'));
        }

        $token = $user->createToken('web-token')->plainTextToken;

        session([
            'api_token' => $token,
            'user' => $user->only([
                'id',
                'name',
                'email',
                'role',
            ]),
        ]);

        return redirect('/dashboard');
    }

    public function logout()
    {
        session()->forget(['api_token', 'user']);

        return redirect('/login');
    }
}