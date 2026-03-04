<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('index.php?auth=login');
}

$user_id = $_SESSION['user_id'];
$rest_id = $_GET['id'] ?? 0;

// Fetch restaurant details
$stmt = $conn->prepare("SELECT * FROM restaurants WHERE id = ? AND status = 'approved' AND is_published = 1");
$stmt->bind_param("i", $rest_id);
$stmt->execute();
$restaurant = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$restaurant) {
    die("Restaurant not found or not published.");
}

// Fetch user profile
$stmt = $conn->prepare("SELECT u_firstname as first_name, u_lastname as last_name, u_gender as gender, u_dob as dob FROM tbl_users WHERE u_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch closed days for this restaurant
$stmt = $conn->prepare("SELECT day_of_week FROM restaurant_schedule WHERE restaurant_id = ? AND is_closed = 1");
$stmt->bind_param("i", $rest_id);
$stmt->execute();
$closed_days_result = $stmt->get_result();
$closed_days = [];
while ($row = $closed_days_result->fetch_assoc()) {
    $closed_days[] = $row['day_of_week'];
}
$stmt->close();

// Check if basic profile is filled
$is_profile_complete = !empty($user['first_name'] ?? '') && !empty($user['last_name'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_booking'])) {
    $date = $_POST['date'];
    $time = $_POST['time'];
    $guests = (int)$_POST['guests'];

    // Server-side validation for closed days
    $day_of_week = strtolower(date('D', strtotime($date))); // 'Mon', 'Tue' -> 'mon', 'tue'
    
    if (in_array($day_of_week, $closed_days)) {
        $error = "Sorry, this restaurant is closed on the selected day.";
    } elseif ($guests > $restaurant['max_guests']) {
        $error = "The number of guests exceeds the maximum capacity of " . $restaurant['max_guests'] . " for this restaurant.";
    } else {
        // Update profile if missing
        if (!$is_profile_complete) {
            $fname = $_POST['first_name'];
            $lname = $_POST['last_name'];
            $gender = $_POST['gender'];
            $dob = $_POST['dob'];
            $stmt = $conn->prepare("UPDATE tbl_users SET u_firstname = ?, u_lastname = ?, u_gender = ?, u_dob = ? WHERE u_id = ?");
            $stmt->bind_param("ssssi", $fname, $lname, $gender, $dob, $user_id);
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $conn->prepare("SELECT id FROM bookings WHERE restaurant_id = ? AND booking_date = ? AND booking_time = ? AND status != 'cancelled'");
        $stmt->bind_param("iss", $rest_id, $date, $time);
        $stmt->execute();
        $existing = $stmt->get_result();
        $stmt->close();

        if ($existing->num_rows > 0) {
            $error = "Sorry, that time slot was just booked by someone else. Please select another time.";
        } else {
            $stmt = $conn->prepare("INSERT INTO bookings (user_id, restaurant_id, booking_date, booking_time, guests, status) VALUES (?, ?, ?, ?, ?, 'confirmed')");
            $stmt->bind_param("iissi", $user_id, $rest_id, $date, $time, $guests);

            if ($stmt->execute()) {
                $booking_id = $conn->insert_id;
                $success = true;
            }
        }
    }
}

require_once 'header.php';
?>

<div class="booking-page"
    style="padding: 4rem 5%; max-width: 1200px; margin: 0 auto; display: flex; gap: 3rem; align-items: flex-start; flex-wrap: wrap;">

    <!-- Left Side: Restaurant Info -->
    <div style="flex: 1.2; min-width: 300px;">

        <!-- Premium Image Display -->
        <div
            style="position: relative; width: 100%; height: 400px; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-bottom: 2rem;">
            <img src="<?php echo htmlspecialchars($restaurant['primary_image']); ?>"
                style="width: 100%; height: 100%; object-fit: cover;">
            <div
                style="position: absolute; top: 1.5rem; right: 1.5rem; background: #ef4444; color: #fff; font-size: 0.9rem; font-weight: 700; padding: 0.6rem 1.2rem; border-radius: 6px; box-shadow: 0 4px 10px rgba(239,68,68,0.3);">
                -20% Special Offer
            </div>
        </div>

        <!-- Content Container -->
        <div>
            <h1
                style="font-family: 'Outfit', sans-serif; color: #1e293b; font-size: 2.5rem; margin: 0 0 1rem 0; font-weight: 800;">
                <?php echo htmlspecialchars($restaurant['name']); ?>
            </h1>

            <div style="display: flex; flex-wrap: wrap; gap: 0.8rem; margin-bottom: 2rem;">
                <span
                    style="font-size: 0.95rem; background: #f1f5f9; color: #475569; padding: 0.6rem 1.2rem; border-radius: 20px; font-weight: 600;">
                    <?php echo htmlspecialchars($restaurant['cuisine'] ?? 'Various'); ?>
                </span>
                <span
                    style="font-size: 0.95rem; background: #f1f5f9; color: #475569; padding: 0.6rem 1.2rem; border-radius: 20px; font-weight: 600;">
                    <i class="fas fa-map-marker-alt"
                        style="margin-right: 6px;"></i><?php echo htmlspecialchars($restaurant['location'] ?? 'Location N/A'); ?>
                </span>
                <span
                    style="font-size: 0.95rem; background: #f1f5f9; color: #475569; padding: 0.6rem 1.2rem; border-radius: 20px; font-weight: 600;">
                    <i class="fas fa-coins"
                        style="margin-right: 6px;"></i>₹<?php echo number_format($restaurant['avg_price'] ?? 0, 0); ?>
                    Avg.
                </span>
            </div>

            <?php if (!empty($restaurant['description'])): ?>
                <h4 style="font-size: 1.1rem; color: #1e293b; margin-bottom: 0.5rem; font-weight: 700;">Description
                </h4>
                <p style="font-size: 1rem; color: #64748b; line-height: 1.6; margin-bottom: 1.5rem;">
                    <?php echo nl2br(htmlspecialchars($restaurant['description'])); ?>
                </p>
            <?php endif; ?>

            <div style="display: grid; gap: 1.2rem; padding-top: 1.5rem; border-top: 1px solid #f1f5f9;">
                <?php if (!empty($restaurant['phone'])): ?>
                    <div style="display: flex; align-items: center; gap: 1rem; font-size: 1.1rem; color: #475569;">
                        <div
                            style="width: 32px; height: 32px; background: #f8fafc; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #94a3b8;">
                            <i class="fas fa-phone" style="font-size: 1.1rem;"></i>
                        </div>
                        <?php echo htmlspecialchars($restaurant['phone']); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($restaurant['email'])): ?>
                    <div style="display: flex; align-items: center; gap: 1rem; font-size: 1.1rem; color: #475569;">
                        <div
                            style="width: 32px; height: 32px; background: #f8fafc; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #94a3b8;">
                            <i class="fas fa-envelope" style="font-size: 1.1rem;"></i>
                        </div>
                        <?php echo htmlspecialchars($restaurant['email']); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($restaurant['seating_type'])): ?>
                    <div style="display: flex; align-items: center; gap: 1rem; font-size: 1.1rem; color: #475569;">
                        <div
                            style="width: 32px; height: 32px; background: #f8fafc; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #94a3b8;">
                            <i class="fas fa-chair" style="font-size: 1.1rem;"></i>
                        </div>
                        <span style="font-weight: 500;">Seating:</span>
                        <span style="text-transform: capitalize; font-weight: 600;"><?php echo htmlspecialchars($restaurant['seating_type']); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>


    <!-- Right Side: Booking Wizard -->
    <div id="booking-wizard-container"
        style="flex: 1; min-width: 280px; max-width: 340px; background: #fff; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.06); padding: 1.2rem; align-self: flex-start; position: sticky; top: 100px;">
        <?php if (isset($error)): ?>
            <div style="background: #fdf2f2; color: #e74c3c; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; border: 1px solid #fce4e4;">
                <i class="fas fa-exclamation-circle" style="margin-right: 0.5rem;"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div style="text-align: center; padding: 3rem 0;">
                <i class="fas fa-check-circle" style="font-size: 4rem; color: #27ae60; margin-bottom: 1rem;"></i>
                <h2>Booking Confirmed!</h2>
                <p style="color: #7f8c8d; margin-bottom: 2rem;">Your reservation at
                    <?php echo htmlspecialchars($restaurant['name']); ?> is all set.
                </p>
                <a href="my_bookings.php"
                    style="background: #27ae60; color: #fff; padding: 1rem 2rem; border-radius: 50px; text-decoration: none; font-weight: 700;">View
                    My Bookings</a>
            </div>
        <?php else: ?>
            <div id="booking-wizard">
                <!-- Step Progress -->
                <div style="display: flex; justify-content: space-between; margin-bottom: 1.5rem; position: relative;">
                    <div
                        style="position: absolute; top: 12px; left: 0; width: 100%; height: 2px; background: #eee; z-index: 1;">
                    </div>
                    <div class="step-dot active" id="dot-1"
                        style="width: 25px; height: 25px; background: #27ae60; border-radius: 50%; z-index: 2; border: 4px solid #fff; box-shadow: 0 0 0 2px #27ae60;">
                    </div>
                    <div class="step-dot" id="dot-2"
                        style="width: 25px; height: 25px; background: #eee; border-radius: 50%; z-index: 2; border: 4px solid #fff;">
                    </div>
                    <div class="step-dot" id="dot-3"
                        style="width: 25px; height: 25px; background: #eee; border-radius: 50%; z-index: 2; border: 4px solid #fff;">
                    </div>
                    <div class="step-dot" id="dot-4"
                        style="width: 25px; height: 25px; background: #eee; border-radius: 50%; z-index: 2; border: 4px solid #fff;">
                    </div>
                </div>

                <form method="POST" id="main-booking-form">
                    <!-- Step 1: Date -->
                    <div class="wizard-step" id="step-1" style="min-height: 280px; display: flex; flex-direction: column;">
                        <h3 style="font-size: 1.05rem; margin-bottom: 0;">1. Select Date</h3>

                        <!-- Custom Calendar UI -->
                        <div class="custom-calendar-container"
                            style="margin-top: 0.5rem; width: 100%; margin-left: auto; margin-right: auto; flex-grow: 1;">
                            <div class="calendar-header"
                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <button type="button" id="prev-month"
                                    style="background: none; border: none; font-size: 1.1rem; cursor: pointer; color: #bdc3c7;"><i
                                        class="fas fa-chevron-left"></i></button>
                                <h4 id="calendar-month-year"
                                    style="margin: 0; font-size: 1.1rem; font-weight: 800; color: #1e293b;"></h4>
                                <button type="button" id="next-month"
                                    style="background: none; border: none; font-size: 1.1rem; cursor: pointer; color: #1e293b;"><i
                                        class="fas fa-chevron-right"></i></button>
                            </div>
                            <div class="calendar-days"
                                style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.2rem; text-align: center; font-size: 0.7rem; font-weight: 600; color: #334155; margin-bottom: 0.2rem;">
                                <div>Mo</div>
                                <div>Tu</div>
                                <div>We</div>
                                <div>Th</div>
                                <div>Fr</div>
                                <div>Sa</div>
                                <div>Su</div>
                            </div>
                            <div id="calendar-grid"
                                style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.2rem;">
                                <!-- JS will populate days here -->
                            </div>
                        </div>

                        <!-- Hidden input to store selected date -->
                        <input type="hidden" name="date" id="book-date" required>

                        <button type="button" onclick="nextStep(2)"
                            style="margin-top: 1rem; width: 100%; background: #2c3e50; color: #fff; padding: 0.8rem; border: none; border-radius: 10px; font-weight: 700; cursor: pointer;">Next
                            Step</button>
                    </div>

                    <!-- Step 2: Time -->
                    <div class="wizard-step" id="step-2" style="display: none; min-height: 280px; flex-direction: column;">
                        <h3 style="font-size: 1.05rem; margin-bottom: 0;">2. Pick a Time</h3>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.4rem; margin-top: 0.5rem; flex-grow: 1; align-content: start;">
                            <?php
                            $times = ['12:00', '13:00', '14:00', '18:00', '19:00', '20:00', '21:00', '22:00'];
                            foreach ($times as $t): ?>
                                <label style="cursor: pointer;">
                                    <input type="radio" name="time" value="<?php echo $t; ?>" style="display: none;"
                                        class="time-radio">
                                    <div style="padding: 0.8rem; border: 1px solid #ddd; border-radius: 8px; text-align: center; transition: all 0.3s;"
                                        class="time-box">
                                        <?php echo $t; ?>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                            <button type="button" onclick="prevStep(1)"
                                style="flex: 1; background: #f1f5f9; color: #475569; padding: 0.8rem; border: none; border-radius: 10px; font-weight: 700; cursor: pointer;">Back</button>
                            <button type="button" onclick="nextStep(3)"
                                style="flex: 1; background: #2c3e50; color: #fff; padding: 0.8rem; border: none; border-radius: 10px; font-weight: 700; cursor: pointer;">Next
                                Step</button>
                        </div>
                    </div>

                    <!-- Step 3: Guests -->
                    <div class="wizard-step" id="step-3" style="display: none; min-height: 280px; flex-direction: column;">
                        <h3 style="font-size: 1.05rem; margin-bottom: 0;">3. Number of guests</h3>

                        <input type="hidden" name="guests" id="guests-input" required>

                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.4rem; margin-top: 0.5rem; flex-grow: 1; align-content: start;">
                            <?php for ($i = 2; $i <= 13; $i++): ?>
                                <div class="guest-box" data-guests="<?php echo $i; ?>"
                                    style="border: 1px solid #f1f5f9; border-radius: 8px; padding: 0.6rem 0; text-align: center; cursor: pointer; transition: all 0.2s; display: flex; flex-direction: column; align-items: center; justify-content: center; user-select: none;">
                                    <span
                                        style="font-weight: 600; font-size: 0.95rem; color: #1e293b; margin-bottom: 2px;"><?php echo $i; ?></span>
                                    <?php if ($i <= 7): ?>
                                        <span
                                            style="font-size: 0.65rem; background: ; color: ; padding: 2px 6px; border-radius: 12px; font-weight: 600;"></span>
                                    <?php endif; ?>
                                </div>
                            <?php endfor; ?>
                        </div>

                        <div id="more-guests-container" style="display: none; margin-top: 1.5rem; text-align: center;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Enter number of guests:</label>
                            <input type="number" id="manual-guests-input" min="1" style="width: 150px; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px; text-align: center; font-size: 1.1rem;">
                            <div id="guests-error-msg" style="color: #e74c3c; font-size: 0.85rem; margin-top: 0.5rem; display: none;"></div>
                        </div>

                        <div id="more-guests-toggle-container" style="text-align: center; margin-top: 1rem;">
                            <a href="javascript:void(0)" id="more-guests-toggle"
                                style="color: #1e293b; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.9rem;">
                                <span style="text-decoration: underline;">More guests options</span> <i class="fas fa-plus"
                                    style="font-size: 1rem;"></i>
                            </a>
                        </div>

                        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                            <button type="button" onclick="prevStep(2)"
                                style="flex: 1; background: #f1f5f9; color: #475569; padding: 0.8rem; border: none; border-radius: 10px; font-weight: 700; cursor: pointer;">Back</button>
                            <button type="button" id="btn-review-confirm" onclick="nextStep(4)"
                                style="flex: 1; background: #2c3e50; color: #fff; padding: 0.8rem; border: none; border-radius: 10px; font-weight: 700; cursor: pointer;">Review
                                & Confirm</button>
                        </div>
                    </div>

                    <!-- Step 4: Confirm -->
                    <div class="wizard-step" id="step-4" style="display: none; min-height: 280px; flex-direction: column;">
                        <h3 style="font-size: 1.05rem; margin-bottom: 0;">4. Final Confirmation</h3>
                        <div
                            style="margin-top: 0.5rem; background: #f8f9fa; padding: 0.8rem; border-radius: 10px; margin-bottom: 1rem; flex-grow: 1;">
                            <p><strong>Date:</strong> <span id="summary-date"></span></p>
                            <p><strong>Time:</strong> <span id="summary-time"></span></p>
                            <p><strong>Guests:</strong> <span id="summary-guests"></span></p>
                        </div>

                        <?php if (!$is_profile_complete): ?>
                            <p style="color: #e67e22; font-size: 0.9rem; margin-bottom: 1rem; font-weight: 600;">Please
                                provide your details to complete the booking:</p>
                            <div style="display: grid; gap: 1rem;">
                                <div>
                                    <label
                                        style="display: block; font-size: 0.8rem; color: #7f8c8d; margin-bottom: 0.2rem;">First
                                        Name</label>
                                    <input type="text" name="first_name" placeholder="Enter First Name" required
                                        value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>"
                                        style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px;">
                                </div>
                                <div>
                                    <label
                                        style="display: block; font-size: 0.8rem; color: #7f8c8d; margin-bottom: 0.2rem;">Last
                                        Name</label>
                                    <input type="text" name="last_name" placeholder="Enter Last Name" required
                                        value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>"
                                        style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px;">
                                </div>
                                <div>
                                    <label
                                        style="display: block; font-size: 0.8rem; color: #7f8c8d; margin-bottom: 0.2rem;">Gender</label>
                                    <select name="gender"
                                        style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px;">
                                        <option value="male" <?php echo ($user['gender'] ?? '') == 'male' ? 'selected' : ''; ?>>
                                            Male</option>
                                        <option value="female" <?php echo ($user['gender'] ?? '') == 'female' ? 'selected' : ''; ?>>
                                            Female</option>
                                        <option value="other" <?php echo ($user['gender'] ?? '') == 'other' ? 'selected' : ''; ?>>
                                            Other</option>
                                    </select>
                                </div>
                                <div>
                                    <label
                                        style="display: block; font-size: 0.8rem; color: #7f8c8d; margin-bottom: 0.2rem;">Date
                                        of Birth</label>
                                    <input type="date" name="dob" required value="<?php echo $user['dob'] ?? ''; ?>"
                                        style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px;">
                                </div>
                            </div>
                        <?php else: ?>
                            <p style="color: #7f8c8d;">Confirming booking for <strong>
                                    <?php echo $user['first_name'] . ' ' . $user['last_name']; ?>
                                </strong></p>
                        <?php endif; ?>

                        <input type="hidden" name="confirm_booking" value="1">
                        <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                            <button type="button" onclick="prevStep(3)"
                                style="flex: 1; background: #f1f5f9; color: #475569; padding: 0.8rem; border: none; border-radius: 10px; font-weight: 700; cursor: pointer;">Back</button>
                            <button type="submit"
                                style="flex: 2; background: #27ae60; color: #fff; padding: 0.8rem; border: none; border-radius: 10px; font-weight: 700; cursor: pointer;">Book
                                Table Now</button>
                        </div>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function nextStep(step) {
        // Basic validation
        if (step === 2 && !document.getElementById('book-date').value) {
            alert("Please select a date");
            return;
        }
        if (step === 3) {
            const timeSelected = document.querySelector('input[name="time"]:checked');
            if (!timeSelected) {
                alert("Please pick a time");
                return;
            }
        }

        // Hide all steps
        document.querySelectorAll('.wizard-step').forEach(s => s.style.display = 'none');
        document.querySelectorAll('.step-dot').forEach(d => {
            d.style.background = '#eee';
            d.style.boxShadow = 'none';
        });

        // Show current step
        document.getElementById(`step-${step}`).style.display = 'block';
        for (let i = 1; i <= step; i++) {
            const dot = document.getElementById(`dot-${i}`);
            dot.style.background = '#27ae60';
            if (i === step) dot.style.boxShadow = '0 0 0 2px #27ae60';
        }

        // Fill summary
        if (step === 4) {
            document.getElementById('summary-date').innerText = document.getElementById('book-date').value;
            document.getElementById('summary-time').innerText = document.querySelector('input[name="time"]:checked').value;
            document.getElementById('summary-guests').innerText = document.getElementById('guests-input').value + " Person(s)";
        }
    }

    function prevStep(step) {
        // Hide all steps
        document.querySelectorAll('.wizard-step').forEach(s => s.style.display = 'none');
        document.querySelectorAll('.step-dot').forEach(d => {
            d.style.background = '#eee';
            d.style.boxShadow = 'none';
        });

        // Show current step
        document.getElementById(`step-${step}`).style.display = 'block';
        for (let i = 1; i <= step; i++) {
            const dot = document.getElementById(`dot-${i}`);
            dot.style.background = '#27ae60';
            if (i === step) dot.style.boxShadow = '0 0 0 2px #27ae60';
        }
    }

    document.querySelectorAll('.time-radio').forEach(radio => {
        radio.addEventListener('change', function () {
            document.querySelectorAll('.time-box').forEach(box => {
                if (!box.classList.contains('disabled-box')) {
                    box.style.background = 'none';
                    box.style.color = '#333';
                    box.style.borderColor = '#ddd';
                }
            });
            const box = this.nextElementSibling;
            if (!box.classList.contains('disabled-box')) {
                box.style.background = '#2c3e50';
                box.style.color = '#fff';
                box.style.borderColor = '#2c3e50';
            }
        });
    });

    // --- Guest Selection Logic ---
    const guestBoxes = document.querySelectorAll('.guest-box');
    const guestsInput = document.getElementById('guests-input');
    const toggleContainer = document.getElementById('more-guests-toggle-container');
    const manualInputContainer = document.getElementById('more-guests-container');
    const manualInput = document.getElementById('manual-guests-input');
    const errorMsg = document.getElementById('guests-error-msg');
    const btnConfirm = document.getElementById('btn-review-confirm');
    const maxGuests = <?php echo $restaurant['max_guests']; ?>;

    guestBoxes.forEach(box => {
        box.addEventListener('click', function () {
            // Reset all boxes
            guestBoxes.forEach(b => {
                b.style.borderColor = '#f1f5f9';
                b.style.boxShadow = 'none';
            });
            // Select this box
            this.style.borderColor = '#1e293b';
            this.style.boxShadow = '0 0 0 1px #1e293b';
            guestsInput.value = this.dataset.guests;
            
            // Clear manual input if any
            manualInput.value = '';
            errorMsg.style.display = 'none';
            btnConfirm.disabled = false;
            btnConfirm.style.opacity = '1';
        });
    });

    // Auto-select first guest box if available
    if (guestBoxes.length > 0) guestBoxes[0].click();

    // Toggle manual input
    document.getElementById('more-guests-toggle').addEventListener('click', function() {
        toggleContainer.style.display = 'none';
        manualInputContainer.style.display = 'block';
        
        // Deselect boxes
        guestBoxes.forEach(b => {
            b.style.borderColor = '#f1f5f9';
            b.style.boxShadow = 'none';
        });
        guestsInput.value = ''; // Clear box value
        manualInput.focus();
    });

    // Manual input validation
    manualInput.addEventListener('input', function() {
        let val = parseInt(this.value);
        if (isNaN(val) || val < 1) {
            btnConfirm.disabled = true;
            btnConfirm.style.opacity = '0.5';
            errorMsg.style.display = 'none';
            return;
        }

        guestsInput.value = val;

        if (val > maxGuests) {
            errorMsg.textContent = `The size is too large. Max size: ${maxGuests}. Please re-enter.`;
            errorMsg.style.display = 'block';
            btnConfirm.disabled = true;
            btnConfirm.style.opacity = '0.5';
        } else {
            errorMsg.style.display = 'none';
            btnConfirm.disabled = false;
            btnConfirm.style.opacity = '1';
        }
    });

    // --- Custom Calendar Logic ---
    const calendarGrid = document.getElementById('calendar-grid');
    const monthYearDisplay = document.getElementById('calendar-month-year');
    const bookDateInput = document.getElementById('book-date');
    const prevBtn = document.getElementById('prev-month');
    const nextBtn = document.getElementById('next-month');

    let currentDate = new Date();
    // Normalize today for comparisons
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    // Max advance booking is 2 months
    const limitDate = new Date(today);
    limitDate.setMonth(limitDate.getMonth() + 2);

    // Closed days mapping (JS Date.getDay() format: 0=Sun, 1=Mon, ..., 6=Sat)
    // DB enum format: 'sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'
    const dbClosedDays = <?php echo json_encode($closed_days); ?>;
    const dayMap = {'sun': 0, 'mon': 1, 'tue': 2, 'wed': 3, 'thu': 4, 'fri': 5, 'sat': 6};
    const closedJsDays = dbClosedDays.map(d => dayMap[d.toLowerCase()]);

    function renderCalendar(date) {
        calendarGrid.innerHTML = '';
        const year = date.getFullYear();
        const month = date.getMonth();

        // Formatting month/year
        const options = { month: 'long', year: 'numeric' };
        monthYearDisplay.textContent = date.toLocaleDateString('en-US', options);

        // Get first day of month (1-7, where 1=Mon, 0=Sun converted to 7)
        let firstDay = new Date(year, month, 1).getDay();
        firstDay = firstDay === 0 ? 7 : firstDay;

        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const daysInPrevMonth = new Date(year, month, 0).getDate();

        // Previous month filler days
        for (let i = firstDay - 1; i > 0; i--) {
            const dayNum = daysInPrevMonth - i + 1;
            addDayCell(dayNum, true, null);
        }

        // Current month days
        for (let i = 1; i <= daysInMonth; i++) {
            const cellDate = new Date(year, month, i);
            const isPastOrFuture = cellDate < today || cellDate > limitDate;
            const isClosedDay = closedJsDays.includes(cellDate.getDay());
            const isDisabled = isPastOrFuture || isClosedDay;
            addDayCell(i, false, isDisabled, cellDate);
        }

        // Calculate remaining cells for grid closure (optional, keeping it simple)
        const totalRendered = (firstDay - 1) + daysInMonth;
        const remainder = totalRendered % 7;
        if (remainder !== 0) {
            for (let i = 1; i <= (7 - remainder); i++) {
                // Next month filler
                addDayCell(i, true, null);
            }
        }

        updateNavButtons();
    }

    function addDayCell(day, isFiller, isDisabled, cellDate = null) {
        const cell = document.createElement('div');
        cell.style.aspectRatio = '1';
        cell.style.borderRadius = '8px';
        cell.style.display = 'flex';
        cell.style.flexDirection = 'column';
        cell.style.alignItems = 'center';
        cell.style.justifyContent = 'center';
        cell.style.border = '1px solid #f1f5f9';
        cell.style.position = 'relative';
        cell.style.cursor = (isFiller || isDisabled) ? 'default' : 'pointer';
        cell.style.userSelect = 'none';

        if (isFiller) {
            cell.style.color = '#cbd5e1';
            cell.innerText = day;
        } else if (isDisabled) {
            cell.style.color = '#94a3b8';
            if (cellDate < today) {
                cell.style.textDecoration = 'line-through';
            }
            cell.innerText = day;
        } else {
            // Valid pickable day
            cell.style.color = '#1e293b';
            cell.style.fontWeight = '600';
            cell.classList.add('valid-day');

            // Format YYYY-MM-DD for value
            const yyyy = cellDate.getFullYear();
            const mm = String(cellDate.getMonth() + 1).padStart(2, '0');
            const dd = String(cellDate.getDate()).padStart(2, '0');
            const dateStr = `${yyyy}-${mm}-${dd}`;
            cell.dataset.date = dateStr;

            // Day number
            const numSpan = document.createElement('span');
            numSpan.innerText = day;
            numSpan.style.zIndex = '2';
            cell.appendChild(numSpan);

            // Click event
            cell.addEventListener('click', function () {
                document.querySelectorAll('.valid-day').forEach(d => {
                    d.style.borderColor = '#f1f5f9';
                    d.style.background = 'transparent';
                    d.style.boxShadow = 'none';
                });

                this.style.borderColor = '#10b981'; // Green accent
                this.style.background = '#f0fdf4';
                this.style.boxShadow = '0 0 0 1px #10b981';
                bookDateInput.value = this.dataset.date;

                // Dynamically fetch and disable booked times
                fetch(`get_booked_times.php?restaurant_id=<?php echo $rest_id; ?>&date=${this.dataset.date}`)
                    .then(res => res.json())
                    .then(bookedTimes => {
                        const timeRadios = document.querySelectorAll('.time-radio');
                        
                        // First, reset all time slots to available
                        timeRadios.forEach(radio => {
                            radio.disabled = false;
                            radio.checked = false;
                            const box = radio.nextElementSibling;
                            box.style.background = 'none';
                            box.style.color = '#333';
                            box.style.borderColor = '#ddd';
                            box.style.opacity = '1';
                            box.style.cursor = 'pointer';
                            box.style.textDecoration = 'none';
                            box.classList.remove('disabled-box');
                        });

                        // Then, block the booked ones
                        timeRadios.forEach(radio => {
                            if (bookedTimes.includes(radio.value)) {
                                radio.disabled = true;
                                const box = radio.nextElementSibling;
                                box.style.background = '#f8f9fa';
                                box.style.color = '#cbd5e1';
                                box.style.borderColor = '#f1f5f9';
                                box.style.opacity = '0.6';
                                box.style.cursor = 'not-allowed';
                                box.style.textDecoration = 'line-through';
                                box.classList.add('disabled-box');
                            }
                        });
                    })
                    .catch(e => console.error("Error fetching available times:", e));
            });

            // Highlight if already selected
            if (bookDateInput.value === dateStr) {
                cell.click();
            }
        }

        calendarGrid.appendChild(cell);
    }

    function updateNavButtons() {
        const currentMonth = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
        const thisMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        const maxMonth = new Date(limitDate.getFullYear(), limitDate.getMonth(), 1);

        // Don't allow going to past months
        if (currentMonth <= thisMonth) {
            prevBtn.style.color = '#e2e8f0';
            prevBtn.style.pointerEvents = 'none';
        } else {
            prevBtn.style.color = '#1e293b';
            prevBtn.style.pointerEvents = 'auto';
        }

        // Don't allow going beyond 2 months
        if (currentMonth >= maxMonth) {
            nextBtn.style.color = '#e2e8f0';
            nextBtn.style.pointerEvents = 'none';
        } else {
            nextBtn.style.color = '#1e293b';
            nextBtn.style.pointerEvents = 'auto';
        }
    }

    prevBtn.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar(currentDate);
    });

    nextBtn.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar(currentDate);
    });

    // Init
    renderCalendar(currentDate);

</script>

<?php require_once 'footer.php'; ?>