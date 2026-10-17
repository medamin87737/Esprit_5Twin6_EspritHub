<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenRouter (Module 2 — Analyses qualité / IA)
    |--------------------------------------------------------------------------
    |
    | Clé sur https://openrouter.ai/keys — compatible API OpenAI.
    | Modèle multimodal recommandé pour lire les PDF : google/gemini-2.0-flash-001
    |
    */

    'api_key' => env('OPENROUTER_API_KEY'),

    'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),

    'model' => env('OPENROUTER_MODEL', 'google/gemini-2.0-flash-001'),

    'timeout' => (int) env('OPENROUTER_TIMEOUT', 60),

    'site_url' => env('OPENROUTER_SITE_URL', env('APP_URL', 'http://127.0.0.1:8000')),

    'site_name' => env('OPENROUTER_SITE_NAME', env('APP_NAME', 'NutriTrace')),

    /*
    | Délai (jours) au-delà duquel une analyse « en attente » devient une alerte.
    */
    'alerte_en_attente_jours' => (int) env('ANALYSES_ALERTE_JOURS', 7),

];
