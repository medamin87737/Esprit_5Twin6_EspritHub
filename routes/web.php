<?php

/*
|--------------------------------------------------------------------------
| Routes Web — NutriTrace (fichier unique)
|--------------------------------------------------------------------------
|
| Toutes les routes web de l'application sont déclarées ici, dans l'ordre :
| accueil, authentification, compte, administration, acteurs, modules 1 à 5
| (produits, analyses, lots, empreintes, certifications), espace
| consommateur et espace professionnel.
|
*/

use App\Http\Controllers\Admin\ActeurController;
use App\Http\Controllers\Admin\AnalyseController as AdminAnalyseController;
use App\Http\Controllers\Admin\AnalyseExportController;
use App\Http\Controllers\Admin\AnalyseIaController as AdminAnalyseIaController;
use App\Http\Controllers\Admin\CategorieController;
use App\Http\Controllers\Admin\CertificationController as AdminCertificationController;
use App\Http\Controllers\Admin\CertificationExportController;
use App\Http\Controllers\Admin\CertificationIaController;
use App\Http\Controllers\Admin\CertificationTraductionController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EmpreinteCarboneController;
use App\Http\Controllers\Admin\EmpreinteIaController;
use App\Http\Controllers\Admin\EtapeController as AdminEtapeController;
use App\Http\Controllers\Admin\FacteurClimatiqController;
use App\Http\Controllers\Admin\IndicateurController as AdminIndicateurController;
use App\Http\Controllers\Admin\LaboratoireController;
use App\Http\Controllers\Admin\LaboratoireExportController;
use App\Http\Controllers\Admin\LotController as AdminLotController;
use App\Http\Controllers\Admin\OrganismeController;
use App\Http\Controllers\Admin\ProduitController as AdminProduitController;
use App\Http\Controllers\Admin\TypeActeurController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Consommateur\ChatQualiteController;
use App\Http\Controllers\Consommateur\ProduitQualiteController;
use App\Http\Controllers\Consommateur\QualiteController as ConsommateurQualiteController;
use App\Http\Controllers\Consommateur\ScanController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Front\AccueilController;
use App\Http\Controllers\Front\AnnuaireController;
use App\Http\Controllers\Front\CatalogueController;
use App\Http\Controllers\Front\ChatCertificationController;
use App\Http\Controllers\Front\ComparaisonController;
use App\Http\Controllers\Front\ComparateurController;
use App\Http\Controllers\Front\EcoScoreController;
use App\Http\Controllers\Front\LabelController;
use App\Http\Controllers\Front\LotController as FrontLotController;
use App\Http\Controllers\Front\ProduitController as FrontProduitController;
use App\Http\Controllers\Front\RapportAnalyseController;
use App\Http\Controllers\Front\TracabiliteController;
use App\Http\Controllers\Module1\IaCategoriserController;
use App\Http\Controllers\Module1\IaOcrController;
use App\Http\Controllers\Module1\IaRecommandationsController;
use App\Http\Controllers\Pro\AnalyseController as ProAnalyseController;
use App\Http\Controllers\Pro\AnalyseIaController as ProAnalyseIaController;
use App\Http\Controllers\Pro\CertificationController as ProCertificationController;
use App\Http\Controllers\Pro\DashboardController as ProDashboardController;
use App\Http\Controllers\Pro\EtapeController as ProEtapeController;
use App\Http\Controllers\Pro\IndicateurController as ProIndicateurController;
use App\Http\Controllers\Pro\LotController as ProLotController;
use App\Http\Controllers\Pro\ProduitController as ProProduitController;
use App\Http\Controllers\Pro\ProfilController;
use App\Http\Controllers\Pro\QualiteController as ProQualiteController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// ═══════════════════════════════════════════════════════════════════════════
// Accueil & contact
// ═══════════════════════════════════════════════════════════════════════════

Route::get('/', AccueilController::class)->name('home');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

// ═══════════════════════════════════════════════════════════════════════════
// Authentification (connexion, inscription, mot de passe, 2FA, vérification)
// ═══════════════════════════════════════════════════════════════════════════

