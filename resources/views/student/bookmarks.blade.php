@extends('layouts.student')

@section('content')
<div class="student-bookmarks">
    <div class="student-bookmarks__heading">
        <h1 class="student-bookmarks__title">Mis vacantes guardadas</h1>
        <a class="student-bookmarks__back" href="{{ route('student.home') }}">
            Volver a buscar vacantes
        </a>
    </div>

    @if (session('success'))
        <p class="flow-feedback flow-feedback--success" role="status">{{ session('success') }}</p>
    @endif

    <div class="student-bookmarks__grid">
        @forelse ($savedVacancies as $vacancy)
            <article class="student-bookmarks__item">
                <x-vacancy-card
                    :vacancy="$vacancy"
                    :href="route('student.vacancies.show', ['id' => $vacancy['id']])"
                />

                <form method="POST" action="{{ route('student.bookmarks.destroy', ['id' => $vacancy['id']]) }}">
                    @csrf
                    @method('DELETE')
                    <button class="student-bookmarks__remove" type="submit">
                        Quitar de guardadas
                    </button>
                </form>
            </article>
        @empty
            <p class="student-bookmarks__empty">
                Aún no tienes vacantes guardadas. Explora las oportunidades y guarda las que te interesen.
            </p>
        @endforelse
    </div>
</div>
@endsection
