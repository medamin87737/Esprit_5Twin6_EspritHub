<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('nutritrace:admin', function () {
    $data = [
        'name' => $this->ask('Nom complet'),
        'email' => $this->ask('Adresse e-mail'),
        'password' => $this->secret('Mot de passe (8 caractères min., lettres et chiffres)'),
    ];

    $validator = Validator::make($data, [
        'name' => ['required', 'string', 'min:2', 'max:100'],
        'email' => ['required', 'email', 'unique:users,email'],
        'password' => ['required', Password::min(8)->letters()->numbers()],
    ]);

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $message) {
            $this->error($message);
        }

        return 1;
    }

    $user = User::create($data + ['role' => 'admin', 'active' => true]);
    $user->forceFill(['email_verified_at' => now()])->save();

    $this->info("Administrateur créé : {$user->email}");

    return 0;
})->purpose('Créer un compte administrateur NutriTrace');
