@php($classes = ['conforme' => '', 'non_conforme' => 'nt-badge-danger', 'en_attente' => 'nt-badge-gold'])
<span class="nt-badge {{ $classes[$analyse->resultat] ?? 'nt-badge-muted' }}">{{ $analyse->resultatLabel() }}</span>