Route::middleware('guest')->group(function () {
    Route::get('/connexion', [LoginController::class, 'create'])->name('login');
    Route::post('/connexion', [LoginController::class, 'store'])->middleware('throttle:6,1')->name('login.store');

    Route::get('/inscription', [RegisterController::class, 'create'])->name('register');
    Route::post('/inscription', [RegisterController::class, 'store'])->middleware('throttle:6,1')->name('register.store');

    Route::get('/mot-de-passe-oublie', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/mot-de-passe-oublie', [PasswordResetController::class, 'sendLink'])->middleware('throttle:6,1')->name('password.email');
    Route::get('/reinitialiser-mot-de-passe/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/reinitialiser-mot-de-passe', [PasswordResetController::class, 'reset'])->middleware('throttle:6,1')->name('password.update');

    Route::get('/double-authentification', [TwoFactorController::class, 'challenge'])->name('two-factor.challenge');
    Route::post('/double-authentification', [TwoFactorController::class, 'authenticate'])->middleware('throttle:6,1')->name('two-factor.authenticate');
});

Route::post('/deconnexion', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

// Compte en pause volontaire : réactivation après reconnexion réussie
Route::get('/compte-desactive', [LoginController::class, 'deactivated'])->name('account.deactivated');
Route::post('/compte-desactive/reactiver', [LoginController::class, 'reactivate'])->middleware('throttle:6,1')->name('account.reactivate');

Route::middleware('auth')->group(function () {
    Route::get('/email/verifier', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verifier/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('/securite/2fa', [TwoFactorController::class, 'show'])->name('two-factor.show');
    Route::post('/securite/2fa', [TwoFactorController::class, 'enable'])->name('two-factor.enable');
    Route::post('/securite/2fa/confirmer', [TwoFactorController::class, 'confirm'])->name('two-factor.confirm');
    Route::delete('/securite/2fa', [TwoFactorController::class, 'disable'])->name('two-factor.disable');
    Route::post('/securite/2fa/codes', [TwoFactorController::class, 'regenerate'])->name('two-factor.recovery');
});

// ═══════════════════════════════════════════════════════════════════════════
// Compte utilisateur
// ═══════════════════════════════════════════════════════════════════════════

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/mon-compte', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/mon-compte', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/mon-compte/mot-de-passe', [ProfileController::class, 'password'])->name('profile.password');
    Route::delete('/mon-compte/avatar', [ProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');
    Route::post('/mon-compte/desactivation', [ProfileController::class, 'deactivate'])->name('profile.deactivate');
    Route::delete('/mon-compte', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ═══════════════════════════════════════════════════════════════════════════
// Administration — tableau de bord & utilisateurs
// ═══════════════════════════════════════════════════════════════════════════

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile');

    Route::resource('utilisateurs', AdminUserController::class)
        ->parameters(['utilisateurs' => 'user'])
        ->names('users')
        ->except('show');

    Route::patch('utilisateurs/{user}/activation', [AdminUserController::class, 'toggleActive'])->name('users.toggle-active');
});

// ═══════════════════════════════════════════════════════════════════════════
// Acteurs de la chaîne (annuaire public + administration)
// ═══════════════════════════════════════════════════════════════════════════

Route::get('/acteurs', AnnuaireController::class)->name('front.acteurs.index');

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('type-acteurs', TypeActeurController::class);
    Route::resource('acteurs', ActeurController::class);
});

// ═══════════════════════════════════════════════════════════════════════════
// Module 1 — Produits & Catégories (Ghada)
// ORDRE : les routes statiques (/produits/comparer, /ia/produits/*) sont
// déclarées AVANT /produits/{produit} pour éviter que « comparer » soit
// interprété comme un identifiant de produit.
// ═══════════════════════════════════════════════════════════════════════════

// Catalogue public
Route::get('/produits', CatalogueController::class)->name('front.produits.index');

// Comparateur fiches produits — AVANT {produit}
Route::get('/produits/comparer', [ComparateurController::class, 'index'])->name('front.produits.comparer');
Route::post('/produits/comparaison/ajouter', [ComparateurController::class, 'ajouter'])->name('front.produits.comparaison.ajouter');
Route::delete('/produits/comparaison/retirer/{id}', [ComparateurController::class, 'retirer'])
    ->name('front.produits.comparaison.retirer')
    ->whereNumber('id');
Route::delete('/produits/comparaison/vider', [ComparateurController::class, 'vider'])->name('front.produits.comparaison.vider');

// Fiche produit publique — {produit} contraint en entier
Route::get('/produits/{produit}', [FrontProduitController::class, 'show'])
    ->name('front.produits.show')
    ->whereNumber('produit');

// Détail d'un lot (JSON) pour les onglets de la fiche
Route::get('/produits/{produit}/lots/{lot}', [FrontProduitController::class, 'lotJson'])
    ->name('front.produits.lot_json')
    ->whereNumber(['produit', 'lot'])
    ->scopeBindings();

// Recommandations IA — JSON différé
Route::get('/produits/{produit}/recommandations', [IaRecommandationsController::class, 'index'])
    ->middleware('throttle:60,1')
    ->name('front.produits.recommandations')
    ->whereNumber('produit');

// Routes IA produits (auth + throttle)
Route::middleware(['auth', 'throttle:20,1'])->group(function () {
    Route::post('/ia/produits/categoriser', [IaCategoriserController::class, 'categoriser'])
        ->name('ia.produits.categoriser');

    Route::post('/ia/produits/ocr', [IaOcrController::class, 'lire'])
        ->name('ia.produits.ocr');
});

// Administration du catalogue
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Sans parameters(), Laravel nommerait le paramètre {category} et la liaison avec $categorie échouerait.
    Route::resource('categories', CategorieController::class)->parameters(['categories' => 'categorie']);
    Route::resource('produits', AdminProduitController::class);
});

