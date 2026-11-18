@extends('layouts.company')

@section('content')
<section class="membership-page">
    <header class="membership-page__header">
        <h1>Tu suscripción</h1>
        <p>Consulta tu membresía activa y las compras confirmadas.</p>
    </header>

    @if (request('checkout') === 'returned')
        <p class="ns-catalog__notice" role="status">Regresaste del proceso de pago. Tu membresía aparecerá cuando el proveedor confirme el cobro.</p>
    @endif
    <div class="ns-membership-content membership-page__content">
        @if ($currentSubscription)
            <div class="membership-page__current">
                <div class="membership-page__current-info">
                    <h2>{{ $currentSubscription->membership_name ?? 'Membresía activa' }}</h2>
                    <p>${{ number_format($currentSubscription->amount, 2) }} {{ $currentSubscription->currency ?? 'MXN' }}</p>
                    <p>Vigente hasta {{ $currentSubscription->ended_at->format('d/m/Y') }}</p>
                </div>
            </div>

            <div class="membership-benefits membership-benefits--plus">
                <div class="membership-benefits__header">
                    <h2>Beneficios de tu membresía</h2>
                    <a class="membership-benefits__change" href="{{ route('company.membership.change') }}">Ver planes</a>
                </div>
                <ul class="membership-benefits__list">
                    @forelse (($currentSubscription->membership_benefits ?? []) as $benefit)
                        <li class="membership-benefits__item"><span>{{ $benefit }}</span></li>
                    @empty
                        <li class="membership-benefits__item"><span>Consulta los detalles de tu plan en el catálogo.</span></li>
                    @endforelse
                </ul>
            </div>
        @else
            <p class="admin-note">Actualmente no tienes una membresía activa.</p>
            <a class="admin-button admin-button--primary" href="{{ route('company.membership.change') }}">Ver planes</a>
        @endif

        <section class="ns-order-history">
            <h2>Compras confirmadas</h2>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr><th>Plan</th><th>Importe</th><th>Fecha de compra</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr>
                                <td>{{ $order->membership_name ?? 'Plan sin nombre' }}</td>
                                <td>${{ number_format($order->amount, 2) }} {{ $order->currency ?? 'MXN' }}</td>
                                <td>{{ $order->paid_at?->format('d/m/Y') ?? $order->created_at?->format('d/m/Y') ?? 'Sin fecha' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="admin-empty">Todavía no tienes compras confirmadas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('components.admin.pagination', ['records' => $orders])
        </section>
    </div>
</section>
@endsection
