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

        $etaitActif = (bool) $user->active;
        $user->update($data);

        // Le statut modifié via le formulaire garde la même traçabilité
        // que le bouton d'activation / désactivation.
        if ($etaitActif && ! $user->active) {
            $user->forceFill(['deactivated_at' => now(), 'deactivated_by' => 'admin'])->save();
        } elseif (! $etaitActif && $user->active) {
            $user->forceFill(['deactivated_at' => null, 'deactivated_by' => null])->save();
        }

        return redirect()->route('admin.users.index')
            ->with('success', "Le compte de {$user->name} a été mis à jour.");
    }

    /**
     * Active / désactive un compte (bascule).
     * Verrous : impossible de se désactiver soi-même ni de désactiver
     * le dernier administrateur actif. Un compte désactivé est déconnecté
     * dès sa prochaine requête (middleware EnsureAccountIsActive).
     */
    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        if ($user->active) {
            if ($user->is($request->user())) {
                return back()->with('error', 'Vous ne pouvez pas désactiver votre propre compte depuis cette page. Utilisez « Mon profil » pour mettre votre compte en pause.');
            }

            if ($user->isLastActiveAdmin()) {
                return back()->with('error', 'Impossible de désactiver le dernier administrateur actif.');
            }

            $user->desactiver('admin');

            return back()->with('success', "Le compte de {$user->name} a été désactivé. Il sera déconnecté immédiatement et ne pourra plus se connecter.");
        }

        $user->activer();

        return back()->with('success', "Le compte de {$user->name} a été réactivé. Il peut à nouveau se connecter.");
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
