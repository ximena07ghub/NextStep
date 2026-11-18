<aside class="company-sidebar">

    <nav class="company-sidebar__menu" aria-label="Secciones de empresa">


        {{-- DASHBOARD --}}

        <a
            href="{{ route('company.home') }}"
            @if(request()->routeIs('company.home')) aria-current="page" @endif
            class="
                company-sidebar__item
                {{ request()->routeIs('company.home')
                    ? 'company-sidebar__item--active'
                    : '' }}
            "
        >

            <img
                src="{{ asset('images/Grid.svg') }}"
                alt=""
                class="company-sidebar__icon"
            >

            <span>
                Dashboard
            </span>

        </a>


        {{-- VACANTES --}}

        <a
            href="{{ route('company.vacancies.index') }}"
            @if(request()->routeIs('company.vacancies.*')) aria-current="page" @endif
            class="
                company-sidebar__item
                {{ request()->routeIs('company.vacancies.*')
                    ? 'company-sidebar__item--active'
                    : '' }}
            "
        >

            <img
                src="{{ asset('images/Filedash.svg') }}"
                alt=""
                class="company-sidebar__icon"
            >

            <span>
                Vacantes
            </span>

        </a>


        {{-- SOLICITUDES --}}

        <a
            href="{{ route('company.applications') }}"
            @if(request()->routeIs('company.applications*')) aria-current="page" @endif
            class="
                company-sidebar__item
                {{ request()->routeIs('company.applications*')
                    ? 'company-sidebar__item--active'
                    : '' }}
            "
        >

            <img
                src="{{ asset('images/Smile.svg') }}"
                alt=""
                class="company-sidebar__icon"
            >

            <span>
                Solicitudes
            </span>

        </a>


        {{-- CREAR VACANTE --}}
        <a href="{{ route('company.vacancies.create') }}" class="company-sidebar__item {{ request()->routeIs('company.vacancies.create') ? 'company-sidebar__item--active' : '' }}" @if(request()->routeIs('company.vacancies.create')) aria-current="page" @endif>
            <img src="{{ asset('images/Filedash.svg') }}" alt="" class="company-sidebar__icon">
            <span>Publicar vacante</span>
        </a>

        {{-- VALORACIONES --}}

        <a
            href="{{ route('company.ratings') }}"
            @if(request()->routeIs('company.ratings')) aria-current="page" @endif
            class="
                company-sidebar__item
                {{ request()->routeIs('company.ratings')
                    ? 'company-sidebar__item--active'
                    : '' }}
            "
        >

            <img
                src="{{ asset('images/Stardash.svg') }}"
                alt=""
                class="company-sidebar__icon"
            >

            <span>
                Valoraciones
            </span>

        </a>

    </nav>

</aside>