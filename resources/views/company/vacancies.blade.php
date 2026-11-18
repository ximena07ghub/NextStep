@extends('layouts.company-dashboard')

@section('dashboard-content')

<section class="company-vacancies">

    {{-- ========================================
         TITLE
    ========================================= --}}

    <h1 class="company-vacancies__title">Mis vacantes</h1>
    <p>Administra tus oportunidades laborales y consulta sus detalles.</p>
    @if ($vacancies->isNotEmpty())
    <p><a class="admin-button admin-button--primary" href="{{ route('company.vacancies.create') }}">Publicar nueva vacante</a></p>
    @endif


    {{-- ========================================
         VACANCIES GRID
    ========================================= --}}

    <div class="company-vacancies__grid">

        @forelse ($vacancies as $vacancy)

            @if ($vacancy['status'] === 'closed')
                <form method="POST" action="{{ route('company.vacancies.renew', $vacancy['id']) }}">
                    @csrf
                    <span class="admin-badge admin-badge--neutral">Cerrada</span>
                    <button class="admin-button admin-button--primary" type="submit">Renovar vacante</button>
                </form>
            @endif
            <x-vacancy-card
                :vacancy="$vacancy"
                variant="company"
                :href="route(
                    'company.vacancies.show',
                    ['id' => $vacancy['id']]
                )"
            />

        @empty
            <div role="status"><p>Aún no has publicado vacantes.</p><a class="admin-button admin-button--primary" href="{{ route('company.vacancies.create') }}">Crear mi primera vacante</a></div>
        @endforelse

    </div>

</section>

@endsection