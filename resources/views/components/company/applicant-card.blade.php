<a
    href="{{ route('company.applications.show', ['id' => $application['id']]) }}"
    class="applicant-card"
    data-application-card
    data-vacancy-id="{{ $application['vacancyId'] }}"
>

    <div class="applicant-card__content">

        <h3 class="applicant-card__vacancy-title">
            {{ $application['name'] }}
        </h3>

        <p class="applicant-card__applicant">

            <span>
                Vacante:
            </span>

            <span class="applicant-card__applicant-name">
                {{ $application['vacancyTitle'] }}
            </span>

        </p>

    </div>

</a>