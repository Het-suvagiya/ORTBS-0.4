<?php
require_once 'header.php';

if (!isLoggedIn() || isAdmin()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Fetch favorites
$stmt = $conn->prepare("SELECT r.* FROM restaurants r JOIN favorites f ON r.id = f.restaurant_id WHERE f.user_id = ? AND r.is_published = 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$favorites = $stmt->get_result();
$stmt->close();
?>

<div class="favorites-container" style="padding: 3rem 5%; min-height: 80vh;">
    <h1 style="margin-bottom: 2rem; font-family: 'Outfit', sans-serif;">My Saved Restaurants</h1>

    <div class="restaurant-grid"
        style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 2rem;">
        <?php if ($favorites->num_rows > 0): ?>
            <?php while ($rest = $favorites->fetch_assoc()): ?>
                <div class="rest-card"
                    style="background: #fff; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.05); position: relative;">
                    <button onclick="toggleFavorite(<?php echo $rest['id']; ?>, this)"
                        style="position: absolute; top: 1rem; right: 1rem; background: rgba(255,255,255,0.9); border: none; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #e74c3c; font-size: 1.2rem; z-index: 10;">
                        <i class="fas fa-heart"></i>
                    </button>
                    <div
                        style="height: 200px; background: url('<?php echo htmlspecialchars($rest['primary_image']); ?>') center/cover;">
                    </div>
                    <div style="padding: 1.5rem;">
                        <h3 style="margin: 0 0 0.5rem 0;">
                            <?php echo htmlspecialchars($rest['name']); ?>
                        </h3>
                        <p style="color: #7f8c8d; font-size: 0.9rem; margin-bottom: 1rem;"><i class="fas fa-map-marker-alt"
                                style="margin-right: 0.5rem;"></i>
                            <?php echo htmlspecialchars($rest['location']); ?>
                        </p>
                        <a href="book.php?id=<?php echo $rest['id']; ?>"
                            style="display: block; text-align: center; background: #2c3e50; color: #fff; padding: 0.8rem; border-radius: 8px; text-decoration: none; font-weight: 600;">Book
                            Now</a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="grid-column: span 3; text-align: center; padding: 4rem;">
                <i class="far fa-heart" style="font-size: 4rem; color: #bdc3c7; margin-bottom: 1rem;"></i>
                <h2>No saved restaurants yet</h2>
                <p style="color: #7f8c8d; margin-bottom: 2rem;">Explore and save your favorite dining spots!</p>
                <a href="index.php"
                    style="background: #27ae60; color: #fff; padding: 0.8rem 2rem; border-radius: 50px; text-decoration: none; font-weight: 600;">Browse
                    Restaurants</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function toggleFavorite(restId, btn) {
        fetch(`toggle_favorite.php?id=${restId}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'removed') {
                    btn.closest('.rest-card').fadeOut(300, function () { this.remove(); });
                    // Simple removal for vanilla JS
                    const card = btn.closest('.rest-card');
                    card.style.opacity = '0';
                    setTimeout(() => card.remove(), 300);
                }
            });
    }
</script>

<?php require_once 'footer.php'; ?>