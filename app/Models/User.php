<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use App\Support\Totp;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

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
        $this->notify(new ResetPasswordNotification($token));
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    public function hasTwoFactorEnabled(): bool
    {
        return filled($this->two_factor_secret) && $this->two_factor_confirmed_at !== null;
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
        return $this->avatar ? asset('storage/'.$this->avatar) : null;
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

    public function signalements(): HasMany
    {
        return $this->hasMany(Signalement::class);
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