// ═══════════════════════════════════════════════════════════════════════════
// Module 2 — Laboratoires & Analyses qualité (Amin)
// ═══════════════════════════════════════════════════════════════════════════

Route::get('/analyses/{analyse}/rapport', RapportAnalyseController::class)
    ->middleware(['auth', 'can:telechargerRapport,analyse'])
    ->name('front.analyses.rapport');

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Export AVANT le resource (sinon « export » serait pris pour un id de laboratoire)
    Route::get('laboratoires/export', LaboratoireExportController::class)->name('laboratoires.export');
    Route::resource('laboratoires', LaboratoireController::class);

    Route::get('analyses/export', AnalyseExportController::class)->name('analyses.export');
    Route::post('analyses/ia/prefill', [AdminAnalyseIaController::class, 'prefill'])->name('analyses.ia.prefill');
    Route::post('analyses/{analyse}/ia/extraire', [AdminAnalyseIaController::class, 'extraire'])->name('analyses.ia.extraire');
    Route::post('analyses/{analyse}/ia/resumer', [AdminAnalyseIaController::class, 'resumer'])->name('analyses.ia.resumer');
    Route::post('lots/{lot}/ia/risque', [AdminAnalyseIaController::class, 'risque'])->name('lots.ia.risque');

    Route::resource('analyses', AdminAnalyseController::class)->parameters(['analyses' => 'analyse']);
});

// ═══════════════════════════════════════════════════════════════════════════
// Module 3 — Traçabilité des lots (Ons)
// ═══════════════════════════════════════════════════════════════════════════

Route::get('/tracabilite', TracabiliteController::class)->name('front.lots.search');
Route::get('/tracabilite/{lot:numero_lot}', [FrontLotController::class, 'show'])->name('front.lots.show');

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('lots/export', [AdminLotController::class, 'export'])->name('lots.export');
    Route::post('lots/{lot}/recit-ia', [AdminLotController::class, 'regenererRecit'])->name('lots.recit-ia');
    Route::post('lots/{lot}/audit-ia', [AdminLotController::class, 'analyserAuditIa'])->name('lots.audit-ia');
    Route::post('lots/{lot}/conseil-ia', [AdminLotController::class, 'genererConseilIa'])->name('lots.conseil-ia');
    Route::resource('lots', AdminLotController::class);
    Route::post('etapes/assistant-ai', [AdminEtapeController::class, 'assistantAi'])->name('etapes.assistant-ai');
    Route::resource('etapes', AdminEtapeController::class);
});

// ═══════════════════════════════════════════════════════════════════════════
// Module 4 — Empreinte environnementale (Sahar)
// ═══════════════════════════════════════════════════════════════════════════

