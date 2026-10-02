@php($classes = ['valide' => '', 'expiree' => 'nt-badge-danger', 'suspendue' => 'nt-badge-gold'])
<span class="nt-badge {{ $classes[$certification->statutEffectif()] ?? 'nt-badge-muted' }}">{{ $certification->statutLabel() }}</span>
@if ($certification->expireBientot())
    <span class="nt-badge nt-badge-danger ml-1" title="Expire dans moins de 30 jours"><i class="bi bi-alarm" aria-hidden="true"></i> {{ $certification->joursRestants() }} j</span>
@endif
