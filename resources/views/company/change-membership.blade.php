@extends('layouts.company')

@section('content')
<section class="change-membership-page">
    <div class="ns-catalog">
        <a class="ns-catalog__back" href="{{ route('company.membership') }}">← volver a tu suscripción</a>

        <header class="ns-catalog__header">
            <h1>Elige tu plan</h1>
            <p>Compara las opciones disponibles. Los precios están en pesos mexicanos por mes.</p>
        </header>

        @if ($errors->any())
            <div class="admin-errors" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        @if (request('checkout') === 'cancelled')
            <p class="ns-catalog__notice" role="status">Cancelaste el pago. No se realizó ninguna compra.</p>
        @endif
        <form method="POST" action="{{ route('company.membership.checkout') }}">
            @csrf
        <fieldset class="ns-catalog__fieldset">
            <legend class="ns-catalog__legend">Planes disponibles</legend>
            <div class="ns-catalog__plans">
                @forelse ($memberships as $membership)
                    <label class="ns-catalog__plan">
                        <span class="ns-catalog__choice">
                            <input type="radio" name="membership_id" value="{{ $membership->getKey() }}" required @checked(old('membership_id') === (string) $membership->getKey())>
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

        <div class="ns-catalog__checkout">
            <label for="billing-email">Correo para el comprobante</label>
            <input id="billing-email" name="billing_email" type="email" required maxlength="254" value="{{ old('billing_email', auth('web')->user()->email) }}">
            <p>El pago único se procesa con PayPal. No hay renovación automática. La membresía se activa cuando PayPal confirma el cobro.</p>
            <button class="admin-button admin-button--primary" type="submit">Pagar con PayPal</button>
        </div>
        </form>
    </div>
</section>
@endsection
