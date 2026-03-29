<?php
/**
 * PaymentController
 * Process payments via StripeAPI mock, record to DB.
 */
class PaymentController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /** Process a payment for a booking */
    public function processPayment(int $bookingId, float $amount): array
    {
        $stripe = new StripeAPI();
        $result = $stripe->createCharge($amount, 'tok_mock_' . time());

        $stmt = $this->db->prepare(
            "INSERT INTO Payments (booking_id, user_id, amount, payment_method, transaction_id, status)
             VALUES (?, ?, ?, 'credit_card', ?, ?)"
        );
        $stmt->execute([
            $bookingId,
            $_SESSION['user_id'],
            $amount,
            $result['transaction_id'],
            $result['success'] ? 'paid' : 'held'
        ]);

        return $result;
    }

    /** Request a refund */
    public function refund(int $paymentId): void
    {
        $stmt = $this->db->prepare("SELECT * FROM Payments WHERE payment_id = ?");
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch();

        if (!$payment) {
            jsonResponse(['success' => false, 'error' => 'Payment not found.'], 404);
        }

        $stripe = new StripeAPI();
        $result = $stripe->refund($payment['transaction_id']);

        if ($result['success']) {
            $stmt = $this->db->prepare("UPDATE Payments SET status = 'refunded' WHERE payment_id = ?");
            $stmt->execute([$paymentId]);
        }

        jsonResponse($result);
    }
}
