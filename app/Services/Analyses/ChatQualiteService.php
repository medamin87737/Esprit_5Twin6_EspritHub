<?php

namespace App\Services\Analyses;

use App\Models\Produit;
use App\Services\OpenRouter\OpenRouterClient;
use Illuminate\Support\Collection;
use RuntimeException;
use Throwable;

/**
 * Assistant IA consommateur : questions qualité / conformité uniquement.
 */
class ChatQualiteService
{
    public function __construct(private OpenRouterClient $openRouter) {}

    /**
     * @param  list<array{role: string, content: string}>  $historique
     * @return array{reponse: string, produits: list<array<string, mixed>>}
     */
    public function repondre(string $message, array $historique = [], ?int $produitId = null): array
    {
        $catalogue = $this->catalogueQualite();
        $message = trim($message);

        if ($message === '') {
            throw new RuntimeException('Écrivez une question sur la qualité des produits.');
        }

        try {
            if ($this->openRouter->configure()) {
                return $this->viaOpenRouter($message, $historique, $catalogue, $produitId);
            }
        } catch (Throwable) {
            // repli local ci-dessous
        }

        return $this->reponseLocale($message, $catalogue, $produitId);
    }

    /**
     * @param  list<array{role: string, content: string}>  $historique
     * @param  Collection<int, array<string, mixed>>  $catalogue
     * @return array{reponse: string, produits: list<array<string, mixed>>}
     */
    private function viaOpenRouter(string $message, array $historique, Collection $catalogue, ?int $produitId = null): array
    {
        $contexte = $catalogue->map(function (array $p) {
            return sprintf(
                'id=%d | %s | catégorie=%s | bilan=%s | détails=%s',
                $p['id'],
                $p['nom'],
                $p['categorie'] ?? '—',
                $p['bilan'],
                $p['details']
            );
        })->implode("\n");

        $focus = '';
        if ($produitId) {
            $cible = $catalogue->firstWhere('id', $produitId);
            if ($cible) {
                $focus = "\n\nProduit focal (priorité) : id={$cible['id']} « {$cible['nom']} » — bilan {$cible['bilan']} ({$cible['details']}). "
                    ."Réponds d'abord sur CE produit qualité/conformité, et inclus son id dans produits_ids.";
            }
        }

        $systeme = <<<'PROMPT'
Tu es l'assistant qualité NutriTrace (Tunisie). Tu réponds UNIQUEMENT aux questions sur :
- qualité alimentaire, analyses de laboratoire, conformité / non-conformité
- résultats d'analyses (microbiologie, pesticides, métaux lourds, mycotoxines)
- lots et rapports qualité liés aux produits du catalogue fourni

Refuse poliment tout autre sujet (prix, recettes, politique, code, etc.) en 1 phrase et propose une question qualité.

Style : français clair, professionnel, chaleureux. Tu peux utiliser 1 emoji discret (😊 ✅ 🧪) si cela aide.
Quand tu cites un produit du catalogue, utilise exactement son nom et place son id dans "produits_ids".
Ne invente jamais un produit absent du catalogue.
Réponds en JSON strict :
{"reponse":"texte","produits_ids":[1,2]}
PROMPT;

        $messages = [
            ['role' => 'system', 'content' => $systeme.$focus."\n\nCatalogue qualité :\n".$contexte],
        ];

        foreach (array_slice($historique, -6) as $ligne) {
            $role = ($ligne['role'] ?? '') === 'assistant' ? 'assistant' : 'user';
            $contenu = trim((string) ($ligne['content'] ?? ''));
            if ($contenu !== '') {
                $messages[] = ['role' => $role, 'content' => mb_substr($contenu, 0, 800)];
            }
        }

        $messages[] = ['role' => 'user', 'content' => mb_substr($message, 0, 800)];

        $brut = $this->openRouter->chat($messages, 0.35, null, true);
        $data = $this->openRouter->decoderJson($brut['content']);

        $texte = trim((string) ($data['reponse'] ?? $data['message'] ?? ''));
        if ($texte === '') {
            throw new RuntimeException('Réponse IA vide.');
        }

        $ids = collect($data['produits_ids'] ?? $data['produits'] ?? [])
            ->map(fn ($v) => (int) (is_array($v) ? ($v['id'] ?? 0) : $v))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($produitId && ! in_array($produitId, $ids, true)) {
            array_unshift($ids, $produitId);
        }

        return [
            'reponse' => $texte,
            'produits' => $this->produitsPourIds($ids, $catalogue),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $catalogue
     * @return array{reponse: string, produits: list<array<string, mixed>>}
     */
    private function reponseLocale(string $message, Collection $catalogue, ?int $produitId = null): array
    {
        $q = mb_strtolower($message);

        if ($produitId) {
            $cible = $catalogue->firstWhere('id', $produitId);
            if ($cible) {
                return [
                    'reponse' => "Concernant « {$cible['nom']} » : bilan qualité {$cible['bilan_label']}.\n{$cible['details']}\n\nPosez-moi une question précise (conformité, analyses, lots) 😊",
                    'produits' => [$this->carteProduit($cible)],
                ];
            }
        }

        $horsSujet = ! preg_match(
            '/qualit|conform|non.?conform|analys|labo|pesticid|microbio|m[eé]tal|mycotox|lot|rapport|s[eé]curit[eé]|hygi[eè]ne|contamin/u',
            $q
        );

        if ($horsSujet && ! preg_match('/produit|aliment|yaourt|lait|tomate|farine|couscous|p[aâ]te/u', $q)) {
            return [
                'reponse' => "Je suis spécialisé uniquement dans la qualité et la conformité des produits NutriTrace 😊 Posez-moi par exemple : « Quels produits sont conformes ? » ou « Y a-t-il des analyses non conformes ? »",
                'produits' => [],
            ];
        }

        $filtre = null;
        if (preg_match('/non.?conform/u', $q)) {
            $filtre = 'non_conforme';
        } elseif (preg_match('/conform/u', $q)) {
            $filtre = 'conforme';
        } elseif (preg_match('/attente|en cours/u', $q)) {
            $filtre = 'en_attente';
        }

        $selection = $catalogue;
        if ($filtre) {
            $selection = $catalogue->filter(fn (array $p) => $p['bilan'] === $filtre)->values();
        }

        $mots = collect(preg_split('/\W+/u', $q) ?: [])
            ->filter(fn ($m) => mb_strlen($m) >= 4)
            ->values();

        if ($mots->isNotEmpty()) {
            $trouves = $catalogue->filter(function (array $p) use ($mots) {
                $nom = mb_strtolower($p['nom']);

                return $mots->contains(fn ($m) => str_contains($nom, $m));
            });

            if ($trouves->isNotEmpty() && ! $filtre) {
                $selection = $trouves->values();
            } elseif ($trouves->isNotEmpty() && $filtre) {
                $filtrees = $trouves->filter(fn (array $p) => $p['bilan'] === $filtre)->values();
                if ($filtrees->isNotEmpty()) {
                    $selection = $filtrees;
                }
            }
        }

        $selection = $selection->take(6)->values();

        if ($selection->isEmpty()) {
            return [
                'reponse' => 'Aucun produit ne correspond à ce critère de qualité dans le catalogue actuel. Essayez « produits conformes » ou « analyses en attente ».',
                'produits' => [],
            ];
        }

        $libelle = match ($filtre) {
            'conforme' => 'conformes ✅',
            'non_conforme' => 'non conformes',
            'en_attente' => 'avec analyses en attente',
            default => 'liés à votre question qualité',
        };

        $liste = $selection->map(fn (array $p) => '• '.$p['nom'].' ('.$p['bilan_label'].')')->implode("\n");

        return [
            'reponse' => "Voici les produits {$libelle} que je peux vous indiquer :\n{$liste}\n\nCliquez sur une fiche ci-dessous pour voir le détail 😊",
            'produits' => $selection->map(fn (array $p) => $this->carteProduit($p))->values()->all(),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function catalogueQualite(): Collection
    {
        return Produit::query()
            ->with([
                'categorie:id,nom',
                'lots' => fn ($q) => $q->select('id', 'produit_id', 'numero_lot')
                    ->with(['analyses' => fn ($a) => $a->select('id', 'lot_id', 'type', 'resultat', 'date_prelevement')->latest('date_prelevement')->limit(4)]),
            ])
            ->orderBy('nom')
            ->limit(60)
            ->get(['id', 'nom', 'origine', 'categorie_id'])
            ->map(function (Produit $produit) {
                $analyses = $produit->lots->flatMap->analyses;
                $nbConforme = $analyses->where('resultat', 'conforme')->count();
                $nbNon = $analyses->where('resultat', 'non_conforme')->count();
                $nbAttente = $analyses->where('resultat', 'en_attente')->count();

                $bilan = match (true) {
                    $nbNon > 0 => 'non_conforme',
                    $nbConforme > 0 && $nbAttente === 0 => 'conforme',
                    $nbAttente > 0 => 'en_attente',
                    default => 'sans_analyse',
                };

                $details = sprintf(
                    '%d conforme(s), %d non conforme(s), %d en attente — lots : %s',
                    $nbConforme,
                    $nbNon,
                    $nbAttente,
                    $produit->lots->pluck('numero_lot')->take(4)->implode(', ') ?: 'aucun'
                );

                return [
                    'id' => $produit->id,
                    'nom' => $produit->nom,
                    'categorie' => $produit->categorie?->nom,
                    'origine' => $produit->origine,
                    'bilan' => $bilan,
                    'bilan_label' => match ($bilan) {
                        'conforme' => 'Conforme',
                        'non_conforme' => 'Non conforme',
                        'en_attente' => 'Analyses en attente',
                        default => 'Pas d\'analyse',
                    },
                    'details' => $details,
                    'url' => route('front.produits.show', $produit),
                ];
            })
            ->values();
    }

    /**
     * @param  list<int>  $ids
     * @param  Collection<int, array<string, mixed>>  $catalogue
     * @return list<array<string, mixed>>
     */
    private function produitsPourIds(array $ids, Collection $catalogue): array
    {
        $index = $catalogue->keyBy('id');

        return collect($ids)
            ->map(fn (int $id) => $index->get($id))
            ->filter()
            ->map(fn (array $p) => $this->carteProduit($p))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $p
     * @return array<string, mixed>
     */
    private function carteProduit(array $p): array
    {
        return [
            'id' => $p['id'],
            'nom' => $p['nom'],
            'url' => $p['url'],
            'bilan' => $p['bilan'],
            'bilan_label' => $p['bilan_label'],
            'categorie' => $p['categorie'],
        ];
    }
}
