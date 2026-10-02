<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->search($request->string('q')->trim()->value())
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->input('role')))
            ->when($request->filled('statut'), fn ($q) => $q->where('active', $request->input('statut') === 'actif'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('pages.admin.users.index', [
            'users' => $users,
            'stats' => [
                'total' => User::count(),
                'admins' => User::admins()->count(),
                'actifs' => User::where('active', true)->count(),
                'nouveaux' => User::where('created_at', '>=', now()->subDays(30))->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('pages.admin.users.create');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());

        return redirect()->route('admin.users.index')
            ->with('success', "Le compte de {$user->name} a été créé.");
    }

    public function edit(User $user): View
    {
        return view('pages.admin.users.edit', ['user' => $user]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')
            ->with('success', "Le compte de {$user->name} a été mis à jour.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte depuis cette page.');
        }

        if ($user->isLastActiveAdmin()) {
            return back()->with('error', 'Impossible de supprimer le dernier administrateur actif.');
        }

        $nom = $user->name;
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', "Le compte de {$nom} a été supprimé.");
    }
}
