# Module 1 — Produits & Catégories (Ghada)

## Fonctionnalités et URLs

| Fonctionnalité | URL | Méthode | Auth | Description |
|---|---|---|---|---|
| **A1 — Catalogue enrichi** | `/produits` | GET | Public | Recherche (nom, code-barres, origine, description, composition, catégorie), filtres (catégorie, famille, éco-score, labels multiples, origine, certifiés), tri (récents, éco-score, labels, nom), barre de répartition cliquable, pastilles filtres actifs, filtres AJAX sans rechargement |
| **A2 — Comparateur fiches** | `/produits/comparer` | GET | Public | Compare les fiches produits (éco-score, CO₂, labels, lots, % conformes, ingrédients, catégorie, origine), verdict écologique, lien partageable `?produits[]=…` |
| A2 — Ajouter | `/produits/comparaison/ajouter` | POST | Public | JSON si AJAX, redirect sinon. Limite visiteur 2 / connecté 4 |
| A2 — Retirer | `/produits/comparaison/retirer/{id}` | DELETE | Public | JSON si AJAX, redirect sinon |
| A2 — Vider | `/produits/comparaison/vider` | DELETE | Public | JSON si AJAX, redirect sinon |
| **A3 — Fiche produit** | `/produits/{produit}` | GET | Public | KPIs (lots, étapes, CO₂, labels, % conformes), courbe SVG CO₂, certifications avec barre de validité, composition en pastilles, recommandations IA différées |
| A3 — Lot JSON | `/produits/{produit}/lots/{lot}` | GET | Public | Détail d'un lot en JSON (scopeBindings) pour les onglets dynamiques |
| **IA1 — Catégorisation** | `/ia/produits/categoriser` | POST | Auth + Gate create | Propose catégorie, composition, description via OpenRouter. Repli local sans clé |
| **IA2 — OCR étiquette** | `/ia/produits/ocr` | POST | Auth + Gate create | Lit une photo d'étiquette (nom, code-barres, origine, composition). Vérification EAN-13. 503 sans clé |
| **IA3 — Recommandations** | `/produits/{produit}/recommandations` | GET | Public (throttle 60/min) | Alternatives plus écologiques par embeddings. Chargement différé |

## Architecture IA

### Principes

- **Pas de binding ServiceProvider** : chaque service choisit son driver (OpenRouter si clé présente, sinon repli local).
- **Une interface + un driver OpenRouter + un repli local** par fonctionnalité IA.
- **Clé API côté serveur uniquement**, jamais dans le JavaScript.
- **Erreurs IA attrapées** et affichées proprement, jamais d'exception brute.

### Interfaces et drivers

```
app/Services/Module1/
├── Contrats/
│   ├── CategoriseurInterface.php   # IA1
│   ├── OcrInterface.php            # IA2
│   └── EmbeddingInterface.php      # IA3
├── OpenRouterCategoriseur.php      # IA1 — POST /chat/completions (json_object)
├── OpenRouterOcr.php               # IA2 — POST /chat/completions (vision image_url)
├── OpenRouterEmbedding.php         # IA3 — POST /embeddings
├── RepliLocalCategoriseur.php      # IA1 — mots-clés pondérés
└── RepliLocalEmbedding.php         # IA3 — TF + similarité cosinus
```

### Config

`config/ia.php` centralise les paramètres IA :

```php
'openrouter_url' => env('OPENROUTER_URL', 'https://openrouter.ai/api/v1'),
'cle'            => env('OPENROUTER_API_KEY'),
'modeles' => [
    'texte'      => env('OPENROUTER_MODELE_TEXTE',      'google/gemini-2.5-flash'),
    'vision'     => env('OPENROUTER_MODELE_VISION',     'google/gemini-2.5-flash'),
    'embeddings' => env('OPENROUTER_MODELE_EMBEDDINGS', 'openai/text-embedding-3-small'),
],
'timeout' => (int) env('IA_TIMEOUT', 20),
```

## Variables `.env` à ajouter

```
OPENROUTER_API_KEY=               # Clé OpenRouter (vide = repli local)
OPENROUTER_URL=https://openrouter.ai/api/v1
OPENROUTER_MODELE_TEXTE=google/gemini-2.5-flash
OPENROUTER_MODELE_VISION=google/gemini-2.5-flash
OPENROUTER_MODELE_EMBEDDINGS=openai/text-embedding-3-small
IA_TIMEOUT=20
```

Vérifiez les identifiants de modèles sur https://openrouter.ai/models avant de les changer.

## Commandes après un pull

```bash
composer install          # pas de nouveau package, juste par sécurité
npm install               # pas de nouveau package npm
npm run build             # compile module1.js + module1.css dans public/build/
php artisan migrate       # migration IA3 (embedding + embedding_hash sur produits)
php artisan produits:embeddings   # pré-calcule les vecteurs d'embedding
```

Pour recalculer tous les embeddings (changement de modèle ou --force) :

```bash
php artisan produits:embeddings --force
```

## Lancer les tests

```bash
# Tous les tests Module 1 (76 tests, 156 assertions)
php artisan test --filter=Module1

# Un fichier spécifique
php artisan test tests/Feature/Module1/CatalogueTest.php
php artisan test tests/Feature/Module1/ComparateurTest.php
php artisan test tests/Feature/Module1/FicheProduitTest.php
php artisan test tests/Feature/Module1/Ia1CategorisationTest.php
php artisan test tests/Feature/Module1/Ia2OcrTest.php
php artisan test tests/Feature/Module1/Ia3EmbeddingsTest.php
```

Les tests utilisent `RefreshDatabase` + `Http::fake()` : aucun vrai appel API n'est effectué.

## Fichiers JS/CSS

- `resources/assets/front/js/module1.js` — Vanilla JS (aucune dépendance, compatible BS4 et BS5)
- `resources/assets/front/css/module1.css` — Styles préfixés `m1-`, variables CSS avec repli
- Chargés via `@vite('resources/assets/front/js/module1.js')` sur les pages Module 1
- `vite.config.js` : une seule ligne ajoutée dans le tableau `input`

## Lien avec le Module 4 (Sahar)

Le comparateur de Sahar (`/comparer`) compare les **indicateurs environnementaux quantitatifs** (eau, énergie, transport, emballage par lot).
Le comparateur du Module 1 (`/produits/comparer`) compare les **fiches produits** (score global, labels, lots, conformité, ingrédients).

Un lien croisé pointe de `/produits/comparer` vers `/comparer` (un seul sens, aucune modification des fichiers de Sahar).
