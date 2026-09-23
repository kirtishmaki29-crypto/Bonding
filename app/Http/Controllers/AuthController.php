<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    /**
     * Display the login / landing screen with Moon + Deep Ocean + Galaxy theme.
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('chat.index');
        }

        $demoUsers = User::whereIn('email', [
            'explorer@bonding.space',
            'luna@bonding.space',
            'orion@bonding.space',
            'caspian@bonding.space',
        ])->get();

        $googleConfigured = ! empty(config('services.google.client_id'))
            && ! empty(config('services.google.client_secret'));

        return view('auth.login', compact('demoUsers', 'googleConfigured'));
    }

    /**
     * Redirect the user to Google's OAuth authentication page.
     */
    public function redirectToGoogle(): RedirectResponse
    {
        if (empty(config('services.google.client_id')) || empty(config('services.google.client_secret'))) {
            return redirect()->route('login')->with('error', 'Google OAuth credentials are not configured yet in .env. You can sign in using any demo account below for instant testing!');
        }

        return Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from Google after OAuth callback.
     */
    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            /** @var \Laravel\Socialite\Two\User $googleUser */
            $googleUser = Socialite::driver('google')->user();

            $user = User::updateOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'google_id' => $googleUser->getId(),
                    'name' => $googleUser->getName() ?: 'Explorer',
                    'avatar' => $googleUser->getAvatar(),
                    'is_online' => true,
                    'last_active_at' => Carbon::now(),
                ]
            );

            Auth::login($user, true);
            $request->session()->regenerate();

            return redirect()->intended(route('chat.index'));
        } catch (Exception $e) {
            return redirect()->route('login')->with('error', 'Google authentication could not be completed: '.$e->getMessage());
        }
    }

    /**
     * Quick dev/demo login for testing the entire UI/UX instantly.
     */
    public function demoLogin(Request $request, string $email): RedirectResponse
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            return redirect()->route('login')->with('error', 'Selected demo account was not found.');
        }

        $user->update([
            'is_online' => true,
            'last_active_at' => Carbon::now(),
        ]);

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->route('chat.index');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user) {
            $user->update([
                'is_online' => false,
                'last_active_at' => Carbon::now(),
            ]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
