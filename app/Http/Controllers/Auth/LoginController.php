<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Database\Seeders\UserSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * The administrator account is pre-filled on the login form in local development
     * only, so the survey screens are one click away.
     */
    public function create(): View
    {
        return view('auth.login', [
            'devCredentials' => app()->isLocal()
                ? ['login_id' => 'kanri', 'password' => UserSeeder::ADMIN_PASSWORD]
                : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login_id' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ], attributes: [
            'login_id' => 'ログインID',
            'password' => 'パスワード',
        ]);

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'login_id' => 'ログインIDまたはパスワードが正しくありません。',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('responses.index'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
