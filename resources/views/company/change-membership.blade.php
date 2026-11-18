@extends('layouts.company')

@section('content')
<section class="change-membership-page">
    <div class="ns-catalog">
        <a class="ns-catalog__back" href="{{ route('company.membership') }}">← volver a tu suscripción</a>

        <header class="ns-catalog__header">
            <h1>Elige tu plan</h1>
            <p>Compara las opciones disponibles. Los precios están en pesos mexicanos por mes.</p>
        </header>

        <fieldset class="ns-catalog__fieldset">
            <legend class="ns-catalog__legend">Planes disponibles</legend>
            <div class="ns-catalog__plans">
                @forelse ($memberships as $membership)
                    <label class="ns-catalog__plan">
                        <span class="ns-catalog__choice">
                            <input type="radio" name="membership_id" value="{{ $membership->getKey() }}">
                            seleccionar
                        </span>
                        <strong class="ns-catalog__name">{{ $membership->name }}</strong>
                        <span class="ns-catalog__price">${{ number_format($membership->price, 2) }} <small>/ mes</small></span>
                        <span class="ns-catalog__limit">{{ $membership->vacancy_limit === null ? 'vacantes ilimitadas' : $membership->vacancy_limit . ' vacantes' }}</span>
                        <ul class="ns-catalog__benefits">
                            @foreach (($membership->benefits ?? []) as $benefit)
                                <li>{{ $benefit }}</li>
                            @endforeach
                        </ul>
                    </label>
                @empty
                    <p class="ns-catalog__empty">No hay planes disponibles en este momento.</p>
                @endforelse
            </div>
        </fieldset>

        <p class="ns-catalog__notice" role="status">
            La contratación en línea aún no está habilitada. Aquí puedes comparar los planes,
            pero no se registrará una compra ni se realizará un cobro.
        </p>
    </div>
</section>
@endsection
