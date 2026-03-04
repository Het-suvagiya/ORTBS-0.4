<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickTable - Find Your Next Culinary Adventure</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@400;600;700&display=swap"
        rel="stylesheet">
</head>

<body>
    <nav>
        <a href="<?php echo BASE_URL; ?>index.php" class="logo">QuickTable</a>
        <div class="nav-links">
            <?php if (isLoggedIn()): ?>
                <?php
                // Fetch user data for the avatar if not already available in the current scope
                if (!isset($header_user)) {
                    $role_header = getUserRole();
                    if ($role_header == ROLE_ADMIN) {
                        $stmt_header = $conn->prepare("SELECT a_email as email, a_firstname as first_name, a_image as profile_image FROM tbl_admin WHERE a_id = ?");
                    } elseif ($role_header == ROLE_MANAGER) {
                        $stmt_header = $conn->prepare("SELECT m_email as email, m_firstname as first_name, m_image as profile_image FROM tbl_manager WHERE m_id = ?");
                    } else {
                        $stmt_header = $conn->prepare("SELECT u_email as email, u_firstname as first_name, u_image as profile_image FROM tbl_users WHERE u_id = ?");
                    }
                    $stmt_header->bind_param("i", $_SESSION['user_id']);
                    $stmt_header->execute();
                    $header_user = $stmt_header->get_result()->fetch_assoc();
                    $stmt_header->close();
                }

                $avatar_html = '';
                if (!empty($header_user['profile_image'])) {
                    $avatar_html = '<img src="' . BASE_URL . htmlspecialchars($header_user['profile_image']) . '" style="width: 42px; height: 42px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); vertical-align: middle;">';
                } else {
                    $initial = strtoupper(substr($header_user['email'] ?? 'U', 0, 1));
                    $avatar_html = '<div style="width: 42px; height: 42px; border-radius: 50%; background: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 18px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); vertical-align: middle;">' . $initial . '</div>';
                }

                $profile_link = isAdmin() ? BASE_URL . 'admin/profile.php' : BASE_URL . 'profile.php';
                ?>
                <?php if (isAdmin()): ?>
                    <a href="<?php echo BASE_URL; ?>admin/dashboard.php">Dashboard</a>
                    <a href="<?php echo BASE_URL; ?>admin/applications.php">Applications</a>
                    <a href="<?php echo BASE_URL; ?>index.php">Explore</a>
                <?php elseif (isManager()): ?>
                    <a href="<?php echo BASE_URL; ?>manager/dashboard.php">Dashboard</a>
                    <a href="<?php echo BASE_URL; ?>manager/setup.php">My Restaurant</a>
                    <a href="<?php echo BASE_URL; ?>favorites.php">Saved</a>
                    <a href="<?php echo BASE_URL; ?>index.php">Explore</a>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>index.php">Browse</a>
                    <a href="<?php echo BASE_URL; ?>my_bookings.php">My Bookings</a>
                    <a href="<?php echo BASE_URL; ?>register_restaurant.php">Register Restaurant</a>
                    <a href="<?php echo BASE_URL; ?>favorites.php">Saved</a>
                <?php endif; ?>
                <a href="<?php echo BASE_URL; ?>logout.php" class="logout-btn">Logout</a>
                <a href="<?php echo $profile_link; ?>"
                    style="padding: 0; margin-left: 15px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; vertical-align: middle;"><?php echo $avatar_html; ?></a>
            <?php else: ?>
                <a href="javascript:void(0)" onclick="openModal('login-modal')">Login</a>
                <a href="javascript:void(0)" onclick="openModal('register-modal')" class="register-btn">Register</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Login Modal -->
    <div id="login-modal" class="modal-overlay">
        <div class="modal-card">
            <span class="modal-close" onclick="closeModal('login-modal')">&times;</span>
            <h2>Welcome Back</h2>
            <p>Login to your QuickTable account</p>
            <div id="login-error"
                style="color: #e74c3c; margin-bottom: 1rem; display: none; font-size: 0.9rem; background: #fdf2f2; padding: 0.8rem; border-radius: 8px;">
            </div>
            <form onsubmit="handleAuth(event, '<?php echo BASE_URL; ?>login.php', 'login-modal', 'login-error')">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="email@example.com" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="auth-btn">Login Now</button>
            </form>
            <div class="switch-auth">
                Don't have an account? <a href="javascript:void(0)"
                    onclick="closeModal('login-modal'); openModal('register-modal')">Register here</a>
            </div>
        </div>
    </div>

    <!-- Register Modal -->
    <div id="register-modal" class="modal-overlay">
        <div class="modal-card">
            <span class="modal-close" onclick="closeModal('register-modal')">&times;</span>
            <h2>Join QuickTable</h2>
            <p>Create your account in seconds</p>
            <div id="register-error"
                style="color: #e74c3c; margin-bottom: 1rem; display: none; font-size: 0.9rem; background: #fdf2f2; padding: 0.8rem; border-radius: 8px;">
            </div>
            <form
                onsubmit="handleAuth(event, '<?php echo BASE_URL; ?>register.php', 'register-modal', 'register-error')">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="email@example.com" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Min 6 characters" required>
                </div>
                <button type="submit" class="auth-btn">Create Account</button>
            </form>
            <div class="switch-auth">
                Already have an account? <a href="javascript:void(0)"
                    onclick="closeModal('register-modal'); openModal('login-modal')">Login here</a>
            </div>
        </div>
    </div>

    <script>
        function openModal(id) {
            document.getElementById(id).classList.add('active');
            document.body.classList.add('modal-open');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
            document.body.classList.remove('modal-open');
        }

        async function handleAuth(event, url, modalId, errorId) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            const errorDiv = document.getElementById(errorId);
            const submitBtn = form.querySelector('button');

            errorDiv.style.display = 'none';
            submitBtn.disabled = true;
            submitBtn.innerText = 'Processing...';

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    window.location.href = data.redirect.startsWith('http') ? data.redirect : '<?php echo BASE_URL; ?>' + data.redirect;
                } else {
                    errorDiv.innerText = data.error || 'Authentication failed.';
                    errorDiv.style.display = 'block';
                    submitBtn.disabled = false;
                    submitBtn.innerText = modalId.includes('login') ? 'Login Now' : 'Create Account';
                }
            } catch (error) {
                console.error('Error:', error);
                errorDiv.innerText = 'An unexpected error occurred.';
                errorDiv.style.display = 'block';
                submitBtn.disabled = false;
                submitBtn.innerText = 'Try Again';
            }
        }

        // Close modal on outside click
        window.onclick = function (event) {
            if (event.target.classList.contains('modal-overlay')) {
                const overlay = event.target;
                overlay.classList.remove('active');
                document.body.classList.remove('modal-open');
            }
        }

        // Auto-open modal if URL contains ?auth=login or ?auth=register
        window.addEventListener('DOMContentLoaded', () => {
            const params = new URLSearchParams(window.location.search);
            if (params.get('auth') === 'login') openModal('login-modal');
            if (params.get('auth') === 'register') openModal('register-modal');
        });
    </script>