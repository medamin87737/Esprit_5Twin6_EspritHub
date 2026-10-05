<?php

namespace App\Http\Controllers\Pro;

use App\Http\Controllers\Controller;
use App\Models\Acteur;
use App\Models\TypeActeur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Fiche acteur du professionnel connecté : créée au premier enregistrement.
 */
class ProfilController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('pages.pro.profil', [
            'acteur' => $user->acteur ?? new Acteur(['nom' => $user->name, 'email' => $user->email]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $acteur = $user->acteur;

        $data = $request->validate([
            'nom' => ['required', 'string', 'min:2', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('acteurs', 'email')->ignore($acteur?->id)],
            'telephone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9 ().-]{8,30}$/'],
            'adresse' => ['required', 'string', 'max:255'],
            'pays' => ['required', 'string', 'max:80'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
        ], [
            'telephone.regex' => 'Le téléphone ne doit contenir que des chiffres, espaces, points, tirets ou parenthèses (8 caractères min.).',
        ], [
            'nom' => 'nom de la société',
            'email' => 'e-mail de contact',
            'telephone' => 'téléphone',
            'adresse' => 'adresse',
            'pays' => 'pays',
        ]);

        if ($acteur) {
            $acteur->update($data);
        } else {
            $acteur = new Acteur($data + ['date_inscription' => today()]);
            $acteur->typeActeur()->associate(TypeActeur::firstOrCreate(
                ['libelle' => $user->roleLabel()],
                ['role_chaine' => 'Acteur de la chaîne ('.mb_strtolower($user->roleLabel()).')'],
            ));
            $acteur->user()->associate($user);
            $acteur->save();
        }

        return redirect()->route('pro.profil.edit')->with('status', 'Votre profil société a été enregistré.');
    }
}
