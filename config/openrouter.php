<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenRouter (Module 2 — Analyses qualité / IA)
    |--------------------------------------------------------------------------
    |
    | Clé sur https://openrouter.ai/keys — compatible API OpenAI.
    | Modèle gratuit : openrouter/free (texte uniquement, sans Python / vision).
    |
    */

    'api_key' => env('OPENROUTER_API_KEY'),

    'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),

    'model' => env('OPENROUTER_MODEL', 'openrouter/free'),

    'timeout' => (int) env('OPENROUTER_TIMEOUT', 20),

    'connect_timeout' => (int) env('OPENROUTER_CONNECT_TIMEOUT', 8),

    'site_url' => env('OPENROUTER_SITE_URL', env('APP_URL', 'http://127.0.0.1:8000')),

    'site_name' => env('OPENROUTER_SITE_NAME', env('APP_NAME', 'NutriTrace')),

    /*
    | Délai (jours) au-delà duquel une analyse « en attente » devient une alerte.
    */
    'alerte_en_attente_jours' => (int) env('ANALYSES_ALERTE_JOURS', 7),

];