Route::get('/empreintes', EcoScoreController::class)->name('front.empreintes.index');
Route::get('/comparer', ComparaisonController::class)->name('front.comparaison');

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('empreintes', EmpreinteCarboneController::class);
    Route::resource('indicateurs', AdminIndicateurController::class);

    Route::post('empreintes/{empreinte}/audit-ia', [EmpreinteIaController::class, 'auditer'])->name('empreintes.audit-ia');
    Route::delete('empreintes/{empreinte}/audit-ia', [EmpreinteIaController::class, 'reinitialiser'])->name('empreintes.audit-ia.reinitialiser');

    Route::get('facteurs-climatiq', [FacteurClimatiqController::class, 'index'])->name('facteurs-climatiq.index');
    Route::post('facteurs-climatiq/synchroniser', [FacteurClimatiqController::class, 'synchroniser'])->name('facteurs-climatiq.synchroniser');
});

// ═══════════════════════════════════════════════════════════════════════════
// Module 5 — Certifications (Marwa)
// ═══════════════════════════════════════════════════════════════════════════

Route::get('/certifications', LabelController::class)->name('front.certifications.index');
Route::get('/certifications/assistant', ChatCertificationController::class)->name('front.certifications.chat');
Route::post('/certifications/assistant', [ChatCertificationController::class, 'repondre'])->name('front.certifications.chat.repondre')->middleware('throttle:30,1');

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('organismes', OrganismeController::class);
    Route::resource('certifications', AdminCertificationController::class);
    Route::patch('certifications/{certification}/valider', [AdminCertificationController::class, 'valider'])->name('certifications.valider');
    Route::patch('certifications/{certification}/suspendre', [AdminCertificationController::class, 'suspendre'])->name('certifications.suspendre');

    // Export QR Code + PDF certificat (Module 5)
    Route::get('certifications/{certification}/pdf', [CertificationExportController::class, 'pdf'])->name('certifications.pdf');
    Route::get('certifications/{certification}/qr', [CertificationExportController::class, 'qr'])->name('certifications.qr');

    // Traduction de la fiche (fr/en/ar)
    Route::post('certifications/{certification}/traduire', CertificationTraductionController::class)->name('certifications.traduire');

    // IA certifications
    Route::post('certifications/{certification}/ia/upload', [CertificationIaController::class, 'upload'])->name('certifications.ia.upload');
    Route::post('certifications/{certification}/ia/verifier', [CertificationIaController::class, 'verifier'])->name('certifications.ia.verifier');
    Route::post('certifications/{certification}/ia/incoherences', [CertificationIaController::class, 'incoherences'])->name('certifications.ia.incoherences');
});

// ═══════════════════════════════════════════════════════════════════════════
// Espace consommateur (qualité, scans, assistant)
// ═══════════════════════════════════════════════════════════════════════════

Route::middleware(['auth', 'verified', 'can:espace-consommateur'])->prefix('mon-espace')->name('consommateur.')->group(function () {
    Route::get('/scans', ScanController::class)->name('scans.index');

    Route::get('/qualite', [ConsommateurQualiteController::class, 'index'])->name('qualite.index');
    // Popup Qualité d'un produit (chargé en AJAX depuis les cartes catalogue)
    Route::get('/qualite/produit/{produit}', ProduitQualiteController::class)->name('qualite.produit');
    Route::post('/qualite/analyses/{analyse}/resumer', [ConsommateurQualiteController::class, 'resumerAnalyse'])->name('qualite.resumer');
    Route::post('/qualite/lots/{lot}/risque', [ConsommateurQualiteController::class, 'risqueLot'])->name('qualite.risque');

    Route::post('/assistant-qualite', ChatQualiteController::class)->name('chat-qualite');
});

// ═══════════════════════════════════════════════════════════════════════════
// Espace professionnel (producteurs, transformateurs, distributeurs)
// Les droits de chaque rôle sont définis dans config/nutritrace.php
// (roles_pro) et vérifiés par les Gates et les policies.
// ═══════════════════════════════════════════════════════════════════════════

