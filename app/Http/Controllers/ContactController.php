<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public const PROFILS = [
        'consommateur' => 'Consommateur',
        'producteur' => 'Producteur',
        'transformateur' => 'Transformateur',
        'distributeur' => 'Distributeur',
        'organisme' => 'Organisme certificateur',
    ];

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'profil' => ['required', 'in:'.implode(',', array_keys(self::PROFILS))],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ], [], [
            'nom' => 'nom complet',
            'email' => 'adresse e-mail',
            'profil' => 'profil',
            'message' => 'message',
        ]);

        Log::info('Nouveau message de contact NutriTrace', $data);

        return redirect()
            ->to(route('home').'#contact')
            ->with('contact_success', 'Merci ! Votre message a bien été enregistré, notre équipe vous répondra rapidement.');
    }
}
