<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $users = User::query()
            ->searchByQueryString()
            ->sortByQueryString()
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('admin/Users', [
            'users' => $users->through(
                static fn (User $user): array => [
                    'id' => $user->getKey(),
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_super_admin' => $user->isSuperAdmin(),
                    'tenants_count' => $user->tenants()->count(),
                    'created_at' => $user->created_at->toDateString(),
                ],
            ),
        ]);
    }
}
