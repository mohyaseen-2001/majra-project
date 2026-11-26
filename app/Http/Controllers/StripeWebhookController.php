<?php

namespace App\Http\Controllers;

use App\Services\StripeCheckoutService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeCheckoutService $checkout): Response
    {
        $secret = (string) config('services.stripe.webhook_secret');
        $signature = (string) $request->header('Stripe-Signature');
        abort_unless(str_starts_with((string) config('services.stripe.secret'), 'sk_test_') && str_starts_with($secret, 'whsec_'), 503);

        $parts = collect(explode(',', $signature))->mapWithKeys(function ($part) {
            [$key, $value] = array_pad(explode('=', $part, 2), 2, null);
            return [$key => $value];
        });
        $timestamp = (int) $parts->get('t');
        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);
        abort_unless($timestamp > 0 && abs(time() - $timestamp) <= 300 && hash_equals($expected, (string) $parts->get('v1')), 400);

        $event = $request->json()->all();
        if (($event['type'] ?? null) === 'checkout.session.completed') {
            $checkout->completePaidSession($event['data']['object'] ?? []);
        }

        return response()->noContent();
    }
}
