<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | OpenRouter — API compatible OpenAI
    |--------------------------------------------------------------------------
    |
    | Base URL : https://openrouter.ai/api/v1
    | Auth     : Authorization: Bearer OPENROUTER_API_KEY
    | En-têtes optionnels : HTTP-Referer (APP_URL), X-Title (NutriTrace)
    |
    | Les identifiants de modèles sont vérifiés sur https://openrouter.ai/models
    |
    */

    'openrouter_url' => env('OPENROUTER_URL', 'https://openrouter.ai/api/v1'),

    'cle' => env('OPENROUTER_API_KEY'),

    'modeles' => [
        'texte' => env('OPENROUTER_MODELE_TEXTE', 'google/gemini-2.5-flash'),
        'vision' => env('OPENROUTER_MODELE_VISION', 'google/gemini-2.5-flash'),
        'embeddings' => env('OPENROUTER_MODELE_EMBEDDINGS', 'openai/text-embedding-3-small'),
    ],

    'timeout' => (int) env('IA_TIMEOUT', 20),

];
