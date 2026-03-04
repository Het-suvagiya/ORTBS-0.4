<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// Fetch current user details
$role = getUserRole();
if ($role == ROLE_ADMIN) {
    $stmt = $conn->prepare("SELECT a_email as email, a_firstname as first_name, a_lastname as last_name, a_gender as gender, a_dob as dob, a_bio as bio, a_image as profile_image FROM tbl_admin WHERE a_id = ?");
} elseif ($role == ROLE_MANAGER) {
    $stmt = $conn->prepare("SELECT m_email as email, m_firstname as first_name, m_lastname as last_name, m_gender as gender, m_dob as dob, m_bio as bio, m_image as profile_image FROM tbl_manager WHERE m_id = ?");
} else {
    $stmt = $conn->prepare("SELECT u_email as email, u_firstname as first_name, u_lastname as last_name, u_gender as gender, u_dob as dob, u_bio as bio, u_image as profile_image FROM tbl_users WHERE u_id = ?");
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $gender = $_POST['gender'];
    $dob = $_POST['dob'];
    $bio = $_POST['bio'];
    $profile_image = $user['profile_image']; // Default to existing

    // Handle Image Upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $target_dir = "uploads/users/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $image_name = time() . "_" . basename($_FILES["profile_image"]["name"]);
        $target_file = $target_dir . $image_name;

        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            $profile_image = $target_file;
        } else {
            $error_msg = "Sorry, there was an error uploading your profile image.";
        }
    }

    if (empty($error_msg)) {
        if ($role == ROLE_ADMIN) {
            $stmt = $conn->prepare("UPDATE tbl_admin SET a_firstname = ?, a_lastname = ?, a_gender = ?, a_dob = ?, a_bio = ?, a_image = ? WHERE a_id = ?");
        } elseif ($role == ROLE_MANAGER) {
            $stmt = $conn->prepare("UPDATE tbl_manager SET m_firstname = ?, m_lastname = ?, m_gender = ?, m_dob = ?, m_bio = ?, m_image = ? WHERE m_id = ?");
        } else {
            $stmt = $conn->prepare("UPDATE tbl_users SET u_firstname = ?, u_lastname = ?, u_gender = ?, u_dob = ?, u_bio = ?, u_image = ? WHERE u_id = ?");
        }
        $stmt->bind_param("ssssssi", $first_name, $last_name, $gender, $dob, $bio, $profile_image, $user_id);

        if ($stmt->execute()) {
            $success_msg = "profile updated";
            // Update local user data
            $user['first_name'] = $first_name;
            $user['last_name'] = $last_name;
            $user['gender'] = $gender;
            $user['dob'] = $dob;
            $user['bio'] = $bio;
            $user['profile_image'] = $profile_image;
        } else {
            $error_msg = "Error updating profile: " . $conn->error;
        }
        $stmt->close();
    }
}

require_once 'header.php';
?>

<div class="profile-container"
    style="max-width: 800px; margin: 2rem auto; padding: 2rem; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
    <?php if ($success_msg): ?>
        <p style="color: #27ae60; text-align: center; font-weight: 600; margin-bottom: 1rem;">
            <?php echo $success_msg; ?>
        </p>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <p style="color: #e74c3c; text-align: center; font-weight: 600; margin-bottom: 1rem;">
            <?php echo $error_msg; ?>
        </p>
    <?php endif; ?>

    <h1 style="margin-bottom: 2rem; border-bottom: 2px solid #f4f7f6; padding-bottom: 1rem;">My Profile</h1>

    <form method="POST" enctype="multipart/form-data" style="display: grid; gap: 1.5rem;">
        <div style="text-align: center; margin-bottom: 1rem;">
            <?php if (!empty($user['profile_image'])): ?>
                <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile Image"
                    style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; margin-bottom: 1rem; border: 3px solid #27ae60;">
            <?php else: ?>
                <div
                    style="width: 120px; height: 120px; border-radius: 50%; background: #f1f2f6; display: flex; align-items: center; justify-content: center; font-size: 3rem; color: #bdc3c7; margin: 0 auto 1rem;">
                    <i class="fas fa-user"></i>
                </div>
            <?php endif; ?>
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Profile Image</label>
            <input type="file" name="profile_image" accept="image/*"
                style="width: 100%; max-width: 300px; padding: 0.5rem; border: 1px solid #ddd; border-radius: 6px;">
        </div>

        <div style="display: flex; gap: 1rem;">
            <div style="flex: 1;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">First Name</label>
                <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>"
                    required style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px;">
            </div>
            <div style="flex: 1;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Last Name</label>
                <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>"
                    required style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px;">
            </div>
        </div>

        <div>
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Email (Cannot be changed)</label>
            <input type="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" disabled
                style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px; background: #f9f9f9; color: #777;">
        </div>

        <div>
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Gender</label>
            <div style="display: flex; gap: 1.5rem;">
                <label><input type="radio" name="gender" value="male" <?php echo (strtolower($user['gender'] ?? '') == 'male') ? 'checked' : ''; ?>> Male</label>
                <label><input type="radio" name="gender" value="female" <?php echo (strtolower($user['gender'] ?? '') == 'female') ? 'checked' : ''; ?>> Female</label>
                <label><input type="radio" name="gender" value="other" <?php echo (strtolower($user['gender'] ?? '') == 'other') ? 'checked' : ''; ?>> Other</label>
            </div>
        </div>

        <div>
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Date of Birth</label>
            <input type="date" name="dob" value="<?php echo htmlspecialchars($user['dob'] ?? ''); ?>" required
                style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px;">
        </div>

        <div>
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Bio</label>
            <textarea name="bio"
                style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px; min-height: 100px;"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
        </div>

        <button type="submit"
            style="background: #27ae60; color: #fff; padding: 1rem; border: none; border-radius: 6px; font-weight: 700; cursor: pointer; transition: background 0.3s;"
            onmouseover="this.style.background='#219150'" onmouseout="this.style.background='#27ae60'">Save
            Changes</button>
    </form>
</div>

<?php require_once 'footer.php'; ?>