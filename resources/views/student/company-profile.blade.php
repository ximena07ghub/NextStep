@extends('layouts.student')

@section('content')

<main
    class="company-public-profile"
    data-company-public-profile
>




    {{-- PARTE SUPERIOR --}}

    <section class="company-public-profile__top">

        <div class="company-public-profile__header">

            <div class="company-public-profile__identity">

                @if ($company['logo'])

                    <img
                        src="{{ $company['logo'] }}"
                        alt="Logo de {{ $company['name'] }}"
                        class="company-public-profile__logo"
                    >

                @else

                    <div class="
                        company-public-profile__logo
                        company-public-profile__logo--empty
                    "></div>

                @endif


                <div class="company-public-profile__identity-info">

                    <h1 class="company-public-profile__name">
                        {{ $company['name'] }}
                    </h1>


                    <div class="company-public-profile__meta">

                        <span class="company-public-profile__sector">
                            {{ $company['sector'] }}
                        </span>

                    </div>
                    <p class="company-public-profile__rating">{{ $averageRating !== null ? number_format($averageRating, 1) . ' / 5 · ' . $reviews->count() . ' valoraciones' : 'Sin valoraciones todavía' }}</p>

                </div>

            </div>



        </div>


        {{-- DESCRIPCIÓN --}}

        <section class="company-public-profile__description">

            <h2 class="company-public-profile__section-title">
                Descripción de la empresa
            </h2>

            <p class="company-public-profile__description-text">
                {{ $company['description'] }}
            </p>

        </section>

    </section>


    {{-- PESTAÑAS --}}

    <div class="company-public-profile__tabs">

        <button
            type="button"
            class="company-public-profile__tab company-public-profile__tab--active"
            data-profile-tab="vacancies"
        >
            Vacantes disponibles
        </button>
        <button type="button" class="company-public-profile__tab" data-profile-tab="reviews" aria-selected="false">Valoraciones ({{ $reviews->count() }})</button>

    </div>


    {{--  VACANTES  --}}

    <section
        class="company-public-profile__vacancies"
        data-profile-section="vacancies"
    >

        @forelse ($vacancies as $vacancy)

            <x-vacancy-card
                :vacancy="$vacancy"
                :href="route('student.vacancies.show', [
                    'id' => $vacancy['id']
                ])"
            />

        @empty
            <p class="company-public-profile__empty">Esta empresa todavía no tiene vacantes publicadas.</p>
        @endforelse

    </section>

    <section class="company-public-profile__reviews-list" data-profile-section="reviews" hidden>
        <h2 class="company-public-profile__section-title">Valoraciones de estudiantes</h2>
        @forelse ($reviews as $review)
            <article class="company-public-profile__review-card">
                <p aria-label="{{ (int) $review->rating }} de 5 estrellas">{{ str_repeat('★', (int) $review->rating) }}{{ str_repeat('☆', max(0, 5 - (int) $review->rating)) }}</p>
                <h3>{{ $review->review_title ?: 'Valoración' }}</h3>
                @if ($review->job_title)<p>{{ $review->job_title }}</p>@endif
                <p>{{ $review->review_description }}</p>
                <small>{{ $review->created_at?->format('d/m/Y') ?? 'Sin fecha' }}</small>
            </article>
        @empty
            <p class="company-public-profile__empty">Esta empresa todavía no tiene valoraciones registradas.</p>
        @endforelse
    </section>


</main>

@endsection