Route::middleware(['auth', 'verified', 'can:espace-pro'])->prefix('pro')->name('pro.')->group(function () {
    Route::get('/', ProDashboardController::class)->name('dashboard');

    Route::get('/profil', [ProfilController::class, 'edit'])->name('profil.edit');
    Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');

    Route::get('/qualite', ProQualiteController::class)->name('qualite')->middleware('profil-societe');

    Route::middleware('can:pro-produits')->group(function () {
        Route::resource('produits', ProProduitController::class)->except('show');

        Route::get('/certifications', [ProCertificationController::class, 'index'])->name('certifications.index');
        Route::get('/certifications/create', [ProCertificationController::class, 'create'])->name('certifications.create');
        Route::post('/certifications', [ProCertificationController::class, 'store'])->name('certifications.store');
    });

    Route::middleware('profil-societe')->group(function () {
        Route::get('/lots', [ProLotController::class, 'index'])->name('lots.index');
        Route::middleware('can:pro-creer-lot')->group(function () {
            Route::get('/lots/create', [ProLotController::class, 'create'])->name('lots.create');
            Route::post('/lots/assistant-ai', [ProLotController::class, 'assistantAi'])->name('lots.assistant-ai');
            Route::post('/lots', [ProLotController::class, 'store'])->name('lots.store');
        });
        Route::get('/lots/{lot}', [ProLotController::class, 'show'])->name('lots.show')->can('view', 'lot');
        Route::post('/lots/{lot}/transfert', [ProLotController::class, 'transferer'])->name('lots.transfert')->can('transferer', 'lot');

        Route::post('/etapes/assistant-ai', [ProEtapeController::class, 'assistantAi'])->name('etapes.assistant-ai');
        Route::get('/lots/{lot}/etapes/create', [ProEtapeController::class, 'create'])->name('etapes.create')->can('ajouterEtape', 'lot');
        Route::post('/lots/{lot}/etapes', [ProEtapeController::class, 'store'])->name('etapes.store')->can('ajouterEtape', 'lot');
        Route::get('/etapes/{etape}/edit', [ProEtapeController::class, 'edit'])->name('etapes.edit')->can('update', 'etape');
        Route::put('/etapes/{etape}', [ProEtapeController::class, 'update'])->name('etapes.update')->can('update', 'etape');
        Route::delete('/etapes/{etape}', [ProEtapeController::class, 'destroy'])->name('etapes.destroy')->can('delete', 'etape');

        Route::get('/etapes/{etape}/indicateurs/create', [ProIndicateurController::class, 'create'])->name('indicateurs.create')->can('ajouterIndicateur', 'etape');
        Route::post('/etapes/{etape}/indicateurs', [ProIndicateurController::class, 'store'])->name('indicateurs.store')->can('ajouterIndicateur', 'etape');

        Route::get('/lots/{lot}/analyses/create', [ProAnalyseController::class, 'create'])->name('analyses.create')->can('ajouterAnalyse', 'lot');
        Route::post('/lots/{lot}/analyses', [ProAnalyseController::class, 'store'])->name('analyses.store')->can('ajouterAnalyse', 'lot');
        Route::get('/analyses/{analyse}/edit', [ProAnalyseController::class, 'edit'])->name('analyses.edit')->can('update', 'analyse');
        Route::put('/analyses/{analyse}', [ProAnalyseController::class, 'update'])->name('analyses.update')->can('update', 'analyse');
        Route::delete('/analyses/{analyse}', [ProAnalyseController::class, 'destroy'])->name('analyses.destroy')->can('delete', 'analyse');

        Route::post('/lots/{lot}/analyses/ia/prefill', [ProAnalyseIaController::class, 'prefill'])->name('analyses.ia.prefill')->can('ajouterAnalyse', 'lot');
        Route::post('/analyses/{analyse}/ia/extraire', [ProAnalyseIaController::class, 'extraire'])->name('analyses.ia.extraire')->can('update', 'analyse');
        Route::post('/analyses/{analyse}/ia/resumer', [ProAnalyseIaController::class, 'resumer'])->name('analyses.ia.resumer')->can('update', 'analyse');
        Route::post('/lots/{lot}/ia/risque', [ProAnalyseIaController::class, 'risque'])->name('lots.ia.risque')->can('view', 'lot');
    });
});
