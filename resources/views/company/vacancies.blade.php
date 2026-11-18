@extends('layouts.company-dashboard')

@section('dashboard-content')
<section class="company-vacancies">
    <h1 class="company-vacancies__title">Mis vacantes</h1>
    <p>Administra tus oportunidades laborales y consulta sus detalles.</p>

    @if ($vacancies->isNotEmpty())
        <a class="admin-button admin-button--primary" href="{{ route('company.vacancies.create') }}">Publicar nueva vacante</a>
    @endif

    <div class="company-vacancies__grid">
        @forelse ($vacancies as $vacancy)
            <article class="company-vacancies__item">
                <x-vacancy-card
                    :vacancy="$vacancy"
                    variant="company"
                    :href="route('company.vacancies.show', ['id' => $vacancy['id']])"
                />

                @if ($vacancy['status'] === 'closed')
                    <div class="company-vacancies__actions">
                        <span class="admin-badge admin-badge--neutral">Cerrada</span>
                        <form method="POST" action="{{ route('company.vacancies.renew', $vacancy['id']) }}">
                            @csrf
                            <button type="submit" class="admin-button admin-button--primary">Renovar vacante</button>
                        </form>
                    </div>
                @else
                    <div class="company-vacancies__actions">
                        <span class="admin-badge">Publicada</span>
                    </div>
                @endif
            </article>
        @empty
            <div class="company-vacancies__empty" role="status">
                <p>Aún no has publicado vacantes.</p>
                <a class="admin-button admin-button--primary" href="{{ route('company.vacancies.create') }}">Crear mi primera vacante</a>
            </div>
        @endforelse
    </div>
</section>
@endsection
