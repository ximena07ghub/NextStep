@extends('layouts.student')

@section('content')

<main
    class="vacancy-detail-page"
    data-student-vacancy-detail
    data-bookmark-icon="{{ asset('images/Bookmark.svg') }}"
    data-bookmark-filled-icon="{{ asset('images/BookmarkFilled.svg') }}"
    data-open-on-error="{{ $errors->any() ? 'true' : 'false' }}"
>
    <p class="flow-feedback flow-feedback--error" data-bookmark-feedback hidden></p>
    @include('components.flow-feedback')


    <x-vacancy-detail
        :vacancy="$vacancy"
        :actions-at-end="true"
        :company-href="route('student.companies.show', [
            'id' => $vacancy['companyId']
        ])"
    >

        <x-slot:actions>

            <x-student.bookmark-form :id="$vacancy['id']" :saved="$saved" variant="vacancy-detail" />

            {{-- POSTULARSE --}}

            <button
                type="button"
                class="vacancy-detail__primary-button"
                data-application-open
                @if($alreadyApplied) hidden @endif
            >
                Postularse
            </button>
            @if($alreadyApplied)
                @php
                    $status = in_array($applicationStatus, ['pending', 'accepted', 'rejected']) ? $applicationStatus : 'pending';
                    $statusLabel = ['pending' => 'Pendiente', 'accepted' => 'Aceptada', 'rejected' => 'Rechazada'][$status];
                @endphp
                <span class="vacancy-detail__application-status vacancy-detail__application-status--{{ $status }}" role="status">
                    {{ $statusLabel }}
                </span>
            @endif

        </x-slot:actions>

    </x-vacancy-detail>


    @include('student.partials.application-modal', ['vacancy' => $vacancy, 'student' => $student])

</main>

@endsection
