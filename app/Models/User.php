<?php

namespace App\Models;

use App\Services\ResendMailer;
use App\Support\EmailTemplate;
use App\Support\Totp;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLES = [
        'admin' => 'Administrateur',
        'producteur' => 'Producteur',
        'transformateur' => 'Transformateur',
        'distributeur' => 'Distributeur',
        'consommateur' => 'Consommateur',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'avatar',
        'role',
        'active',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function sendPasswordResetNotification($token): void
    {
        $url = url(route('password.reset', [
            'token' => $token,
            'email' => $this->getEmailForPasswordReset(),
        ], false));
        $expire = (int) config('auth.passwords.users.expire', 60);
        $name = e($this->name);
        $email = e($this->getEmailForPasswordReset());
        $logo = EmailTemplate::logoUrl();
        $home = rtrim((string) config('app.url'), '/');

        $fallback = <<<HTML
<table width="100%" style="background:#F5F8F6;padding:32px 12px;font-family:Segoe UI,Arial,sans-serif"><tr><td align="center">
<table width="600" style="background:#fff;border-radius:16px;overflow:hidden;border:1px solid #E1E9E3">
<tr><td style="background:#064E35;padding:28px 32px"><img src="{$logo}" width="48" height="48" alt="NutriTrace" style="border-radius:12px;vertical-align:middle"><span style="color:#fff;font-size:20px;font-weight:700;margin-left:12px">NutriTrace</span></td></tr>
<tr><td style="height:4px;background:#FFB627"></td></tr>
<tr><td style="padding:36px 32px"><h1 style="color:#064E35;font-size:24px">Bonjour {$name},</h1>
<p style="color:#3A4A40;font-size:15px;line-height:1.65">Réinitialisez le mot de passe de <strong>{$email}</strong>. Lien valable <strong>{$expire} min</strong>.</p>
<p><a href="{$url}" style="display:inline-block;background:#087443;color:#fff;padding:14px 28px;border-radius:10px;font-weight:700;text-decoration:none">Réinitialiser mon mot de passe</a></p>
<p style="font-size:12px;color:#65756A;word-break:break-all">{$url}</p></td></tr>
<tr><td style="background:#064E35;padding:20px;text-align:center;color:#B7DCC8;font-size:12px">NutriTrace · <a href="{$home}" style="color:#FFB627">{$home}</a></td></tr>
</table></td></tr></table>
HTML;

        $html = EmailTemplate::render('emails.reset-password', [
            'name' => $this->name,
            'email' => $this->getEmailForPasswordReset(),
            'url' => $url,
            'expire' => $expire,
        ], $fallback);

        app(ResendMailer::class)->send(
            $this->getEmailForPasswordReset(),
            'Réinitialisation de votre mot de passe NutriTrace',
            $html,
            EmailTemplate::logoAttachments(),
        );
    }

    public function sendEmailVerificationNotification(): void
    {
        $url = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $this->getKey(),
                'hash' => sha1($this->getEmailForVerification()),
            ],
        );
        $expire = (int) Config::get('auth.verification.expire', 60);
        $name = e($this->name);
        $email = e($this->getEmailForVerification());
        $logo = EmailTemplate::logoUrl();
        $home = rtrim((string) config('app.url'), '/');

        $fallback = <<<HTML
