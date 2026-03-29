<?php
/**
 * BookingController
 * Handles booking creation with Collision Detection, Hybrid Pricing,
 * DB Transactions, and Cancellation Policy.
 */
class BookingController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // ─── COLLISION DETECTION ──────────────────────────────────

    /**
     * Check if an asset is available for the requested time range.
     */
    public function isAvailable(int $assetId, string $startTime, string $endTime): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS conflicts
             FROM bookings
             WHERE asset_id = ?
               AND status != 'cancelled'
               AND start_time < ?
               AND end_time   > ?"
        );
        $stmt->execute([$assetId, $endTime, $startTime]);
        return (int) $stmt->fetch()['conflicts'] === 0;
    }

    /**
     * AJAX endpoint: check availability.
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
                ? t('asset_available')
                : t('asset_not_available')
        ]);
    }

    // ─── HYBRID PRICING ──────────────────────────────────────

    /**
     * Calculate total price based on asset category.
     */
    public function calculatePrice(array $asset, string $startTime, string $endTime): float
    {
        $start = new DateTime($startTime);
        $end   = new DateTime($endTime);
        $diff  = $start->diff($end);

        $hourlyCategories = ['sport_venue', 'studio', 'parking'];

        if (in_array($asset['category'], $hourlyCategories) && (float) $asset['price_per_hour'] > 0) {
            $hours = ($end->getTimestamp() - $start->getTimestamp()) / 3600;
            $hours = max(ceil($hours), 1);
            return round($hours * (float) $asset['price_per_hour'], 2);
        } else {
            $days = $diff->days;
            $days = max($days, 1);
            return round($days * (float) $asset['price_per_day'], 2);
        }
    }

    // ─── CREATE BOOKING (with DB Transaction) ─────────────────

    /**
     * Create a new booking using DB Transaction for safety.
     */
    public function createBooking(): void
    {
        if (!isLoggedIn()) {
            jsonResponse(['success' => false, 'error' => t('login_required')], 401);
        }

        if (isAdmin()) {
            jsonResponse(['success' => false, 'error' => t('admin_cannot_book')], 403);
        }

        $assetId = (int) ($_POST['asset_id'] ?? 0);
        $start   = $_POST['start_time'] ?? '';
        $end     = $_POST['end_time']   ?? '';

        if (!$assetId || empty($start) || empty($end)) {
            jsonResponse(['success' => false, 'error' => t('missing_booking_details')], 400);
        }

        if (strtotime($end) <= strtotime($start)) {
            jsonResponse(['success' => false, 'error' => t('end_after_start')], 400);
        }

        // Begin transaction
        $this->db->beginTransaction();

        try {
            // Fetch asset (with row lock using FOR UPDATE)
            $stmt = $this->db->prepare(
                "SELECT * FROM assets WHERE asset_id = ? AND status = 'active' AND is_approved = 1 FOR UPDATE"
            );
            $stmt->execute([$assetId]);
            $asset = $stmt->fetch();

            if (!$asset) {
                $this->db->rollBack();
                jsonResponse(['success' => false, 'error' => t('asset_not_available')], 404);
            }

            // Prevent owner from booking their own asset
            if ($asset['owner_id'] == $_SESSION['user_id']) {
                $this->db->rollBack();
                jsonResponse(['success' => false, 'error' => t('cannot_book_own_asset')], 403);
            }

            // Collision Detection (within transaction)
            if (!$this->isAvailable($assetId, $start, $end)) {
                $this->db->rollBack();
                jsonResponse([
                    'success' => false,
                    'error'   => t('asset_already_booked')
                ], 409);
            }

            // Calculate price
            $totalPrice = $this->calculatePrice($asset, $start, $end);

            // Insert Booking
            $stmt = $this->db->prepare(
                "INSERT INTO bookings (user_id, asset_id, start_time, end_time, total_price, status)
                 VALUES (?, ?, ?, ?, ?, 'confirmed')"
            );
            $stmt->execute([$_SESSION['user_id'], $assetId, $start, $end, $totalPrice]);
            $bookingId = (int) $this->db->lastInsertId();

            // Mock Payment
            $stripe = new StripeAPI();
            $paymentResult = $stripe->createCharge($totalPrice, 'tok_mock_' . time());

            // Record payment
            $stmt = $this->db->prepare(
                "INSERT INTO payments (booking_id, user_id, amount, payment_method, transaction_id, status)
                 VALUES (?, ?, ?, 'credit_card', ?, 'paid')"
            );
            $stmt->execute([
                $bookingId, $_SESSION['user_id'], $totalPrice,
                $paymentResult['transaction_id']
            ]);

            // Commit transaction
            $this->db->commit();

            // Notify the asset owner
            NotificationController::create(
                $asset['owner_id'],
                t('new_booking'),
                "{$_SESSION['full_name']} booked your \"{$asset['title']}\".",
                'booking',
                'index.php?page=dashboard'
            );

            // Notify the renter
            NotificationController::create(
                $_SESSION['user_id'],
                t('booking_confirmed'),
                "Your booking for \"{$asset['title']}\" has been confirmed! Total: \${$totalPrice}",
                'booking',
                'index.php?page=profile&tab=bookings'
            );

            jsonResponse([
                'success'     => true,
                'booking_id'  => $bookingId,
                'total_price' => $totalPrice,
                'message'     => t('booking_success') . " \${$totalPrice}"
            ]);

        } catch (Exception $e) {
            $this->db->rollBack();
            jsonResponse(['success' => false, 'error' => t('booking_failed')], 500);
        }
    }

    // ─── CANCEL BOOKING ──────────────────────────────────────

    /**
     * Cancel a booking with cancellation policy enforcement.
     * - Free cancellation > 48 hours before start
     * - 50% refund 24-48 hours before
     * - No refund < 24 hours
     */
    public function cancelBooking(): void
    {
        if (!isLoggedIn()) {
            jsonResponse(['success' => false, 'error' => t('login_required')], 401);
        }

        $bookingId = (int) ($_POST['booking_id'] ?? 0);
        $reason    = trim($_POST['reason'] ?? '');

        $stmt = $this->db->prepare(
            "SELECT b.*, a.title AS asset_title, a.owner_id
             FROM bookings b
             JOIN assets a ON b.asset_id = a.asset_id
             WHERE b.booking_id = ? AND b.user_id = ? AND b.status != 'cancelled'"
        );
        $stmt->execute([$bookingId, $_SESSION['user_id']]);
        $booking = $stmt->fetch();

        if (!$booking) {
            jsonResponse(['success' => false, 'error' => t('booking_not_found')], 404);
        }

        $hoursUntilStart = (strtotime($booking['start_time']) - time()) / 3600;
        $refundPercent = 0;
        $policyMessage = '';

        if ($hoursUntilStart > 48) {
            $refundPercent = 100;
            $policyMessage = t('full_refund');
        } elseif ($hoursUntilStart > 24) {
            $refundPercent = 50;
            $policyMessage = t('partial_refund');
        } else {
            $refundPercent = 0;
            $policyMessage = t('no_refund');
        }

        $refundAmount = round($booking['total_price'] * ($refundPercent / 100), 2);

        // Cancel booking
        $stmt = $this->db->prepare(
            "UPDATE bookings SET status = 'cancelled', cancellation_reason = ?, cancelled_at = NOW()
             WHERE booking_id = ?"
        );
        $stmt->execute([$reason, $bookingId]);

        // Process refund if applicable
        if ($refundAmount > 0) {
            $stmt = $this->db->prepare(
                "UPDATE payments SET status = 'refunded' WHERE booking_id = ?"
            );
            $stmt->execute([$bookingId]);
        }

        // Notify owner
        NotificationController::create(
            $booking['owner_id'],
            t('booking_cancelled'),
            "{$_SESSION['full_name']} cancelled their booking for \"{$booking['asset_title']}\".",
            'booking',
            'index.php?page=dashboard'
        );

        jsonResponse([
            'success'        => true,
            'refund_amount'  => $refundAmount,
            'refund_percent' => $refundPercent,
            'message'        => $policyMessage
        ]);
    }
}
