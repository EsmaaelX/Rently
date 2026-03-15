<?php
/**
 * BookingController
 * Handles booking creation with Collision Detection and Hybrid Pricing.
 */
class BookingController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // ─── COLLISION DETECTION ──────────────────────────────────────────
    /**
     * Check if an asset is available for the requested time range.
     * Uses the overlap formula:
     *   (Requested_Start < Existing_End) AND (Requested_End > Existing_Start)
     *
     * @return bool true = available (no collision), false = conflict exists
     */
    public function isAvailable(int $assetId, string $startTime, string $endTime): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS conflicts
             FROM Bookings
             WHERE asset_id = ?
               AND status != 'cancelled'
               AND start_time < ?
               AND end_time   > ?"
        );
        // Note: start_time < requestedEnd AND end_time > requestedStart
        $stmt->execute([$assetId, $endTime, $startTime]);
        $row = $stmt->fetch();

        return (int) $row['conflicts'] === 0;
    }

    /**
     * AJAX endpoint: check availability and return JSON.
     * GET /index.php?page=booking&action=check&asset_id=X&start=ISO&end=ISO
     */
    public function checkAvailability(): void
    {
        $assetId = (int) ($_GET['asset_id'] ?? 0);
        $start   = $_GET['start'] ?? '';
        $end     = $_GET['end']   ?? '';

        if (!$assetId || empty($start) || empty($end)) {
            jsonResponse(['available' => false, 'error' => 'Missing parameters.'], 400);
        }

        $available = $this->isAvailable($assetId, $start, $end);
        jsonResponse([
            'available' => $available,
            'message'   => $available
                ? 'The asset is available for the selected dates.'
                : 'Sorry, this asset is already booked for the selected time range.'
        ]);
    }

    // ─── HYBRID PRICING ──────────────────────────────────────────────
    /**
     * Calculate total price based on asset category.
     *  - sport_venue → hours × price_per_hour
     *  - car / apartment → days × price_per_day
     */
    public function calculatePrice(array $asset, string $startTime, string $endTime): float
    {
        $start = new DateTime($startTime);
        $end   = new DateTime($endTime);
        $diff  = $start->diff($end);

        if ($asset['category'] === 'sport_venue') {
            // Calculate hours (including partial hours)
            $hours = ($end->getTimestamp() - $start->getTimestamp()) / 3600;
            $hours = max(ceil($hours), 1);
            return round($hours * (float) $asset['price_per_hour'], 2);
        } else {
            // car or apartment — calculate days
            $days = $diff->days;
            $days = max($days, 1); // minimum 1 day
            return round($days * (float) $asset['price_per_day'], 2);
        }
    }

    // ─── CREATE BOOKING ──────────────────────────────────────────────
    /**
     * Create a new booking (AJAX POST).
     * Steps: validate → collision check → price calc → insert → mock payment.
     */
    public function createBooking(): void
    {
        if (!isLoggedIn()) {
            jsonResponse(['success' => false, 'error' => 'Please log in first.'], 401);
        }

        $assetId = (int) ($_POST['asset_id'] ?? 0);
        $start   = $_POST['start_time'] ?? '';
        $end     = $_POST['end_time']   ?? '';

        if (!$assetId || empty($start) || empty($end)) {
            jsonResponse(['success' => false, 'error' => 'Missing booking details.'], 400);
        }

        // Validate dates
        if (strtotime($end) <= strtotime($start)) {
            jsonResponse(['success' => false, 'error' => 'End time must be after start time.'], 400);
        }

        // Fetch asset
        $stmt = $this->db->prepare("SELECT * FROM Assets WHERE asset_id = ? AND status = 'active'");
        $stmt->execute([$assetId]);
        $asset = $stmt->fetch();

        if (!$asset) {
            jsonResponse(['success' => false, 'error' => 'Asset not found or inactive.'], 404);
        }

        // ── Collision Detection ──
        if (!$this->isAvailable($assetId, $start, $end)) {
            jsonResponse([
                'success' => false,
                'error'   => 'This asset is already booked for the selected time range.'
            ], 409);
        }

        // ── Hybrid Pricing ──
        $totalPrice = $this->calculatePrice($asset, $start, $end);

        // ── Insert Booking ──
        $stmt = $this->db->prepare(
            "INSERT INTO Bookings (user_id, asset_id, start_time, end_time, total_price, status)
             VALUES (?, ?, ?, ?, ?, 'confirmed')"
        );
        $stmt->execute([$_SESSION['user_id'], $assetId, $start, $end, $totalPrice]);
        $bookingId = (int) $this->db->lastInsertId();

        // ── Mock Payment via Stripe ──
        $stripe = new StripeAPI();
        $paymentResult = $stripe->createCharge($totalPrice, 'tok_mock_' . time());

        // Record payment
        $stmt = $this->db->prepare(
            "INSERT INTO Payments (booking_id, user_id, amount, payment_method, transaction_id, status)
             VALUES (?, ?, ?, 'credit_card', ?, 'paid')"
        );
        $stmt->execute([
            $bookingId,
            $_SESSION['user_id'],
            $totalPrice,
            $paymentResult['transaction_id']
        ]);

        jsonResponse([
            'success'    => true,
            'booking_id' => $bookingId,
            'total_price'=> $totalPrice,
            'message'    => "Booking confirmed! Total: $$totalPrice"
        ]);
    }
}
