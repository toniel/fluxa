<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\CompleteRegistrationAction;
use App\Actions\FindOrCreateGoogleUserAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class GoogleCallbackController extends Controller
{
    public function __invoke(
        Request $request,
        FindOrCreateGoogleUserAction $findOrCreate,
        CompleteRegistrationAction $complete,
    ): RedirectResponse|SymfonyResponse {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return to_route('login')->withErrors(['email' => 'Gagal login dengan Google.']);
        }

        [$user, $wasCreated] = $findOrCreate->handle($googleUser);

        if ($wasCreated) {
            $complete->handle($user);
        }

        Auth::login($user, remember: true);

        if (is_string($token = $request->session()->pull('fluxa.invitation_token')) && $token !== '') {
            return to_route('invitations.show', $token);
        }

        // Respon login yang sama dengan Fortify: paham redirect lintas
        // origin ke subdomain tenant.
        return app(LoginResponseContract::class)->toResponse($request);
    }
}
