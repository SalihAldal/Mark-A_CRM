<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function show(Request $request)
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt(['email' => $data['email'], 'password' => $data['password'], 'status' => 'active'], true)) {
            throw ValidationException::withMessages([
                'email' => __('ui.auth_invalid'),
            ]);
        }

        $request->session()->regenerate();

        /** @var TenantContext $ctx */
        $ctx = app(TenantContext::class);

        return redirect()->to($ctx->isSuperPanel() ? '/super' : '/panel');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to('/login');
    }
}

