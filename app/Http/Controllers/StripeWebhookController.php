<?php

namespace App\Http\Controllers;

use App\Models\Membership;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $secret = config('services.stripe.webhook_secret');
        $header = $request->header('Stripe-Signature', '');
        $body = $request->getContent();

        if (! $secret || ! $this->validSignature($body, $header, $secret)) {
            return response()->json(['message' => 'Firma no válida'], 400);
        }

        $event = json_decode($body, true);
        if (! is_array($event) || ($event['type'] ?? '') !== 'checkout.session.completed') {
            return response()->json(['received' => true]);
        }

        $session = $event['data']['object'] ?? [];
        $companyId = $session['metadata']['company_user_id'] ?? null;
        $membershipId = $session['metadata']['membership_id'] ?? null;
        $reference = $session['id'] ?? null;

        // solo un cobro confirmado puede activar la membresía
        if (($session['payment_status'] ?? '') !== 'paid'
            || ($session['mode'] ?? '') !== 'payment'
            || ($session['currency'] ?? '') !== 'mxn'
            || ! is_string($reference) || ! str_starts_with($reference, 'cs_')
            || ! is_string($companyId) || ! is_string($membershipId)
            || ($session['client_reference_id'] ?? null) !== $companyId) {
            return response()->json(['received' => true]);
        }

        if (Subscription::where('reference', $reference)->exists()) {
            return response()->json(['received' => true]);
        }

        $company = User::where('role', 'company')->find($companyId);
        $membership = Membership::find($membershipId);
        if (! $company || ! $membership) {
            return response()->json(['received' => true]);
        }

        $amount = (int) ($session['amount_total'] ?? 0);
        if ($amount < 100 || $amount !== (int) round($membership->price * 100)) {
            return response()->json(['message' => 'Importe no coincide'], 400);
        }

        $start = now();
        $end = $start->copy()->addMonthNoOverflow();

        Subscription::create([
            'company_user_id' => $companyId,
            'membership_id' => $membershipId,
            'status' => 'active',
            'started_at' => $start,
            'ended_at' => $end,
            'amount' => $amount / 100,
            'currency' => 'MXN',
            'billing_email' => $session['customer_details']['email'] ?? $company->email,
            'payment_status' => 'paid',
            'reference' => $reference,
            'membership_name' => $membership->name,
            'membership_code' => $membership->code,
            'membership_benefits' => $membership->benefits ?? [],
            'membership_vacancy_limit' => $membership->vacancy_limit,
            'membership_priority' => $membership->priority,
            'payment_method' => 'stripe_checkout',
            'paid_at' => $start,
        ]);

        return response()->json(['received' => true]);
    }

    private function validSignature(string $body, string $header, string $secret): bool
    {
        $timestamp = null;
        $signatures = [];
        foreach (explode(',', $header) as $part) {
            $pieces = explode('=', trim($part), 2);
            if (count($pieces) !== 2) {
                continue;
            }
            if ($pieces[0] === 't') {
                $timestamp = $pieces[1];
            } elseif ($pieces[0] === 'v1') {
                $signatures[] = $pieces[1];
            }
        }

        if (! $timestamp || ! ctype_digit($timestamp) || abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$body, $secret);
        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }
}
