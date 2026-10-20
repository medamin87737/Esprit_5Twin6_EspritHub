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

        // Profil santé déclaré : réponse déterministe et professionnelle
        // (jamais une simple liste du catalogue sans tri).
        $profil = $this->profilDetecte(mb_strtolower($message));
        if ($profil !== null) {
            return $this->reponseProfil($profil, $catalogue, $produitId);
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

    /** @return array{key: string, label: string}|null */
    private function profilDetecte(string $q): ?array
    {
        return match (true) {
            preg_match('/diab|glyc/u', $q) === 1 => ['key' => 'diabete', 'label' => 'Diabète / sucre'],
            preg_match('/hypertens|tension art/u', $q) === 1 => ['key' => 'hypertension', 'label' => 'Hypertension / sel'],
            preg_match('/cholest/u', $q) === 1 => ['key' => 'cholesterol', 'label' => 'Cholestérol / graisses'],
            preg_match('/allerg|intol[eé]ran|gluten|lactose/u', $q) === 1 => ['key' => 'allergie', 'label' => 'Allergie / intolérance'],
            preg_match('/enceinte|grossesse/u', $q) === 1 => ['key' => 'grossesse', 'label' => 'Grossesse'],
            preg_match('/enfant|b[eé]b[eé]/u', $q) === 1 => ['key' => 'enfant', 'label' => 'Enfant'],
            default => null,
        };
    }

    /** @return array{statut: string, raison: string} */
    private function classifierProduit(array $p, string $profilKey): array
    {
        $t = mb_strtolower($p['nom'].' '.($p['categorie'] ?? '').' '.$p['composition']);
        $a = fn (string $r) => ['statut' => 'adapte', 'raison' => $r];
        $pr = fn (string $r) => ['statut' => 'prudence', 'raison' => $r];
        $d = fn (string $r) => ['statut' => 'deconseille', 'raison' => $r];

        if ($profilKey === 'diabete') {
            if (preg_match('/eau min[eé]rale|eau de source/u', $t)) {
                return $a("aucun sucre ni calorie, c'est la boisson de référence pour la glycémie.");
            }
            if (preg_match('/boga|coca|pepsi|fanta|sprite|orangina|cola|soda|cidre|boisson gazeuse|citronnade|limonade|boisson [eé]nergisante/u', $t)) {
                return $d("boisson sucrée type soda (≈10 g de sucres rapides/100 ml) : pic glycémique rapide, à éviter avec un diabète.");
            }
            if (preg_match('/\bjus\b|nectar|smoothie/u', $t)) {
                return $d('jus/nectar : sucres des fruits sans les fibres, glycémie qui monte vite.');
            }
            if (preg_match('/sucre|miel|sirop|confiture|chocolat|bonbon/u', $t)) {
                return $d('produit sucré : charge glycémique élevée, pas adapté au quotidien.');
            }
            if (preg_match('/datte|raisin sec|fruit sec/u', $t)) {
                return $pr('sucres naturels concentrés : petite quantité (2-3 pièces) au sein d’un repas, pas en grignotage seul.');
            }
            if (preg_match('/couscous|semoule|p[aâ]tes|riz blanc|pain blanc/u', $t)) {
                return $pr('féculent raffiné à index glycémique élevé : portion mesurée, toujours avec légumes et protéines.');
            }

            return $a('aucun sucre ni boisson sucrée détecté dans sa composition.');
        }

        if ($profilKey === 'hypertension') {
            if (preg_match('/saumure|charcuterie|saucisson|jambon fum|chips/u', $t)) {
                return $d('très salé (saumure/charcuterie) : le sodium fait monter la tension.');
            }
            if (preg_match('/\bsel\b|sodium|c[aâ]pre|olive.*vinaigre|fromage|conserve/u', $t)) {
                return $pr('apport en sel à surveiller : petite portion, sans rajouter de sel au repas.');
            }
            if (preg_match('/eau min[eé]rale/u', $t)) {
                return $a('aide à équilibrer les apports en sodium de la journée.');
            }

            return $a('pas de facteur salé marqué détecté.');
        }

        if ($profilKey === 'cholesterol') {
            if (preg_match('/beurre|charcuterie|huile de palme|frit/u', $t)) {
                return $d('graisses saturées importantes : impact direct sur le mauvais cholestérol (LDL).');
            }
            if (preg_match('/fromage|cr[eè]me|viande rouge/u', $t)) {
                return $pr('graisses saturées présentes : fréquence et portion à limiter.');
            }
            if (preg_match('/huile d.olive/u', $t)) {
                return $a('graisses insaturées favorables au profil lipidique.');
            }

            return $a('aucun facteur gras majeur détecté.');
        }

        if ($profilKey === 'enfant') {
            if (preg_match('/boga|coca|soda|citronnade|limonade|bonbon/u', $t)) {
                return $d('boisson/produit sucré : caries et goût du sucre, pas au quotidien chez l’enfant.');
            }
            if (preg_match('/sucre|miel|chocolat/u', $t)) {
                return $pr('sucré : au goûter uniquement, en petite portion.');
            }

            return $a('compatible avec une alimentation enfant équilibrée, en portion adaptée à l’âge.');
        }

        if ($profilKey === 'grossesse') {
            if (preg_match('/alcool|lait cru|non pasteuris/u', $t)) {
                return $d('à éviter pendant la grossesse (alcool ou produit non pasteurisé).');
            }

            return $a('aucun facteur de risque grossesse détecté ; privilégiez les produits pasteurisés et bien cuits.');
        }

        // Allergie : on signale les allergènes majeurs présents.
        $allergenes = [];
        foreach (['lait/lactose' => '/lait|lactose|fromage|yaourt/u', 'gluten' => '/bl[eé]|farine|p[aâ]tes|pain/u', 'arachide' => '/arachide/u', 'fruits à coque' => '/noix|noisette|amande/u', 'soja' => '/soja/u', 'œuf' => '/oeuf|œuf/u'] as $label => $regex) {
            if (preg_match($regex, $t)) {
                $allergenes[] = $label;
            }
        }

        return $allergenes !== []
            ? $pr('allergène(s) présent(s) : '.implode(', ', $allergenes).' — à éviter si c’est votre allergie.')
            : $a('aucun allergène majeur détecté dans la composition déclarée.');
    }

    /**
     * @param  array{key: string, label: string}  $profil
     * @param  Collection<int, array<string, mixed>>  $catalogue
     * @return array{reponse: string, produits: list<array<string, mixed>>}
     */
    private function reponseProfil(array $profil, Collection $catalogue, ?int $produitId = null): array
    {
        if ($produitId) {
            $cible = $catalogue->firstWhere('id', $produitId);
            if ($cible) {
                $c = $this->classifierProduit($cible, $profil['key']);
                $verdict = match ($c['statut']) {
                    'adapte' => '✅ Plutôt adapté à votre profil',
                    'prudence' => '⚠️ À consommer avec prudence',
                    default => '⛔ Peu recommandé pour votre profil',
                };

                return [
                    'reponse' => "Profil {$profil['label']} — « {$cible['nom']} »\n{$verdict} : {$c['raison']}\n\nBilan qualité du lot : {$cible['bilan_label']} ({$cible['details']}).",
                    'produits' => [$this->carteProduit($cible)],
                ];
            }
        }

        $classes = $catalogue->map(fn (array $p) => $p + $this->classifierProduit($p, $profil['key']));
        $adaptes = $classes->where('statut', 'adapte')->take(5)->values();
        $prudence = $classes->where('statut', 'prudence')->take(3)->values();
        $eviter = $classes->where('statut', 'deconseille')->take(5)->values();

        $texte = "Pour un profil {$profil['label']}, voici mon orientation dans le catalogue NutriTrace :\n";

        if ($adaptes->isNotEmpty()) {
            $texte .= "\n✅ Produits que je vous conseille :\n".$adaptes
                ->map(fn (array $p) => "• {$p['nom']} ({$p['bilan_label']}) — {$p['raison']}")
                ->implode("\n");
        } else {
            $texte .= "\nAucun produit franchement adapté repéré dans le catalogue actuel pour ce profil.";
        }

        if ($prudence->isNotEmpty()) {
            $texte .= "\n\n⚠️ Avec prudence :\n".$prudence
                ->map(fn (array $p) => "• {$p['nom']} — {$p['raison']}")
                ->implode("\n");
        }

        if ($eviter->isNotEmpty()) {
            $texte .= "\n\n⛔ À éviter pour votre profil : ".$eviter->pluck('nom')->implode(', ').'.';
        }

        $texte .= "\n\nCliquez sur une fiche conseillée pour voir ses analyses et télécharger son rapport.";

        return [
            'reponse' => $texte,
            'produits' => $adaptes->map(fn (array $p) => $this->carteProduit($p))->values()->all(),
        ];
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
                'id=%d | %s | catégorie=%s | bilan=%s | composition=%s | détails=%s',
                $p['id'],
                $p['nom'],
                $p['categorie'] ?? '—',
                $p['bilan'],
                $p['composition'] !== '' ? $p['composition'] : 'n/c',
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
Tu es l'assistant qualité & orientation produit NutriTrace (Tunisie). Tu réponds sur :
- qualité alimentaire, analyses labo, conformité / non-conformité
- lots et rapports qualité du catalogue fourni
- orientation produit selon un profil déclaré (ex. diabète / sucre, hypertension, allergies) :
  dis clairement si un produit paraît adapté, à consommer avec prudence, ou peu recommandé
  en te basant sur la composition/catégorie/bilan qualité, avec des détails concrets (facteurs, portions, alternatives)

Refuse poliment : prix, recettes détaillées, politique, code, sujets hors alimentation/qualité.

Style : français professionnel, détaillé, concret et rassurant. Ne dis jamais « je ne sais pas » sans proposer ce que tu sais, et ne mets aucune mention de type « pas un avis médical ».
Quand tu cites un produit du catalogue, utilise exactement son nom et place son id dans "produits_ids".
N'invente jamais un produit absent du catalogue.
Réponds en JSON strict :
{"reponse":"texte","produits_ids":[1,2],"aptitude":"adapte|prudence|deconseille|null"}
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

        $sante = (bool) preg_match('/diab|sucre|glyc[eé]|tension|hypertens|allerg|intol[eé]ran|gluten|lactose|maladie|r[eé]gime|sant[eé]/u', $q);

        $horsSujet = ! preg_match(
            '/qualit|conform|non.?conform|analys|labo|pesticid|microbio|m[eé]tal|mycotox|lot|rapport|s[eé]curit[eé]|hygi[eè]ne|contamin/u',
            $q
        );

        if ($horsSujet && ! $sante && ! preg_match('/produit|aliment|yaourt|lait|tomate|farine|couscous|p[aâ]te|conseil|adapt/u', $q)) {
            return [
                'reponse' => "Je vous oriente sur la qualité NutriTrace et l'adéquation produit selon votre profil (ex. diabète). Exemple : « J'ai du diabète, ce produit est-il adapté ? » ou « Quels produits sont conformes ? »",
                'produits' => [],
            ];
        }

        if ($sante && $produitId) {
            $cible = $catalogue->firstWhere('id', $produitId);
            if ($cible) {
                $bas = mb_strtolower($cible['composition'].' '.$cible['nom']);
                $prudenceSucre = preg_match('/diab|sucre|glyc[eé]/u', $q)
                    && preg_match('/sucre|miel|confitur|chocolat|sirop|dessert/u', $bas);

                $msg = $prudenceSucre
                    ? "Pour un profil lié au sucre / diabète, « {$cible['nom']} » mérite de la prudence : sa composition paraît sucrée, donc préférez une portion réduite, au cours d'un repas riche en fibres, plutôt qu'à jeun. Bilan qualité du lot : {$cible['bilan_label']}."
                    : "Compte tenu de votre profil, « {$cible['nom']} » peut être envisagé sereinement : aucun facteur aggravant majeur repéré dans sa composition. Bilan qualité du lot : {$cible['bilan_label']}. Gardez des portions régulières et variez les produits.";

                return [
                    'reponse' => $msg,
                    'produits' => [$this->carteProduit($cible)],
                ];
            }
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
            ->get(['id', 'nom', 'origine', 'description', 'composition', 'categorie_id'])
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
                    'composition' => mb_substr(trim((string) ($produit->composition ?: $produit->description)), 0, 220),
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
