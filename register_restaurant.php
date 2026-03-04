<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// Check if profile is complete
$stmt = $conn->prepare("SELECT u_firstname, u_lastname, u_dob, u_gender FROM tbl_users WHERE u_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_profile = $stmt->get_result()->fetch_assoc();
$stmt->close();

$is_profile_complete = !empty($user_profile['u_firstname']) && !empty($user_profile['u_lastname']) && !empty($user_profile['u_dob']) && !empty($user_profile['u_gender']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_profile_complete) {
    $name = $_POST['name'];
    $cuisine = $_POST['cuisine'];
    $location = $_POST['location'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $seating_type = $_POST['seating_type'];
    $avg_price = $_POST['avg_price'];
    $max_guests = $_POST['max_guests'];
    $description = $_POST['description'];

    // Handle Image Upload
    $target_dir = "uploads/restaurants/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $image_name = time() . "_" . basename($_FILES["primary_image"]["name"]);
    $target_file = $target_dir . $image_name;

    if (move_uploaded_file($_FILES["primary_image"]["tmp_name"], $target_file)) {

        // Transfer User to Manager Table
        $raw_query = "SELECT u_firstname, u_lastname, u_email, u_password, u_phone, u_gender, u_dob, u_image, u_bio FROM tbl_users WHERE u_id = " . intval($user_id);
        $result = mysqli_query($conn, $raw_query);

        if ($result && mysqli_num_rows($result) > 0) {
            mysqli_store_result($conn);
            if ($row = mysqli_fetch_row($result)) {
                $u_firstname = $row[0];
                $u_lastname = $row[1];
                $u_email = $row[2];
                $u_password = $row[3];
                $u_phone = $row[4];
                $u_gender = $row[5];
                $u_dob = $row[6];
                $u_image = $row[7];
                $u_bio = $row[8];

                $insert_query = "INSERT INTO tbl_manager (m_id, m_firstname, m_lastname, m_email, m_password, m_phone, m_gender, m_dob, m_image, m_bio) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $insert_stmt = mysqli_prepare($conn, $insert_query);
                mysqli_stmt_bind_param($insert_stmt, "isssssssss", $user_id, $u_firstname, $u_lastname, $u_email, $u_password, $u_phone, $u_gender, $u_dob, $u_image, $u_bio);

                if (mysqli_stmt_execute($insert_stmt)) {
                    // Delete old user record
                    $delete_query = "DELETE FROM tbl_users WHERE u_id = ?";
                    $delete_stmt = mysqli_prepare($conn, $delete_query);
                    mysqli_stmt_bind_param($delete_stmt, "i", $user_id);
                    mysqli_stmt_execute($delete_stmt);

                    // Now insert restaurant mapped to this manager_id ($user_id)
                    $stmt = $conn->prepare("INSERT INTO restaurants (manager_id, name, cuisine, location, phone, email, seating_type, avg_price, max_guests, description, primary_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("issssssdiss", $user_id, $name, $cuisine, $location, $phone, $email, $seating_type, $avg_price, $max_guests, $description, $target_file);

                    if ($stmt->execute()) {
                        $_SESSION['role'] = ROLE_MANAGER; // Update role in session
                        $success_msg = "Your application has been submitted successfully and is pending admin approval. You are now a manager.";
                    } else {
                        $error_msg = "Error submitting application: " . $conn->error;
                    }
                    $stmt->close();
                } else {
                    $error_msg = "Error transferring to manager table.";
                }
            }
            mysqli_free_result($result);
        } else {
            $error_msg = "User not found data error.";
        }
    } else {
        $error_msg = "Sorry, there was an error uploading your image.";
    }
}

require_once 'header.php';
?>

<div class="application-container"
    style="max-width: 800px; margin: 2rem auto; padding: 2rem; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
    <?php if (!$is_profile_complete): ?>
        <div style="text-align: center; padding: 3rem;">
            <i class="fas fa-user-edit" style="font-size: 3rem; color: #f39c12; margin-bottom: 1rem;"></i>
            <h2>Complete Your Profile First</h2>
            <p style="color: #666; margin-bottom: 2rem;">To apply for restaurant registration, you must fill out your
                profile details (First Name, Last Name, Date of Birth, and Gender).</p>
            <a href="profile.php"
                style="background: #3498db; color: #fff; padding: 0.8rem 2rem; border-radius: 6px; text-decoration: none; font-weight: 600;">Go
                to Profile</a>
        </div>
    <?php else: ?>
        <?php if ($success_msg): ?>
            <p
                style="color: #27ae60; text-align: center; font-weight: 600; margin-bottom: 2rem; padding: 1rem; background: #e8f5e9; border-radius: 6px;">
                <?php echo $success_msg; ?>
            </p>
            <div style="text-align: center;"><a href="index.php" style="color: #3498db; font-weight: 600;">Back to Home</a>
            </div>
        <?php else: ?>
            <h1 style="margin-bottom: 1rem;">Register My Restaurant</h1>
            <p style="color: #666; margin-bottom: 2rem;">Please provide the necessary details for your restaurant application.
            </p>

            <?php if ($error_msg): ?>
                <p style="color: #e74c3c; margin-bottom: 1rem;">
                    <?php echo $error_msg; ?>
                </p>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" style="display: grid; gap: 1.5rem;">
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Restaurant Name *</label>
                    <input type="text" name="name" required
                        style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px;">
                </div>

                <div style="display: flex; gap: 1rem;">
                    <div style="flex: 1;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Cuisine *</label>
                        <input type="text" name="cuisine" required
                            style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px;"
                            placeholder="e.g. Italian, Indian">
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Location *</label>
                        <input type="text" name="location" required
                            style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px;">
                    </div>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <div style="flex: 1;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Phone Number *</label>
                        <input type="tel" name="phone" required
                            style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px;">
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Restaurant Email *</label>
                        <input type="email" name="email" required
                            style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px;">
                    </div>
                </div>

                <div style="display: flex; gap: 1rem; align-items: flex-end;">
                    <div style="flex: 1;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Seating Type *</label>
                        <select name="seating_type" required
                            style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px;">
                            <option value="inside">Inside</option>
                            <option value="outside">Outside</option>
                            <option value="both">Both</option>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Average Price (for 2) *</label>
                        <input type="number" name="avg_price" step="0.01" required
                            style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px;">
                    </div>
                </div>

                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Maximum Guests per Booking *</label>
                    <input type="number" name="max_guests" min="1" required
                        style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px;" placeholder="e.g. 20">
                </div>

                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Restaurant Photos (First Image)
                        *</label>
                    <input type="file" name="primary_image" accept="image/*" required
                        style="width: 100%; padding: 0.8rem; border: 1px dashed #ccc; border-radius: 6px; background: #fafafa;">
                </div>

                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Description (Optional)</label>
                    <textarea name="description"
                        style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px; min-height: 100px;"></textarea>
                </div>

                <button type="submit"
                    style="background: #27ae60; color: #fff; padding: 1rem; border: none; border-radius: 6px; font-weight: 700; cursor: pointer; transition: transform 0.2s;"
                    onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">Apply
                    Now</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>