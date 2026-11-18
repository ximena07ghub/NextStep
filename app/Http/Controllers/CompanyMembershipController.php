<?php

namespace App\Http\Controllers;

use App\Models\Membership;
use App\Models\Subscription;
use Illuminate\Http\Request;

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

}
