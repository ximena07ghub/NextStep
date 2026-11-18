<?php

namespace App\Http\Controllers;

use App\Models\Membership;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CompanyMembershipController extends Controller
{
    public function index(Request $request): mixed
    {
        $companyId = (string) $request->user('web')->getKey();
        $currentSubscription = Subscription::where('company_user_id', $companyId)
            ->where('payment_status', 'paid')->where('status', 'active')
            ->where('started_at', '<=', now())->where('ended_at', '>', now())->latest('started_at')->first();
        // solo mostramos compras que ya están confirmadas
        $orders = Subscription::where('company_user_id', $companyId)
            ->where('payment_status', 'paid')->latest()->paginate(10)->withQueryString();
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['current_subscription' => $currentSubscription?->summary($request->user('web')), 'orders' => $orders->through(fn ($order) => $order->summary($request->user('web')))]);
        }

        return view('company.membership', compact('currentSubscription', 'orders'));
    }

    public function catalog(): mixed
    {
        $memberships = Membership::where('active', true)->orderBy('priority')->get();

        return view('company.change-membership', compact('memberships'));
    }

    public function checkout(Request $request)
    {
        $data = $request->validate([
            'membership_id' => 'required|string|regex:/^[a-f0-9]{24}$/',
            'billing_email' => 'required|email|max:254',
        ]);

        $membership = Membership::where('active', true)->findOrFail($data['membership_id']);
        $secret = config('services.stripe.secret');

        if (! $secret) {
            return back()->withErrors(['payment' => 'Los pagos aún no están configurados.'])->withInput();
        }

        $companyId = (string) $request->user('web')->getKey();
        $amount = (int) round($membership->price * 100);

        if ($amount < 100) {
            return back()->withErrors(['payment' => 'Este plan no tiene un precio válido para el cobro.'])->withInput();
        }

        // stripe recibe el precio en centavos y devuelve una página de pago segura
        $response = Http::asForm()->withBasicAuth($secret, '')->timeout(15)
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => 'payment',
                'payment_method_types' => ['card'],
                'success_url' => route('company.membership').'?checkout=returned',
                'cancel_url' => route('company.membership.change').'?checkout=cancelled',
                'customer_email' => $data['billing_email'],
                'client_reference_id' => $companyId,
                'metadata' => [
                    'company_user_id' => $companyId,
                    'membership_id' => (string) $membership->getKey(),
                ],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'mxn',
                        'unit_amount' => $amount,
                        'product_data' => ['name' => $membership->name],
                    ],
                    'quantity' => 1,
                ]],
            ]);

        $url = $response->json('url');
        if (! $response->successful() || ! is_string($url) || ! str_starts_with($url, 'https://checkout.stripe.com/')) {
            return back()->withErrors(['payment' => 'No se pudo abrir el pago. Intenta nuevamente.'])->withInput();
        }

        return redirect()->away($url);
    }
}
