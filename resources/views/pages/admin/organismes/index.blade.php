@extends('layouts.admin')

@section('title', 'Organismes')

@section('content')
    @php($organismes = $organismes ?? collect())

    <x-admin.page-header title="Organismes certificateurs" module="Module 5 · Certifications"
                         subtitle="Les organismes accrédités qui délivrent les labels.">
        <x-slot:actions>
            <a href="{{ route('admin.organismes.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Nouvel organisme
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.table-card :items="$organismes" title="Liste des organismes" search-placeholder="Nom ou pays…">
        <x-slot:head>
            <th scope="col">Organisme</th>
            <th scope="col">Pays</th>
            <th scope="col">Accréditation</th>
            <th scope="col" class="text-center">Certifications</th>
            <th scope="col" class="text-right">Actions</th>
        </x-slot:head>

        @foreach ($organismes as $organisme)
            <tr>
                <td>
                    <div class="nt-cell-title">{{ $organisme->nom }}</div>
                    <a href="{{ $organisme->site_web }}" class="nt-cell-sub" target="_blank" rel="noopener">
                        {{ parse_url($organisme->site_web, PHP_URL_HOST) ?? $organisme->site_web }} <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                    </a>
                </td>
                <td>{{ $organisme->pays }}</td>
                <td><span class="nt-badge nt-badge-info">{{ $organisme->accreditation }}</span></td>
                <td class="text-center font-weight-600">{{ $organisme->certifications_count ?? $organisme->certifications->count() }}</td>
                <td class="text-right">
                    <x-admin.row-actions
                        :show="route('admin.organismes.show', $organisme)"
                        :edit="route('admin.organismes.edit', $organisme)"
                        :delete="route('admin.organismes.destroy', $organisme)"
                        :confirm="'Supprimer l\'organisme « ' . $organisme->nom . ' » ?'" />
                </td>
            </tr>
        @endforeach

        <x-slot:empty>
            <x-admin.empty-state icon="bi-bank" title="Aucun organisme enregistré">
                Ajoutez les organismes certificateurs avant de saisir les certifications qu'ils délivrent.
                <x-slot:action>
                    <a href="{{ route('admin.organismes.create') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i> Ajouter un organisme
                    </a>
                </x-slot:action>
            </x-admin.empty-state>
        </x-slot:empty>
    </x-admin.table-card>
@endsection
