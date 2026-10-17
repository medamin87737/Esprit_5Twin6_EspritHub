<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LotRequest;
use App\Models\Etape;
use App\Models\Lot;
use App\Models\Produit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LotController extends Controller
{
    private function queryLots(Request $request): Builder
    {
        return Lot::query()
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($sub) => $sub
                ->where('numero_lot', 'like', '%'.$request->input('q').'%')
                ->orWhereHas('produit', fn ($p) => $p->where('nom', 'like', '%'.$request->input('q').'%'))))
            ->when($request->filled('produit'), fn ($q) => $q->where('produit_id', $request->input('produit')))
            ->when($request->filled('peremption'), fn ($q) => $q->peremption($request->input('peremption')));
    }

    public function index(Request $request): View
    {
        $lots = $this->queryLots($request)
            ->with(['produit', 'empreinteCarbone:id,lot_id,score'])
            ->withCount('etapes')
            ->latest('date_production')
            ->paginate(10)
            ->withQueryString();

        return view('pages.admin.lots.index', [
            'lots' => $lots,
            'produits' => Produit::orderBy('nom')->get(['id', 'nom']),
        ]);
    }

    /**
     * Exporte la liste filtrée des lots avec le détail de leurs étapes au format CSV / Excel.
     */
    public function export(Request $request): StreamedResponse
    {
        $lots = $this->queryLots($request)
            ->with([
                'produit.categorie',
                'acteurCourant.typeActeur',
                'empreinteCarbone',
                'etapes' => fn ($q) => $q->with('acteur.typeActeur')->orderBy('date_heure'),
            ])
            ->latest('date_production')
            ->get();

        $nomFichier = 'lots_nutritrace_'.now()->format('Y-m-d_His').'.csv';

        $entetes = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$nomFichier}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->streamDownload(function () use ($lots) {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8 pour ouverture directe et accents préservés dans Microsoft Excel
            fwrite($handle, "\xEF\xBB\xBF");

            // En-têtes CSV (séparateur point-virgule)
            fputcsv($handle, [
                'Numéro de lot',
                'Produit',
                'Catégorie',
                'Quantité',
                'Date de production',
                'Date de péremption',
                'Statut péremption',
                'Détenteur actuel',
                'Éco-score',
                'CO2 total (kg CO2e)',
                'Nombre d\'étapes',
                'Dernière étape',
                'Étape 1 · Production',
                'Étape 2 · Transformation',
                'Étape 3 · Distribution',
                'Étape 4 · Vente',
                'Parcours complet de traçabilité',
            ], ';');

            $formaterEtape = function (?Etape $etape): string {
                if (! $etape) {
                    return '—';
                }
                $parts = [
                    $etape->acteur?->nom,
                    $etape->lieu,
                    $etape->date_heure?->format('d/m/Y H:i'),
                ];
                if ($etape->mode_transport && $etape->mode_transport !== 'aucun') {
                    $parts[] = 'Transport: '.$etape->transportLabel();
                }
                if ($etape->remarques) {
                    $parts[] = '('.$etape->remarques.')';
                }

                return implode(' · ', array_filter($parts));
            };

            foreach ($lots as $lot) {
                $statutPeremption = match ($lot->alertePeremption()) {
                    'expire' => 'Périmé (depuis '.abs((int) $lot->joursAvantPeremption()).' jour(s))',
                    'j7' => 'Critique J-'.$lot->joursAvantPeremption(),
                    'j30' => 'Attention J-'.$lot->joursAvantPeremption(),
                    'valide' => 'Valide (J+'.$lot->joursAvantPeremption().')',
                    default => 'Non renseignée',
                };

                $derniere = $lot->etapes->last();
                $derniereEtape = $derniere
                    ? $derniere->typeLabel().' ('.($derniere->acteur?->nom ?? '—').' - '.($derniere->date_heure?->format('d/m/Y') ?? '—').')'
                    : 'Aucune';

                $etapeProd = $lot->etapes->firstWhere('type_etape', 'production');
                $etapeTrans = $lot->etapes->firstWhere('type_etape', 'transformation');
                $etapeDist = $lot->etapes->firstWhere('type_etape', 'distribution');
                $etapeVente = $lot->etapes->firstWhere('type_etape', 'vente');

                $parcoursComplet = $lot->etapes->isEmpty()
                    ? 'Aucune étape enregistrée'
                    : $lot->etapes->map(function ($e, $i) use ($formaterEtape) {
                        return ($i + 1).'. '.$e->typeLabel().' ['.$formaterEtape($e).']';
                    })->implode('  ➔  ');

                fputcsv($handle, [
                    $lot->numero_lot,
                    $lot->produit?->nom ?? '—',
                    $lot->produit?->categorie?->nom ?? '—',
                    $lot->quantite,
                    $lot->date_production?->format('d/m/Y') ?? '—',
                    $lot->date_peremption?->format('d/m/Y') ?? '—',
                    $statutPeremption,
                    $lot->acteurCourant?->nom ?? '—',
                    $lot->empreinteCarbone?->score ?? '—',
                    $lot->empreinteCarbone ? number_format($lot->empreinteCarbone->co2_total, 2, ',', ' ') : '—',
                    $lot->etapes->count(),
                    $derniereEtape,
                    $formaterEtape($etapeProd),
                    $formaterEtape($etapeTrans),
                    $formaterEtape($etapeDist),
                    $formaterEtape($etapeVente),
                    $parcoursComplet,
                ], ';');
            }

            fclose($handle);
        }, $nomFichier, $entetes);
    }

    public function create(): View
    {
        return view('pages.admin.lots.create', ['produits' => Produit::orderBy('nom')->get(['id', 'nom'])]);
    }

    public function store(LotRequest $request): RedirectResponse
    {
        $lot = Lot::create($request->validated());

        return redirect()->route('admin.lots.show', $lot)
            ->with('success', "Le lot « {$lot->numero_lot} » a été créé. Ajoutez maintenant ses étapes.");
    }

    public function show(Lot $lot): View
    {
        $lot->load([
            'produit.categorie',
            'etapes' => fn ($q) => $q->with('acteur.typeActeur')->withCount('indicateurs')->orderBy('date_heure'),
            'empreinteCarbone' => fn ($q) => $q->withCount('indicateurs'),
            'analyses' => fn ($q) => $q->with('laboratoire:id,nom')->latest('date_prelevement'),
        ]);

        return view('pages.admin.lots.show', ['lot' => $lot]);
    }

    public function edit(Lot $lot): View
    {
        return view('pages.admin.lots.edit', [
            'lot' => $lot,
            'produits' => Produit::orderBy('nom')->get(['id', 'nom']),
        ]);
    }

    public function update(LotRequest $request, Lot $lot): RedirectResponse
    {
        $lot->update($request->validated());

        return redirect()->route('admin.lots.index')
            ->with('success', "Le lot « {$lot->numero_lot} » a été mis à jour.");
    }

    public function destroy(Lot $lot): RedirectResponse
    {
        $etapes = $lot->etapes()->count();
        $lot->delete();

        return redirect()->route('admin.lots.index')
            ->with('success', "Le lot « {$lot->numero_lot} » et ses {$etapes} étape(s) ont été supprimés.");
    }
}
