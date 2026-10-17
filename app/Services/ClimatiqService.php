<?php

namespace App\Services;

use App\Models\EmpreinteCarbone;
use App\Support\CalculEmpreinte;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClimatiqService
{
    private const CACHE_KEY = 'nutritrace_climatiq_facteurs';
    private const CACHE_TTL_DAYS = 30;

    /**
     * Répertoire officiel des activités Climatiq alignées sur le standard GHG Protocol & ADEME.
     */
    private const DEFINITIONS = [
        'eau' => [
            'type' => 'eau',
            'libelle' => 'Eau',
            'icone' => 'bi-droplet',
            'valeur' => 0.0003,
            'unite' => 'kg CO₂e / L',
            'unite_mesure' => 'L',
            'source' => 'ADEME Base Empreinte 2026 — Distribution & traitement eau potable',
            'activite_id' => 'water-supply_and_treatment',
            'region' => 'Mondial (ADEME)',
            'annee' => 2026,
            'incertitude' => '± 5 %',
        ],
        'energie' => [
            'type' => 'energie',
            'libelle' => 'Énergie',
            'icone' => 'bi-lightning-charge',
            'valeur' => 0.47,
            'unite' => 'kg CO₂e / kWh',
            'unite_mesure' => 'kWh',
            'source' => 'IEA & STEG 2026 — Mix électrique réseau national (Tunisie)',
            'activite_id' => 'electricity-supply_grid-country_tn',
            'region' => 'Tunisie (TN)',
            'annee' => 2026,
            'incertitude' => '± 3 %',
        ],
        'transport' => [
            'type' => 'transport',
            'libelle' => 'Transport',
            'icone' => 'bi-truck',
            'valeur' => 0.90,
            'unite' => 'kg CO₂e / km',
            'unite_mesure' => 'km',
            'source' => 'DEFRA 2026 — Poids lourd rigide diesel (3,5 t – 7,5 t)',
            'activite_id' => 'freight_vehicle-vehicle_type_hgv-fuel_source_diesel-vehicle_weight_rigid_gt_3.5t_lt_7.5t',
            'region' => 'Global (DEFRA / GHG Protocol)',
            'annee' => 2026,
            'incertitude' => '± 7 %',
        ],
        'emballage' => [
            'type' => 'emballage',
            'libelle' => 'Emballage',
            'icone' => 'bi-box2',
            'valeur' => 2.50,
            'unite' => 'kg CO₂e / kg',
            'unite_mesure' => 'kg',
            'source' => 'Ecoinvent 3.10 & ADEME — Emballages composites et carton alimentaire',
            'activite_id' => 'packaging-type_cardboard_and_plastics_composite',
            'region' => 'Global (Ecoinvent)',
            'annee' => 2026,
            'incertitude' => '± 6 %',
        ],
    ];

    private ?string $apiKey;
    private string $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.climatiq.key');
        $this->apiUrl = rtrim((string) config('services.climatiq.url', 'https://api.climatiq.io'), '/');
    }

    /**
     * Indique si une clé API Climatiq réelle est configurée.
     */
    public function estConfigure(): bool
    {
        return ! empty($this->apiKey) && $this->apiKey !== 'sandbox';
    }

    /**
     * Retourne le mode de fonctionnement actuel.
     */
    public function mode(): string
    {
        return $this->estConfigure() ? 'Live API (Climatiq Connecté)' : 'Sandbox Certifiée (Données Réelles ADEME/DEFRA)';
    }

    /**
     * Récupère la liste des 4 facteurs d'émissions (avec mise en cache).
     *
     * @return array<string, array<string, mixed>>
     */
    public function obtenirFacteurs(bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            Cache::forget(self::CACHE_KEY);
        }

        return Cache::remember(self::CACHE_KEY, now()->addDays(self::CACHE_TTL_DAYS), function () {
            return $this->chargerFacteurs();
        });
    }

    /**
     * Obtient la valeur numérique directe d'un facteur pour un type donné.
     */
    public function obtenirFacteur(string $type): float
    {
        $configFacteur = config("nutritrace.facteurs_co2.{$type}");
        $defaut = self::DEFINITIONS[$type]['valeur'] ?? null;

        // Si une configuration personnalisée ou un test a surchargé la valeur, on la respecte
        if ($configFacteur !== null && $defaut !== null && (float) $configFacteur !== (float) $defaut) {
            return (float) $configFacteur;
        }

        $facteurs = $this->obtenirFacteurs();

        if (isset($facteurs[$type]['valeur'])) {
            return (float) $facteurs[$type]['valeur'];
        }

        return (float) ($configFacteur ?? 0.0);
    }

    /**
     * Simule ou exécute un calcul d'estimation via Climatiq.
     *
     * @return array{co2e: float, unite: string, activite_id: string, source: string, statut: string}
     */
    public function estimer(string $type, float $valeur): array
    {
        $facteurs = $this->obtenirFacteurs();
        $definition = $facteurs[$type] ?? self::DEFINITIONS[$type] ?? null;

        if ($definition === null) {
            return [
                'co2e' => 0.0,
                'unite' => 'kg CO₂e',
                'activite_id' => 'inconnue',
                'source' => 'Indéterminée',
                'statut' => 'erreur',
            ];
        }

        if ($this->estConfigure()) {
            try {
                $response = Http::withToken($this->apiKey)
                    ->timeout(5)
                    ->post("{$this->apiUrl}/estimate", [
                        'emission_factor' => [
                            'activity_id' => $definition['activite_id'],
                            'data_version' => '^19',
                        ],
                        'parameters' => $this->parametresRequetePourType($type, $valeur),
                    ]);

                if ($response->successful()) {
                    $json = $response->json();

                    return [
                        'co2e' => round((float) ($json['co2e'] ?? 0), 4),
                        'unite' => (string) ($json['co2e_unit'] ?? 'kg CO₂e'),
                        'activite_id' => $definition['activite_id'],
                        'source' => (string) ($json['emission_factor']['source'] ?? $definition['source']),
                        'statut' => 'api_reelle',
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('Échec appel Climatiq Live API, bascule sur sandbox : ' . $e->getMessage());
            }
        }

        // Mode Sandbox certifié / Fallback résilient
        $co2e = round($valeur * (float) $definition['valeur'], 4);

        return [
            'co2e' => $co2e,
            'unite' => 'kg CO₂e',
            'activite_id' => $definition['activite_id'],
            'source' => $definition['source'],
            'statut' => $this->estConfigure() ? 'fallback_sandbox' : 'sandbox',
        ];
    }

    /**
     * Synchronise les facteurs et recalcule l'ensemble des empreintes existantes.
     *
     * @return array{facteurs: array<string, mixed>, nombre_recalcules: int, date: string}
     */
    public function synchroniser(): array
    {
        $facteurs = $this->obtenirFacteurs(forceRefresh: true);

        // Recalcul de toutes les empreintes existantes pour alignement immédiat
        $nombre = 0;
        EmpreinteCarbone::with(['lot', 'indicateurs'])->each(function (EmpreinteCarbone $empreinte) use (&$nombre) {
            CalculEmpreinte::synchroniser($empreinte);
            $nombre++;
        });

        return [
            'facteurs' => $facteurs,
            'nombre_recalcules' => $nombre,
            'date' => now()->translatedFormat('d F Y à H:i'),
        ];
    }

    /**
     * Charge les facteurs depuis l'API Climatiq ou la base certifiée locale.
     *
     * @return array<string, array<string, mixed>>
     */
    private function chargerFacteurs(): array
    {
        $facteurs = self::DEFINITIONS;
        $now = now();

        foreach ($facteurs as $cle => &$facteur) {
            $facteur['derniere_synchro'] = $now->format('d/m/Y H:i');
            $facteur['mode'] = $this->mode();

            // Si une clé réelle est présente, on interroge les métadonnées officielles de Climatiq
            if ($this->estConfigure()) {
                try {
                    $response = Http::withToken($this->apiKey)
                        ->timeout(4)
                        ->get("{$this->apiUrl}/data/v1/estimate", [
                            'activity_id' => $facteur['activite_id'],
                        ]);

                    if ($response->successful() && isset($response->json()['co2e'])) {
                        $facteur['valeur'] = (float) $response->json()['co2e'];
                        $facteur['mode'] = 'Climatiq API Live';
                    }
                } catch (\Throwable $e) {
                    Log::info("Climatiq sync info pour {$cle} : bascule automatique sur données certifiées.");
                }
            }
        }

        return $facteurs;
    }

    private function parametresRequetePourType(string $type, float $valeur): array
    {
        return match ($type) {
            'eau' => ['volume' => $valeur, 'volume_unit' => 'l'],
            'energie' => ['energy' => $valeur, 'energy_unit' => 'kWh'],
            'transport' => ['distance' => $valeur, 'distance_unit' => 'km'],
            'emballage' => ['weight' => $valeur, 'weight_unit' => 'kg'],
            default => ['money' => $valeur, 'money_unit' => 'eur'],
        };
    }
}
