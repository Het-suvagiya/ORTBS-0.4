<?php
require_once 'header.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Handle Cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking_id'])) {
    $cancel_id = $_POST['cancel_booking_id'];

    // Safety check: Ensure the booking actually belongs to the user
    $cancel_stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND user_id = ?");
    $cancel_stmt->bind_param("ii", $cancel_id, $user_id);

    if ($cancel_stmt->execute()) {
        $msg = "Booking cancelled successfully.";
    } else {
        $error = "Error cancelling booking.";
    }
    $cancel_stmt->close();
}

// Handle Removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_booking_id'])) {
    $remove_id = $_POST['remove_booking_id'];

    // Safety check: Ensure the booking belongs to the user and is already cancelled
    $remove_stmt = $conn->prepare("DELETE FROM bookings WHERE id = ? AND user_id = ? AND status = 'cancelled'");
    $remove_stmt->bind_param("ii", $remove_id, $user_id);

    if ($remove_stmt->execute()) {
        $msg = "Booking removed from your history.";
    } else {
        $error = "Error removing booking.";
    }
    $remove_stmt->close();
}

$stmt = $conn->prepare("SELECT b.*, r.name, r.primary_image, r.location FROM bookings b JOIN restaurants r ON b.restaurant_id = r.id WHERE b.user_id = ? ORDER BY b.booking_date DESC, b.booking_time DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings = $stmt->get_result();
$stmt->close();
?>

<div class="bookings-container" style="padding: 3rem 5%; min-height: 80vh; max-width: 1000px; margin: 0 auto;">
    <h1 style="margin-bottom: 2rem; font-family: 'Outfit', sans-serif;">My Bookings</h1>

    <?php if (isset($msg)): ?>
        <div style="background: #e8f5e9; color: #27ae60; padding: 1rem; border-radius: 8px; margin-bottom: 2rem;">
            <i class="fas fa-check-circle" style="margin-right: 0.5rem;"></i> <?php echo $msg; ?>
        </div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div style="background: #fdf2f2; color: #e74c3c; padding: 1rem; border-radius: 8px; margin-bottom: 2rem;">
            <i class="fas fa-exclamation-circle" style="margin-right: 0.5rem;"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; gap: 1.5rem;">
        <?php if ($bookings->num_rows > 0): ?>
            <?php while ($b = $bookings->fetch_assoc()): ?>
                <div
                    style="background: #fff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow: hidden; display: flex; position: relative;">

                    <?php if ($b['status'] == 'cancelled'): ?>
                        <form method="POST" onsubmit="return confirm('Remove this booking from your history completely?');"
                            style="position: absolute; top: 10px; right: 10px;">
                            <input type="hidden" name="remove_booking_id" value="<?php echo $b['id']; ?>">
                            <button type="submit"
                                style="background: rgba(231, 76, 60, 0.1); border: none; color: #e74c3c; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;"
                                onmouseover="this.style.background='#e74c3c'; this.style.color='#fff';"
                                onmouseout="this.style.background='rgba(231, 76, 60, 0.1)'; this.style.color='#e74c3c';">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                    <?php endif; ?>

                    <div style="width: 150px; background: url('<?php echo $b['primary_image']; ?>') center/cover;"></div>
                    <div style="padding: 1.5rem; flex: 1; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h3 style="margin: 0 0 0.5rem 0;">
                                <?php echo htmlspecialchars($b['name']); ?>
                            </h3>
                            <p style="color: #666; font-size: 0.9rem; margin: 0.2rem 0;"><i class="fas fa-calendar-day"
                                    style="width: 20px;"></i>
                                <?php echo date('D, M d, Y', strtotime($b['booking_date'])); ?>
                            </p>
                            <p style="color: #666; font-size: 0.9rem; margin: 0.2rem 0;"><i class="fas fa-clock"
                                    style="width: 20px;"></i>
                                <?php echo date('H:i', strtotime($b['booking_time'])); ?>
                            </p>
                            <p style="color: #666; font-size: 0.9rem; margin: 0.2rem 0;"><i class="fas fa-users"
                                    style="width: 20px;"></i>
                                <?php echo $b['guests']; ?> Guest(s)
                            </p>
                        </div>
                        <div style="text-align: right;">
                            <?php
                            $statusColor = '#e8f5e9';
                            $statusTextColor = '#27ae60';
                            if ($b['status'] == 'cancelled') {
                                $statusColor = '#fdf2f2';
                                $statusTextColor = '#e74c3c';
                            } else if ($b['status'] == 'pending') {
                                $statusColor = '#fff3e0';
                                $statusTextColor = '#f39c12';
                            }
                            ?>
                            <span
                                style="background: <?php echo $statusColor; ?>; color: <?php echo $statusTextColor; ?>; padding: 0.5rem 1.2rem; border-radius: 50px; font-weight: 700; font-size: 0.85rem; text-transform: uppercase;">
                                <?php echo htmlspecialchars($b['status']); ?>
                            </span>

                            <?php if ($b['status'] !== 'cancelled'): ?>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this booking?');"
                                    style="margin-top: 1rem;">
                                    <input type="hidden" name="cancel_booking_id" value="<?php echo $b['id']; ?>">
                                    <button type="submit"
                                        style="background: transparent; border: 1px solid #e74c3c; color: #e74c3c; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: all 0.3s;"
                                        onmouseover="this.style.background='#e74c3c'; this.style.color='#fff';"
                                        onmouseout="this.style.background='transparent'; this.style.color='#e74c3c';">
                                        Cancel Booking
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 4rem;">
                <i class="fas fa-utensils" style="font-size: 4rem; color: #bdc3c7; margin-bottom: 1rem;"></i>
                <h2>No bookings yet</h2>
                <p style="color: #7f8c8d; margin-bottom: 2rem;">You haven't made any reservations. Ready for a feast?</p>
                <a href="index.php"
                    style="background: #27ae60; color: #fff; padding: 0.8rem 2rem; border-radius: 50px; text-decoration: none; font-weight: 600;">Browse
                    Restaurants</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>