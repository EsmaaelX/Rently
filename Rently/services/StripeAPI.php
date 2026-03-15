<?php
/**
 * StripeAPI Mock
 * Placeholder for Stripe payment processing.
 * Replace the mock logic with the real Stripe PHP SDK when ready.
 *
 * Usage:
 *   $stripe = new StripeAPI();
 *   $result = $stripe->createCharge(99.99, 'tok_xxx');
 */
class StripeAPI
{
    // ──────────────────────────────────────────
    // DROP YOUR REAL STRIPE SECRET KEY HERE:
    private string $secretKey = 'sk_test_XXXXXXXXXXXXXXXXXXXXXXXX';
    // ──────────────────────────────────────────

    /**
     * Create a charge (mock).
     * @param float  $amount  Amount in USD
     * @param string $token   Payment token from client
     * @return array Result with success flag, transaction_id, message
     */
    public function createCharge(float $amount, string $token): array
    {
        // ── MOCK IMPLEMENTATION ──
        // In production, replace with:
        // \Stripe\Stripe::setApiKey($this->secretKey);
        // $charge = \Stripe\Charge::create([...]);

        return [
            'success'        => true,
            'transaction_id' => 'txn_mock_' . bin2hex(random_bytes(8)),
            'amount'         => $amount,
            'currency'       => 'USD',
            'message'        => 'Payment processed successfully (mock).',
        ];
    }

    /**
     * Refund a charge (mock).
     * @param string $transactionId Original transaction ID
     * @return array
     */
    public function refund(string $transactionId): array
    {
        // ── MOCK IMPLEMENTATION ──
        return [
            'success'   => true,
            'refund_id' => 'ref_mock_' . bin2hex(random_bytes(8)),
            'message'   => 'Refund processed successfully (mock).',
        ];
    }

    /**
     * Create a payment intent (mock) — for Stripe Elements integration.
     */
    public function createPaymentIntent(float $amount): array
    {
        return [
            'success'       => true,
            'client_secret' => 'pi_mock_secret_' . bin2hex(random_bytes(12)),
            'amount'        => $amount,
        ];
    }
}
