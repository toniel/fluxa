<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class GoogleRedirectController extends Controller
{
    public function __invoke(Request $request): SymfonyResponse
    {
        // Simpan token undangan agar callback tahu harus ke mana.
        if (is_string($token = $request->query('invitation')) && $token !== '') {
            $request->session()->put('fluxa.invitation_token', $token);
        }

        return Socialite::driver('google')->redirect();
    }
}
