<?php

namespace App\Http\Controllers;

use App\Models\Membership;
use App\Models\Subscription;
use App\Services\PayPalService;
use Illuminate\Http\Request;
use RuntimeException;

class CompanyMembershipController extends Controller
{
    public function index(Request $request): mixed
    {
        $companyId = (string) $request->user('web')->getKey();
        $currentSubscription = Subscription::where('company_user_id', $companyId)
            ->where('payment_status', 'paid')->where('status', 'active')
            ->where('started_at', '<=', now())->where('ended_at', '>', now())->latest('started_at')->first();

        $orders = Subscription::where('company_user_id', $companyId)
            ->where('payment_status', 'paid')->latest()->paginate(10)->withQueryString();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'current_subscription' => $currentSubscription?->summary($request->user('web')),
                'orders' => $orders->through(fn ($order) => $order->summary($request->user('web'))),
            ]);
        }

        return view('company.membership', compact('currentSubscription', 'orders'));
    }

    public function catalog(): mixed
    {
        $memberships = Membership::where('active', true)->orderBy('priority')->get();

        return view('company.change-membership', compact('memberships'));
    }

    public function checkout(Request $request, PayPalService $paypal)
    {
        $data = $request->validate([
            'membership_id' => 'required|string|regex:/^[a-f0-9]{24}$/',
            'billing_email' => 'required|email|max:254',
        ]);

        $membership = Membership::where('active', true)->findOrFail($data['membership_id']);
        $amount = number_format((float) $membership->price, 2, '.', '');
        if ((float) $amount < 1) {
            return back()->withErrors(['payment' => 'El precio del plan no es válido.'])->withInput();
        }

        $companyId = (string) $request->user('web')->getKey();

        try {
            $order = $paypal->createOrder(
                $companyId,
                (string) $membership->getKey(),
                $amount,
                route('company.membership.paypal.return'),
                route('company.membership.change').'?checkout=cancelled'
            );
        } catch (RuntimeException $e) {
            report($e);
            return back()->withErrors(['payment' => 'No se pudo iniciar el pago con PayPal.'])->withInput();
        }

        // se guarda el precio original antes de que el comprador apruebe el pago
        Subscription::create([
            'company_user_id' => $companyId,
            'membership_id' => (string) $membership->getKey(),
            'status' => 'pending',
            'payment_status' => 'pending',
            'reference' => $order['id'],
            'amount' => (float) $amount,
            'currency' => 'MXN',
            'billing_email' => $data['billing_email'],
            'membership_name' => $membership->name,
            'membership_code' => $membership->code,
            'membership_benefits' => $membership->benefits ?? [],
            'membership_vacancy_limit' => $membership->vacancy_limit,
            'membership_priority' => $membership->priority,
            'payment_method' => 'paypal',
        ]);

        return redirect()->away($order['url']);
    }

    public function paypalReturn(Request $request, PayPalService $paypal)
    {
        $data = $request->validate(['token' => ['required', 'string', 'regex:/^[A-Za-z0-9-]{8,80}$/']]);
        $companyId = (string) $request->user('web')->getKey();

        $purchase = Subscription::where('company_user_id', $companyId)
            ->where('reference', $data['token'])->where('payment_method', 'paypal')->first();

        if (! $purchase) {
            abort(404);
        }

        if ($purchase->payment_status === 'paid') {
            return redirect()->route('company.membership')->with('success', 'Tu membresía ya está activa.');
        }

        if ($purchase->payment_status !== 'pending') {
            return redirect()->route('company.membership.change')->withErrors(['payment' => 'La orden no está pendiente.']);
        }

        try {
            // Consultar primero: el comprador pudo regresar tras una captura ya completada.
            $order = $paypal->getOrder($data['token']);
            if (($order['status'] ?? null) === 'APPROVED') {
                $paypal->captureOrder($data['token']);
                // GET contiene los datos completos que validamos abajo.
                $order = $paypal->getOrder($data['token']);
            }
        } catch (RuntimeException $e) {
            report($e);
            return redirect()->route('company.membership.change')->withErrors(['payment' => 'No se pudo confirmar el pago. Intenta regresar desde PayPal.']);
        }

        $unit = $order['purchase_units'][0] ?? [];
        $capture = $unit['payments']['captures'][0] ?? [];
        $amount = $capture['amount'] ?? [];
        $valid = ($order['id'] ?? null) === $purchase->reference
            && ($order['status'] ?? null) === 'COMPLETED'
            && ($unit['custom_id'] ?? null) === $companyId
            && ($unit['reference_id'] ?? null) === (string) $purchase->membership_id
            && ($capture['status'] ?? null) === 'COMPLETED'
            && ($amount['currency_code'] ?? null) === 'MXN'
            && is_numeric($amount['value'] ?? null)
            && (int) round((float) $amount['value'] * 100) === (int) round((float) $purchase->amount * 100)
            && is_string($capture['id'] ?? null);

        if (! $valid) {
            return redirect()->route('company.membership.change')->withErrors(['payment' => 'El pago todavía no está confirmado o sus datos no coinciden.']);
        }

        $start = now();
        // el filtro pending impide activar dos veces la misma compra
        Subscription::where('_id', $purchase->getKey())->where('payment_status', 'pending')->update([
            'payment_status' => 'paid',
            'status' => 'active',
            'started_at' => $start,
            'ended_at' => $start->copy()->addMonthNoOverflow(),
            'paid_at' => $start,
        ]);

        return redirect()->route('company.membership')->with('success', 'Pago confirmado. Tu membresía está activa por un mes.');
    }
}
