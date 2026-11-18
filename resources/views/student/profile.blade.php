@extends('layouts.student')

@section('content')

<div
    class="student-profile-page"
    data-student-profile-page
>

    {{-- ------- perfil del estudiante ------- --}}

    <x-student.profile-card :student="$student" />


    {{-- ------- vacantes y postulaciones ------- --}}

    <section class="student-profile-page__applications">

        <div class="student-profile-page__heading">
            <h1 class="student-profile-page__title">Mis postulaciones</h1>
            <a class="student-profile-page__bookmarks" href="{{ route('student.bookmarks') }}">
                Ver vacantes guardadas
            </a>
        </div>

        {{-- ------- postulaciones enviadas ------- --}}

        <div class="student-profile-page__application-grid">

            @forelse ($applications as $application)

                <div class="student-profile-page__application">
                    @if(($application['isAvailable'] ?? false) && !empty($application['id']))
                        <x-vacancy-card
                            :vacancy="$application"
                            :href="route('student.vacancies.show', ['id' => $application['id']])"
                        />
                    @else
                        <div class="student-profile-page__unavailable">
                            <strong>{{ $application['title'] ?? 'Vacante no disponible' }}</strong>
                            <span>Esta vacante ya no está publicada.</span>
                        </div>
                    @endif
                </div>

            @empty

                <p class="student-profile-page__empty">
                    Aún no has enviado postulaciones.
                </p>

            @endforelse

        </div>

    </section>

</div>

@endsection