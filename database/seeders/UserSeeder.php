<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@nutritrace.tn'],
            [
                'name' => 'Amin Administrateur',
                'role' => 'admin',
                'active' => true,
                'password' => 'Admin@NutriTrace2026',
                'email_verified_at' => now(),
            ],
        );

        User::updateOrCreate(
            ['email' => 'utilisateur@nutritrace.tn'],
            [
                'name' => 'Utilisateur NutriTrace',
                'role' => 'consommateur',
                'active' => true,
                'password' => 'User@NutriTrace2026',
                'email_verified_at' => now(),
            ],
        );

        $fournisseurs = [
            'fournisseur@nutritrace.tn' => 'Délices du Sahel',
            'capbon@nutritrace.tn' => 'Coopérative du Cap Bon',
        ];

        foreach ($fournisseurs as $email => $nom) {
            User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $nom,
                    'role' => 'fournisseur',
                    'active' => true,
                    'password' => 'Fournisseur@NutriTrace2026',
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
