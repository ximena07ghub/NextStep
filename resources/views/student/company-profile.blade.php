@extends('layouts.student')

@section('content')

<main
    class="company-public-profile"
    data-company-public-profile
>




    {{-- encabezado --}}

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


        {{-- descripción --}}

        <section class="company-public-profile__description">

            <h2 class="company-public-profile__section-title">
                Descripción de la empresa
            </h2>

            <p class="company-public-profile__description-text">
                {{ $company['description'] }}
            </p>

        </section>

    </section>


    {{-- pestañas --}}

    <div class="company-public-profile__tabs">

        <button
            type="button"
            class="company-public-profile__tab {{ $errors->any() || session("success") ? "" : "company-public-profile__tab--active" }}"
            data-profile-tab="vacancies" aria-selected="{{ $errors->any() || session("success") ? "false" : "true" }}"
        >
            Vacantes disponibles
        </button>
        <button type="button" class="company-public-profile__tab {{ $errors->any() || session("success") ? "company-public-profile__tab--active" : "" }}" data-profile-tab="reviews" aria-selected="{{ $errors->any() || session("success") ? "true" : "false" }}">Valoraciones ({{ $reviews->count() }})</button>

    </div>


    {{-- vacantes --}}

    <section
        class="company-public-profile__vacancies"
        data-profile-section="vacancies" @if ($errors->any() || session("success")) hidden @endif
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

    <section class="company-public-profile__reviews-list" data-profile-section="reviews" @if (!$errors->any() && !session("success")) hidden @endif>
        <h2 class="company-public-profile__section-title">Valoraciones de estudiantes</h2>
        @if (session('success'))<p role="status">{{ session('success') }}</p>@endif
        @if ($errors->any())
            <div role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        @if (!$alreadyReviewed)
            <form class="company-public-profile__review-form" method="POST" action="{{ route('student.companies.reviews.store', $company['id']) }}">
                @csrf
                <h3>Calificar esta empresa</h3>
                <p>Comparte una experiencia real de manera respetuosa.</p>
                <label for="company-rating">Calificación</label>
                <select id="company-rating" name="rating" required>
                    <option value="">Selecciona de 1 a 5 estrellas</option>
                    @for ($score = 1; $score <= 5; $score++)
                        <option value="{{ $score }}" @selected(old('rating') == $score)>{{ $score }} {{ $score == 1 ? 'estrella' : 'estrellas' }}</option>
                    @endfor
                </select>
                <label for="review-description">Tu experiencia</label>
                <textarea id="review-description" name="review_description" rows="5" minlength="10" maxlength="1500" required>{{ old('review_description') }}</textarea>
                <p>Escribe al menos 10 caracteres sobre tu experiencia.</p>
                <button type="submit">Publicar valoración</button>
            </form>
        @else
            <p role="status">Ya publicaste una valoración para esta empresa.</p>
        @endif
        @forelse ($reviews as $review)
            <article class="company-public-profile__review-card">
                <p aria-label="{{ (int) $review->rating }} de 5 estrellas">{{ str_repeat('★', (int) $review->rating) }}{{ str_repeat('☆', max(0, 5 - (int) $review->rating)) }}</p>
                <p>{{ $review->review_description }}</p>
                <small>{{ $review->created_at?->format('d/m/Y') ?? 'Sin fecha' }}</small>
            </article>
        @empty
            <p class="company-public-profile__empty">Esta empresa todavía no tiene valoraciones registradas.</p>
        @endforelse
    </section>


</main>

@endsection
