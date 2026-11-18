@extends('layouts.admin')
@section('title', 'Reportes')
@section('content')
<section class="admin-reports">
    <h1>Reportes generales</h1>
    <p class="admin-subtitle">Consulta postulaciones, estudiantes, vacantes y valoraciones registradas en NextStep.</p>

    <div class="admin-summary admin-reports__summary">
        @foreach ([
            'Postulaciones' => $summary['applications'],
            'Vacantes registradas' => $summary['vacancies'],
            'Empresas' => $summary['companies'],
            'Valoraciones' => $summary['reviews']
        ] as $label => $value)
            <div class="admin-stat"><strong>{{ $value }}</strong><span>{{ $label }}</span></div>
        @endforeach
    </div>

    @php
        $reports = [
            'applications' => 'Postulaciones por vacante',
            'students' => 'Seguimiento de estudiantes',
            'vacancies' => 'Vacantes por empresa',
            'reviews' => 'Valoraciones por empresa'
        ];
        $statuses = in_array($report, ['applications', 'students'])
            ? ['pending' => 'Pendiente', 'accepted' => 'Aceptada', 'rejected' => 'Rechazada']
            : ['published' => 'Publicada', 'closed' => 'Cerrada'];
    @endphp
    <nav class="admin-reports__tabs" aria-label="Tipos de reportes">
        @foreach ($reports as $key => $label)
            <a href="{{ route('admin.reports.index', ['report' => $key]) }}"
               @if($report === $key) aria-current="page" @endif class="admin-reports__tab">{{ $loop->iteration }}. {{ $label }}</a>
        @endforeach
    </nav>

    <h2>{{ $reports[$report] }}</h2>
    <form class="admin-toolbar" method="GET" action="{{ route('admin.reports.index') }}">
        <input type="hidden" name="report" value="{{ $report }}">
        <label class="admin-search">Buscar
            <input type="search" name="q" maxlength="100" value="{{ request('q') }}" placeholder="Nombre, vacante o empresa">
        </label>
        <label>Estado
            <select name="status">
                <option value="">Todos</option>
                @foreach ($statuses as $key => $label)
                    <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <button class="admin-button admin-button--primary" type="submit">Filtrar</button>
        <a class="admin-link" href="{{ route('admin.reports.index', ['report' => $report]) }}">Limpiar</a>
    </form>

    <div class="admin-table-wrap admin-reports__table">
        <table class="admin-table">
            <thead>
                @if ($report === 'applications')
                    <tr><th>Vacante</th><th>Empresa</th><th>Pendientes</th><th>Aceptadas</th><th>Rechazadas</th><th>Total</th></tr>
                @elseif ($report === 'students')
                    <tr><th>Estudiante</th><th>Vacante</th><th>Empresa</th><th>Estado</th></tr>
                @elseif ($report === 'vacancies')
                    <tr><th>Empresa</th><th>Vacante</th><th>Estado</th></tr>
                @else
                    <tr><th>Empresa</th><th>Valoraciones</th><th>Promedio</th><th>Publicadas</th><th>Cerradas</th></tr>
                @endif
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    @if ($report === 'applications')
                        <tr><td>{{ $row['vacancy'] }}</td><td>{{ $row['company'] }}</td><td>{{ $row['pending'] }}</td><td>{{ $row['accepted'] }}</td><td>{{ $row['rejected'] }}</td><td><strong>{{ $row['total'] }}</strong></td></tr>
                    @elseif ($report === 'students')
                        <tr><td>{{ $row['student'] }}</td><td>{{ $row['vacancy'] }}</td><td>{{ $row['company'] }}</td>
                        <td><span class="admin-badge {{ $row['status'] === 'pending' ? 'admin-badge--pending' : ($row['status'] === 'rejected' ? 'admin-badge--failed' : '') }}">{{ ['pending' => 'Pendiente', 'accepted' => 'Aceptada', 'rejected' => 'Rechazada'][$row['status']] ?? $row['status'] }}</span></td></tr>
                    @elseif ($report === 'vacancies')
                        <tr><td>{{ $row['company'] }}</td><td>{{ $row['vacancy'] }}</td><td><span class="admin-badge {{ $row['status'] === 'closed' ? 'admin-badge--neutral' : '' }}">{{ ['published' => 'Publicada', 'closed' => 'Cerrada'][$row['status']] ?? $row['status'] }}</span></td></tr>
                    @else
                        <tr><td>{{ $row['company'] }}</td><td>{{ $row['reviews'] }}</td><td>{{ $row['average'] !== null ? number_format($row['average'], 1).' / 5' : 'Sin valoraciones' }}</td><td>{{ $row['published'] }}</td><td>{{ $row['closed'] }}</td></tr>
                    @endif
                @empty
                    <tr><td colspan="{{ $report === 'applications' ? 6 : ($report === 'students' ? 4 : ($report === 'vacancies' ? 3 : 5)) }}" class="admin-empty">No hay registros para estos filtros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <p class="admin-subtitle" style="margin-top:14px">Registros mostrados: {{ $rows->count() }}.</p>
</section>
@endsection
