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

        // Comptes professionnels, rattachés à leur fiche acteur par ActeurSeeder.
        $professionnels = [
            'producteur@nutritrace.tn' => ['Ferme El Baraka', 'producteur'],
            'transformateur@nutritrace.tn' => ['Laiterie du Nord', 'transformateur'],
            'distributeur@nutritrace.tn' => ['Carthage Frais Distribution', 'distributeur'],
        ];

        foreach ($professionnels as $email => [$nom, $role]) {
            User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $nom,
                    'role' => $role,
                    'active' => true,
                    'password' => 'Pro@NutriTrace2026',
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
