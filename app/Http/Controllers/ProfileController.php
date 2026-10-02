<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $view = $request->routeIs('admin.*') ? 'pages.admin.profile' : 'pages.front.account';

        return view($view, ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validateWithBag('profile', [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
        ], [], [
            'name' => 'nom complet',
            'email' => 'adresse e-mail',
        ]);

        $user->update($data);

        return back()->with('status', 'Vos informations ont été mises à jour.');
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)->letters()->numbers()],
        ], [
            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
            'password.different' => 'Le nouveau mot de passe doit être différent de l\'actuel.',
        ], [
            'current_password' => 'mot de passe actuel',
            'password' => 'nouveau mot de passe',
        ]);

        $request->user()->update(['password' => $data['password']]);

        return back()->with('status', 'Votre mot de passe a été modifié.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validateWithBag('deletion', [
            'delete_password' => ['required', 'current_password'],
        ], [
            'delete_password.current_password' => 'Le mot de passe est incorrect.',
        ], [
            'delete_password' => 'mot de passe',
        ]);

        if ($user->isLastActiveAdmin()) {
            return back()->withErrors(['delete_password' => 'Vous êtes le dernier administrateur actif : votre compte ne peut pas être supprimé.'], 'deletion');
        }

        Auth::logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
