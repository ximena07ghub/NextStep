@extends('layouts.company-dashboard')

@section('dashboard-content')
<section class="company-ratings">
    <header class="company-ratings__header">
        <h1 class="company-ratings__title">Valoraciones</h1>
        <p class="company-ratings__description">Consulta las experiencias que los estudiantes han compartido sobre tu empresa.</p>
    </header>

    <div class="company-ratings__company">
        <div class="company-ratings__company-info">
            <h2>{{ $company->company_name }}</h2>
            <div class="company-ratings__company-rating">
                @if ($average !== null)
                    <span>{{ number_format($average, 1) }} de 5</span>
                    <img src="{{ asset('images/Star.svg') }}" alt="">
                @else
                    <span>Sin calificaciones todavía</span>
                @endif
            </div>
        </div>
    </div>

    <div class="company-ratings__reviews">
        <div class="company-ratings__reviews-header">
            <h2>{{ $total }} {{ $total === 1 ? 'valoración' : 'valoraciones' }}</h2>
            <form method="GET" action="{{ route('company.ratings') }}">
                <label for="ratings-sort">Ordenar por</label>
                <select id="ratings-sort" name="sort" onchange="this.form.submit()">
                    <option value="recent" @selected($sort === 'recent')>Más recientes</option>
                    <option value="oldest" @selected($sort === 'oldest')>Más antiguas</option>
                </select>
                <button type="submit" class="admin-button">Ordenar</button>
            </form>
        </div>

        <div class="company-ratings__review-list">
            @forelse ($reviews as $review)
                <article class="company-ratings__review">
                    <div class="company-ratings__review-rating">
                        <span aria-label="{{ $review->rating }} de 5 estrellas">{{ str_repeat('★', (int) $review->rating) }}{{ str_repeat('☆', 5 - (int) $review->rating) }}</span>
                        <time class="company-ratings__review-date" datetime="{{ $review->created_at?->format('Y-m-d') }}">
                            {{ $review->created_at?->format('d/m/Y') ?? 'Sin fecha' }}
                        </time>
                    </div>
                    <p class="company-ratings__review-comment">{{ $review->review_title ?: 'Valoración sin título' }}</p>
                </article>
            @empty
                <p class="company-ratings__empty">Aún no hay valoraciones registradas para tu empresa.</p>
            @endforelse
        </div>
        {{ $reviews->links() }}
    </div>
</section>
@endsection
