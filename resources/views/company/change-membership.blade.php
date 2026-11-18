@extends('layouts.company')

@section('content')
<section class="change-membership-page">
    <div class="ns-catalog">
        <a class="ns-catalog__back" href="{{ route('company.membership') }}">← volver a tu suscripción</a>

        <header class="ns-catalog__header">
            <h1>Escoge tu suscripción</h1>
            <p>Compara los planes disponibles. Los precios están en pesos mexicanos por mes.</p>
        </header>

        @if ($errors->any())
            <div class="admin-errors" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('company.membership.orders.store') }}">
            @csrf

            <fieldset class="ns-catalog__fieldset">
                <legend class="ns-catalog__legend">Selecciona un plan</legend>
                <div class="ns-catalog__plans">
                    @forelse ($memberships as $membership)
                        <label class="ns-catalog__plan">
                            <span class="ns-catalog__choice">
                                <input type="radio" name="membership_id"
                                    value="{{ $membership->getKey() }}" required
                                    @checked(old('membership_id') === (string) $membership->getKey())>
                                seleccionar plan
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
                        <p class="ns-catalog__empty">No hay membresías disponibles en este momento.</p>
                    @endforelse
                </div>
            </fieldset>

            @if ($memberships->isNotEmpty())
                <div class="ns-catalog__checkout">
                    <label for="billing-email">Correo para la compra</label>
                    <input id="billing-email" type="email" name="billing_email"
                        value="{{ old('billing_email', auth('web')->user()->email) }}"
                        maxlength="255" autocomplete="email" required>
                    <p>Tu solicitud quedará con pago pendiente. No se realizará un cobro en esta pantalla.</p>
                    <button class="admin-button admin-button--primary" type="submit">Seleccionar plan</button>
                </div>
            @endif
        </form>
    </div>
</section>
@endsection
