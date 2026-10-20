<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Activation / désactivation des comptes :
 * - l'utilisateur met son compte en pause et peut le réactiver seul ;
 * - l'admin active / désactive tous les comptes (avec verrous).
 */
class ActivationCompteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function consommateur(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'consommateur',
            'active' => true,
            'password' => 'Motdepasse1',
        ], $attrs));
    }

    public function test_utilisateur_met_son_compte_en_pause_avec_mot_de_passe(): void
    {
        $user = $this->consommateur();

        $this->actingAs($user)
            ->post(route('profile.deactivate'), ['deactivate_password' => 'Motdepasse1'])
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $user->refresh();
        $this->assertFalse($user->active);
        $this->assertSame('soi-meme', $user->deactivated_by);
        $this->assertNotNull($user->deactivated_at);
    }

    public function test_pause_refusee_si_mot_de_passe_incorrect(): void
    {
        $user = $this->consommateur();

        $this->actingAs($user)
            ->post(route('profile.deactivate'), ['deactivate_password' => 'mauvais'])
            ->assertSessionHasErrors('deactivate_password', null, 'deactivation');

        $this->assertTrue($user->refresh()->active);
    }

    public function test_reconnexion_sur_compte_en_pause_propose_reactivation(): void
    {
        $user = $this->consommateur();
        $user->desactiver('soi-meme');

        // Connexion avec le bon mot de passe -> page de réactivation
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'Motdepasse1'])
            ->assertRedirect(route('account.deactivated'));
        $this->assertGuest();

        // Réactivation en un clic -> compte actif et connecté
        $this->post(route('account.reactivate'))->assertRedirect();
        $this->assertAuthenticatedAs($user);
        $user->refresh();
        $this->assertTrue($user->active);
        $this->assertNull($user->deactivated_by);
    }

    public function test_compte_suspendu_par_admin_ne_peut_pas_se_reactiver_seul(): void
    {
        $user = $this->consommateur();
        $user->desactiver('admin');

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'Motdepasse1'])
            ->assertSessionHasErrors('email');

        $this->post(route('account.reactivate'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertFalse($user->refresh()->active);
    }

    public function test_admin_desactive_et_reactive_un_utilisateur(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $user = $this->consommateur();

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-active', $user))
            ->assertSessionHas('success');
        $user->refresh();
        $this->assertFalse($user->active);
        $this->assertSame('admin', $user->deactivated_by);

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-active', $user))
            ->assertSessionHas('success');
        $this->assertTrue($user->refresh()->active);
    }

    public function test_admin_ne_peut_pas_se_desactiver_lui_meme(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-active', $admin))
            ->assertSessionHas('error');

        $this->assertTrue($admin->refresh()->active);
    }

    public function test_dernier_admin_actif_est_protege(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $this->assertTrue($admin->isLastActiveAdmin());

        // Même en pause volontaire, le dernier admin actif est bloqué
        $this->actingAs($admin)
            ->post(route('profile.deactivate'), ['deactivate_password' => 'password'])
            ->assertSessionHasErrors('deactivate_password', null, 'deactivation');

        $this->assertTrue($admin->refresh()->active);
    }

    public function test_utilisateur_desactive_est_deconnecte_a_la_requete_suivante(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $user = $this->consommateur();

        $this->actingAs($admin)->patch(route('admin.users.toggle-active', $user));

        // La session déjà ouverte de l'utilisateur est coupée par le middleware
        // (utilisateur rechargé depuis la base, comme en requête réelle)
        $this->actingAs($user->fresh())->get(route('profile.edit'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
