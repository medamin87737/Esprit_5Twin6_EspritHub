<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ComparaisonController extends Controller
{
    /**
     * Critères comparés : libellé, unité affichée et calcul (par unité de produit, sauf le transport par lot).
     */
    private const CRITERES = [
        'co2' => ['libelle' => 'Empreinte carbone', 'unite' => 'kg CO₂e / unité', 'icone' => 'bi-cloud-haze2'],
        'eau' => ['libelle' => 'Eau', 'unite' => 'L / unité', 'icone' => 'bi-droplet'],
        'energie' => ['libelle' => 'Énergie', 'unite' => 'kWh / unité', 'icone' => 'bi-lightning-charge'],
        'transport' => ['libelle' => 'Transport', 'unite' => 'km / lot', 'icone' => 'bi-truck'],
        'emballage' => ['libelle' => 'Emballage', 'unite' => 'kg / unité', 'icone' => 'bi-box2'],
    ];

    public function __invoke(Request $request): View
    {
        $max = Gate::allows('comparer-sans-limite')
            ? (int) config('nutritrace.comparaison.connecte')
            : (int) config('nutritrace.comparaison.visiteur');

        $ids = collect((array) $request->query('produits', []))
            ->filter(fn ($id) => ctype_digit((string) $id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $limiteDepassee = $ids->count() > $max;

        $produits = Produit::query()
            ->with(['categorie', 'lots' => fn ($q) => $q->has('empreinteCarbone')->with('empreinteCarbone.indicateurs')])
            ->avecScore()
            ->whereIn('id', $ids->take($max))
            ->get()
            ->sortBy(fn (Produit $p) => $ids->search($p->id))
            ->values();

        $valeurs = $produits->mapWithKeys(fn (Produit $p) => [$p->id => $this->mesures($p)]);

        return view('pages.front.comparaison', [
            'catalogue' => Produit::with('categorie:id,nom')->orderBy('nom')->get(['id', 'nom', 'categorie_id']),
            'produits' => $produits,
            'valeurs' => $valeurs,
            'maxima' => collect(array_keys(self::CRITERES))->mapWithKeys(fn ($c) => [$c => $valeurs->max($c) ?: 0]),
            'criteres' => self::CRITERES,
            'max' => $max,
            'limiteDepassee' => $limiteDepassee,
            'selection' => $ids->take($max)->all(),
        ]);
    }

    /**
     * Moyenne sur les lots du produit ayant une empreinte calculée.
     *
     * @return array<string, ?float>
     */
    private function mesures(Produit $produit): array
    {
        $lots = $produit->lots;
        $mesures = ['co2' => $produit->co2Moyen()];

        foreach (['eau', 'energie', 'transport', 'emballage'] as $type) {
            $parLot = $lots->map(function ($lot) use ($type) {
                $total = $lot->empreinteCarbone->indicateurs->where('type', $type)->sum('valeur');

                return $type === 'transport' ? $total : $total / max($lot->quantite, 1);
            });

            $mesures[$type] = $lots->isEmpty() ? null : round((float) $parLot->avg(), $type === 'transport' ? 0 : 3);
        }

        return $mesures;
    }

    /**
     * @return Collection<int, string>
     */
    public static function couleurs(): Collection
    {
        return collect(['#087443', '#ffb627', '#3b82c4', '#d6342c']);
    }
}
