<?php

namespace App\Http\Controllers\Pro;

use App\Http\Controllers\Controller;
use App\Models\Acteur;
use App\Models\Lot;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LotController extends Controller
{
    public function index(Request $request): View
    {
        $acteur = $request->user()->acteur;
        $historique = $request->query('vue') === 'historique';

        $lots = Lot::query()
            ->with(['produit:id,nom', 'acteurCourant:id,nom', 'empreinteCarbone:id,lot_id,score,co2_total'])
            ->withCount('etapes')
            ->when(
                $historique,
                fn ($q) => $q->whereHas('etapes', fn ($e) => $e->where('acteur_id', $acteur->id))
                    ->where(fn ($s) => $s->whereNull('acteur_courant_id')->orWhere('acteur_courant_id', '!=', $acteur->id)),
                fn ($q) => $q->where('acteur_courant_id', $acteur->id),
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('pages.pro.lots.index', ['lots' => $lots, 'historique' => $historique]);
    }

    public function create(Request $request): View
    {
        return view('pages.pro.lots.create', [
            'produits' => $request->user()->produits()->orderBy('nom')->pluck('nom', 'id'),
            'numeroSuggere' => $this->prochainNumero(),
            'etapeInitiale' => mb_strtolower($this->libelleEtapes($request->user())),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $request->merge(['numero_lot' => strtoupper(trim((string) $request->input('numero_lot')))]);

        $data = $request->validate([
            'produit_id' => ['required', 'integer', Rule::exists('produits', 'id')->where('proprietaire_id', $user->id)],
            'numero_lot' => ['required', 'string', 'regex:/^LOT-\d{4}-\d{4}$/', 'unique:lots,numero_lot'],
            'quantite' => ['required', 'integer', 'min:1', 'max:1000000'],
            'date_production' => ['required', 'date', 'before_or_equal:today'],
            'date_peremption' => ['required', 'date', 'after:date_production'],
        ], [
            'produit_id.exists' => 'Choisissez un de vos produits.',
            'numero_lot.regex' => 'Le numéro doit respecter le format LOT-AAAA-NNNN (ex. LOT-2026-0015).',
            'numero_lot.unique' => 'Ce numéro de lot existe déjà.',
        ], [
            'produit_id' => 'produit',
            'numero_lot' => 'numéro de lot',
            'quantite' => 'quantité',
            'date_production' => 'date de production',
            'date_peremption' => 'date de péremption',
        ]);

        $lot = Lot::create($data + ['acteur_courant_id' => $user->acteur->id]);
        $user->acteur->produits()->syncWithoutDetaching([$lot->produit_id]);

        return redirect()->route('pro.lots.show', $lot)
            ->with('status', "Le lot {$lot->numero_lot} a été créé. Ajoutez maintenant votre étape de ".mb_strtolower($this->libelleEtapes($user)).'.');
    }

    public function show(Request $request, Lot $lot): View
    {
        $lot->load([
            'produit.categorie',
            'acteurCourant.typeActeur',
            'empreinteCarbone',
            'etapes' => fn ($q) => $q->with(['acteur.typeActeur', 'indicateurs', 'lot:id,acteur_courant_id'])->orderBy('date_heure'),
            'analyses' => fn ($q) => $q->with('laboratoire:id,nom')->latest('date_prelevement'),
        ]);
        $lot->analyses->each->setRelation('lot', $lot);

        $points = $lot->etapes
            ->filter(fn ($etape) => $etape->acteur?->hasCoordinates())
            ->map(fn ($etape) => [
                'lat' => $etape->acteur->latitude,
                'lng' => $etape->acteur->longitude,
                'titre' => $etape->typeLabel().' · '.$etape->acteur->nom,
                'texte' => $etape->lieu,
            ])
            ->values()
            ->all();

        return view('pages.pro.lots.show', [
            'lot' => $lot,
            'points' => $points,
            'destinataires' => $request->user()->can('transferer', $lot) ? $this->destinataires($request->user()) : new Collection,
        ]);
    }

    public function transferer(Request $request, Lot $lot): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'acteur_id' => ['required', 'integer', Rule::in($this->destinataires($user)->modelKeys())],
        ], [
            'acteur_id.in' => 'Choisissez un acteur de l\'étape suivante de la chaîne.',
        ], [
            'acteur_id' => 'destinataire',
        ]);

        if (! $lot->etapes()->where('acteur_id', $user->acteur->id)->exists()) {
            return back()->with('error', 'Enregistrez d\'abord votre étape sur ce lot avant de le transférer.');
        }

        $destinataire = Acteur::find($data['acteur_id']);
        $lot->update(['acteur_courant_id' => $destinataire->id]);

        return redirect()->route('pro.lots.index')
            ->with('status', "Le lot {$lot->numero_lot} a été transféré à {$destinataire->nom}. Vos étapes sont désormais verrouillées.");
    }

    /**
     * Acteurs des maillons suivants de la chaîne, disposant d'un compte professionnel.
     *
     * @return Collection<int, Acteur>
     */
    private function destinataires(User $user): Collection
    {
        return Acteur::query()
            ->with(['typeActeur:id,libelle', 'user:id,role'])
            ->whereHas('user', fn ($q) => $q->whereIn('role', $user->rolesEnAval())->where('active', true))
            ->whereKeyNot($user->acteur?->id)
            ->orderBy('nom')
            ->get();
    }

    private function libelleEtapes(User $user): string
    {
        return collect((array) $user->droitPro('etapes'))
            ->map(fn (string $type) => config("nutritrace.options.etape_types.{$type}"))
            ->join(', ', ' ou ');
    }

    private function prochainNumero(): string
    {
        $annee = now()->year;
        $dernier = Lot::where('numero_lot', 'like', "LOT-{$annee}-%")->orderByDesc('numero_lot')->value('numero_lot');
        $suivant = $dernier ? (int) substr($dernier, -4) + 1 : 1;

        return sprintf('LOT-%d-%04d', $annee, $suivant);
    }
}
