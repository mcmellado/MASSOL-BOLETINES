<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function login(Request $request)
{
    $credentials = $request->validate([
        'username' => ['required', 'string'],
        'password' => ['required'],
    ]);

    $remember = $request->boolean('remember');

    // Usamos "name" como usuario
    if (\Illuminate\Support\Facades\Auth::attempt([
        'name' => $credentials['username'],
        'password' => $credentials['password'],
    ], $remember)) {

        $request->session()->regenerate();
        return redirect()->intended(route('clientes.index'));
    }

    return back()
        ->withErrors(['username' => 'Usuario o contraseña incorrectos'])
        ->onlyInput('username');
}


    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
