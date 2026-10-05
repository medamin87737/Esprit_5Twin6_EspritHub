@props(['certification'])

<span {{ $attributes->class(['label-chip', 'cert-badge']) }}
      title="{{ $certification->typeLabel() }} n° {{ $certification->numero }}{{ $certification->organisme ? ' · ' . $certification->organisme->nom : '' }}">
    <i class="bi {{ $certification->icone() }}" aria-hidden="true"></i>{{ $certification->typeLabel() }}
</span>
