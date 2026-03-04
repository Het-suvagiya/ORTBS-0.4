<?php
require_once 'config.php';

if (isLoggedIn()) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        echo json_encode(['success' => true, 'redirect' => 'index.php']);
        exit;
    }
    redirect('index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        // Check if email already exists in any table
        $query = "SELECT u_email as email FROM tbl_users WHERE u_email = ? 
                  UNION SELECT m_email as email FROM tbl_manager WHERE m_email = ? 
                  UNION SELECT a_email as email FROM tbl_admin WHERE a_email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sss", $email, $email, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Email already registered.";
            $stmt->close();
        } else {
            $stmt->close();
            // Secure password hash
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = ROLE_USER;

            $stmt = $conn->prepare("INSERT INTO tbl_users (u_email, u_password) VALUES (?, ?)");
            $stmt->bind_param("ss", $email, $hashed_password);

            if ($stmt->execute()) {
                // Auto-login after registration
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $role;

                if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                    echo json_encode(['success' => true, 'redirect' => 'index.php']);
                    exit;
                }
                redirect('index.php');
            } else {
                $error = "Registration failed. Please try again.";
            }
            $stmt->close();
        }
    }

    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        echo json_encode(['success' => false, 'error' => $error]);
        exit;
    }
}
?>
<?php require_once 'header.php'; ?>

<div class="auth-wrapper">
    <div class="auth-card">
        <a href="index.php" class="close-btn">&times;</a>
        <h1>Create Account</h1>
        <p>Join QuickTable and start exploring!</p>

        <?php if ($error): ?>
            <div style="color: #e74c3c; margin-bottom: 1rem;"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="example@email.com">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="Min 6 characters">
            </div>
            <button type="submit" class="auth-btn">Register</button>
        </form>

        <div class="switch-auth">
            Already have an account? <a href="login.php">Login</a>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>