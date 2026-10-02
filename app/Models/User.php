<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLES = [
        'admin' => 'Administrateur',
        'fournisseur' => 'Fournisseur',
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
            'active' => 'boolean',
            'password' => 'hashed',
        ];
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

    public function isFournisseur(): bool
    {
        return $this->role === 'fournisseur';
    }

    public function scopeFournisseurs(Builder $query): void
    {
        $query->where('role', 'fournisseur');
    }

    public function produits(): HasMany
    {
        return $this->hasMany(Produit::class, 'fournisseur_id');
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
