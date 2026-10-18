<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Analyse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RapportAnalyseController extends Controller
{
    public function __invoke(Analyse $analyse): StreamedResponse
    {
        $disque = Storage::disk(Analyse::DISQUE);

        abort_unless($disque->exists($analyse->rapport), 404);

        $ext = pathinfo((string) $analyse->rapport, PATHINFO_EXTENSION) ?: 'txt';

        return $disque->download($analyse->rapport, "Rapport-{$analyse->numero}.{$ext}");
    }
}
