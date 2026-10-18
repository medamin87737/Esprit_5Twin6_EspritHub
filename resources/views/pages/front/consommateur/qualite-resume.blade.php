@extends('layouts.front')

@section('title', 'Résumé qualité IA')

@section('content')
    <x-front.page-header eyebrow="Qualité" title="{{ $data['titre_resume'] ?? 'Synthèse qualité' }}" image="consommateur-bowl.webp"
                         subtitle="Résumé généré à partir de « {{ $nomFichier }} »." />

    <section class="page-section pt-0 bg-cream">
        <div class="container px-4 px-lg-5 col-lg-8">
            <div class="account-card">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    @if (! empty($data['resultat']))
                        <span class="status-pill {{ $data['resultat'] === 'conforme' ? 'status-valid' : ($data['resultat'] === 'non_conforme' ? 'status-expired' : 'status-pending') }}">
                            {{ config('nutritrace.options.analyse_resultats.'.$data['resultat'], $data['resultat']) }}
                        </span>
                    @endif
                    @if (! empty($data['type']))
                        <span class="label-chip">{{ config('nutritrace.options.analyse_types.'.$data['type'], $data['type']) }}</span>
                    @endif
                </div>

                <div class="mb-3" style="white-space: pre-wrap; line-height: 1.7;">
                    {!! preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', e($data['resume_qualite'] ?? $data['resume_court'] ?? 'Résumé indisponible.')) !!}
                </div>

                @if (! empty($data['commentaire']))
                    <p class="small text-muted mb-0"><strong>Commentaire :</strong> {{ $data['commentaire'] }}</p>
                @endif

                <div class="form-actions mt-4">
                    <a href="{{ route('consommateur.qualite.index', $numero ? ['numero' => $numero] : []) }}" class="btn btn-outline-secondary">Retour</a>
                    @if ($numero)
                        <a href="{{ route('consommateur.qualite.index', ['numero' => $numero]) }}" class="btn btn-primary">Voir le lot {{ $numero }}</a>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