<table width="100%" style="background:#F5F8F6;padding:32px 12px;font-family:Segoe UI,Arial,sans-serif"><tr><td align="center">
<table width="600" style="background:#fff;border-radius:16px;overflow:hidden;border:1px solid #E1E9E3">
<tr><td style="background:#064E35;padding:28px 32px"><img src="{$logo}" width="48" height="48" alt="NutriTrace" style="border-radius:12px;vertical-align:middle"><span style="color:#fff;font-size:20px;font-weight:700;margin-left:12px">NutriTrace</span></td></tr>
<tr><td style="height:4px;background:#FFB627"></td></tr>
<tr><td style="padding:36px 32px"><h1 style="color:#064E35;font-size:24px">Bienvenue {$name} !</h1>
<p style="color:#3A4A40;font-size:15px;line-height:1.65">Confirmez <strong>{$email}</strong> pour activer votre compte. Lien valable <strong>{$expire} min</strong>.</p>
<p><a href="{$url}" style="display:inline-block;background:#087443;color:#fff;padding:14px 28px;border-radius:10px;font-weight:700;text-decoration:none">Vérifier mon e-mail</a></p>
<p style="font-size:12px;color:#65756A;word-break:break-all">{$url}</p></td></tr>
<tr><td style="background:#064E35;padding:20px;text-align:center;color:#B7DCC8;font-size:12px">NutriTrace · <a href="{$home}" style="color:#FFB627">{$home}</a></td></tr>
</table></td></tr></table>
HTML;

        $html = EmailTemplate::render('emails.verify-email', [
            'name' => $this->name,
            'email' => $this->getEmailForVerification(),
            'url' => $url,
            'expire' => $expire,
        ], $fallback);

        app(ResendMailer::class)->send(
            $this->getEmailForVerification(),
            'Vérifiez votre adresse e-mail NutriTrace',
            $html,
            EmailTemplate::logoAttachments(),
        );
    }

    public function hasTwoFactorEnabled(): bool
    {
        // Secret illisible (ex. APP_KEY régénérée après l'enrôlement) = 2FA
        // inutilisable : on ne bloque pas l'utilisateur avec des codes
        // toujours refusés, il pourra réactiver la 2FA depuis ses réglages.
        return filled($this->two_factor_secret)
            && $this->two_factor_confirmed_at !== null
            && $this->twoFactorSecret() !== null;
    }

    /**
     * Supprime une configuration 2FA devenue illisible (secret indéchiffrable).
     * Retourne true si une réparation a été faite.
     */
    public function reparerDeuxFacteursSiIllisible(): bool
    {
        if (filled($this->two_factor_secret) && $this->twoFactorSecret() === null) {
            $this->forceFill([
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
            ])->save();

            return true;
        }

        return false;
    }

    public function twoFactorSecret(): ?string
    {
        if (! $this->two_factor_secret) {
            return null;
        }

        try {
            return decrypt($this->two_factor_secret);
        } catch (\Throwable) {
            return null;
        }
    }

    public function validerCodeDeuxFacteurs(string $code): bool
    {
        $secret = $this->twoFactorSecret();

        if ($secret && Totp::verifier($secret, $code)) {
            return true;
        }

        return $this->consommerCodeRecuperation($code);
    }

    public function consommerCodeRecuperation(string $code): bool
    {
        if (! $this->two_factor_recovery_codes) {
            return false;
        }

        try {
            /** @var list<string> $hashes */
            $hashes = json_decode(decrypt($this->two_factor_recovery_codes), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return false;
        }

        foreach ($hashes as $index => $hash) {
            if (password_verify(strtoupper(trim($code)), $hash) || password_verify(trim($code), $hash)) {
                unset($hashes[$index]);
                $this->forceFill([
                    'two_factor_recovery_codes' => encrypt(json_encode(array_values($hashes))),
                ])->save();

                return true;
            }
        }

        return false;
    }

    public function avatarUrl(): ?string
    {
        if (! $this->avatar) {
            return null;
        }

        return asset('storage/'.$this->avatar).'?v='.($this->updated_at?->timestamp ?? time());
    }

    public function supprimerAvatar(): void
    {
        if ($this->avatar) {
            Storage::disk('public')->delete($this->avatar);
            $this->forceFill(['avatar' => null])->save();
        }
    }

    public function scopeAdmins(Builder $query): void
    {
        $query->where('role', 'admin');
    }

    public function scopeSearch(Builder $query, ?string $term): void
    {
        $query->when($term, fn (Builder $q) => $q->where(fn (Builder $w) => $w
            ->where('name', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%")));
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isConsommateur(): bool
    {
        return $this->role === 'consommateur';
    }

    /**
     * Producteur, transformateur ou distributeur : accès à l'espace /pro.
     */
    public function isPro(): bool
    {
        return array_key_exists((string) $this->role, config('nutritrace.roles_pro'));
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /**
     * Réglage de l'espace pro pour le rôle courant (etapes, gere_produits, cree_lots).
     */
    public function droitPro(string $cle): mixed
    {
        return config("nutritrace.roles_pro.{$this->role}.{$cle}");
    }

    /**
     * Page d'arrivée après connexion ou inscription.
     */
    public function espaceParDefaut(): string
    {
        return match (true) {
            $this->isAdmin() => route('admin.dashboard'),
            $this->isPro() => route('pro.dashboard'),
            default => route('home'),
        };
    }

    public function acteur(): HasOne
    {
        return $this->hasOne(Acteur::class);
    }

    /**
     * Rôles autorisés à posséder des produits (producteur, transformateur).
     *
     * @return list<string>
     */
    public static function rolesGestionnairesProduits(): array
    {
        return array_keys(array_filter(
            config('nutritrace.roles_pro'),
            fn (array $droits) => $droits['gere_produits'],
        ));
    }

    public function scopeGestionnairesProduits(Builder $query): void
    {
        $query->whereIn('role', static::rolesGestionnairesProduits());
    }

    public function produits(): HasMany
    {
        return $this->hasMany(Produit::class, 'proprietaire_id');
    }

    public function scans(): HasMany
    {
        return $this->hasMany(Scan::class);
    }

    /**
     * Rôles situés après le sien dans la chaîne (ordre de roles_pro) :
     * destinataires possibles d'un transfert de lot.
     *
     * @return list<string>
     */
    public function rolesEnAval(): array
    {
        $roles = array_keys(config('nutritrace.roles_pro'));
        $position = array_search($this->role, $roles, true);

        return $position === false ? [] : array_slice($roles, $position + 1);
    }

    public function isLastActiveAdmin(): bool
    {
        return $this->isAdmin()
            && $this->active
            && static::admins()->where('active', true)->whereKeyNot($this->getKey())->doesntExist();
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? ucfirst((string) $this->role);
    }

    public function initials(): string
    {
        return collect(preg_split('/\s+/', trim($this->name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }
}
