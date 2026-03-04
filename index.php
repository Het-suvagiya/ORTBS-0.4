<?php
require_once 'header.php';

// Search parameters
$search_query = isset($_GET['q']) ? '%' . $_GET['q'] . '%' : '%';
$location_query = isset($_GET['location']) && !empty($_GET['location']) ? '%' . $_GET['location'] . '%' : '%';

// Fetch published restaurants with search filters
$sql = "SELECT * FROM restaurants WHERE status = 'approved' AND is_published = 1 AND (name LIKE ? OR cuisine LIKE ?) AND location LIKE ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $search_query, $search_query, $location_query);
$stmt->execute();
$restaurants = $stmt->get_result();
$stmt->close();

// Fetch user favorites if logged in
$user_favs = [];
if (isLoggedIn()) {
    $stmt = $conn->prepare("SELECT restaurant_id FROM favorites WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $user_favs[] = $row['restaurant_id'];
    }
    $stmt->close();
}
?>

<main class="hero"
    style="background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&q=80&w=2070'); background-size: cover; background-position: center; color: #fff; padding: 8rem 5%; text-align: center;">
    <h1 style="font-size: 3.5rem; margin-bottom: 1rem; font-family: 'Outfit', sans-serif;">Find Your Next Culinary
        Adventure</h1>
    <p style="font-size: 1.2rem; margin-bottom: 3rem; opacity: 0.9;">Real-time booking for the best restaurants in your
        city.</p>

    <form class="search-container" action="index.php" method="GET"
        style="max-width: 900px; margin: 0 auto; background: #fff; padding: 0.5rem; border-radius: 50px; display: flex; align-items: center; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
        <div class="search-input" style="flex: 1; display: flex; align-items: center; padding: 0 1.5rem;">
            <i class="fas fa-utensils" style="color: #95a5a6; margin-right: 0.8rem;"></i>
            <input type="text" name="q" placeholder="Restaurant name or cuisine..."
                value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
                style="width: 100%; border: none; outline: none; background: transparent; font-size: 1rem; padding: 0.8rem 0;">
        </div>
        <div class="search-input"
            style="flex: 1; display: flex; align-items: center; padding: 0 1.5rem; border-left: 1px solid #eee;">
            <i class="fas fa-location-dot" style="color: #95a5a6; margin-right: 0.8rem;"></i>
            <input type="text" name="location" placeholder="All Locations"
                value="<?php echo htmlspecialchars($_GET['location'] ?? ''); ?>"
                style="width: 100%; border: none; outline: none; background: transparent; font-size: 1rem; padding: 0.8rem 0;">
        </div>
        <button type="submit" class="search-btn"
            style="background: #27ae60; color: #fff; border: none; padding: 1rem 2.5rem; border-radius: 50px; font-weight: 700; cursor: pointer; transition: background 0.3s;"
            onmouseover="this.style.background='#219150'" onmouseout="this.style.background='#27ae60'">Find
            Tables</button>
    </form>
</main>

<section style="padding: 5rem 5%;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 3rem;">
        <div>
            <?php if (isset($_GET['q']) && !empty($_GET['q']) || isset($_GET['location']) && !empty($_GET['location'])): ?>
                <h2 style="font-size: 2.2rem; font-family: 'Outfit', sans-serif;">Search Results</h2>
                <p style="color: #7f8c8d;">
                    Showing results for
                    <?php if (!empty($_GET['q'])): ?>
                        "<strong><?php echo htmlspecialchars($_GET['q']); ?></strong>"
                    <?php endif; ?>
                    <?php if (!empty($_GET['q']) && !empty($_GET['location'])): ?> in <?php endif; ?>
                    <?php if (!empty($_GET['location'])): ?>
                        "<strong><?php echo htmlspecialchars($_GET['location']); ?></strong>"
                    <?php endif; ?>
                </p>
            <?php else: ?>
                <h2 style="font-size: 2.2rem; font-family: 'Outfit', sans-serif;">Popular Restaurants</h2>
                <p style="color: #7f8c8d;">The most booked spots this month</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="restaurant-grid"
        style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 2rem;">
        <?php if ($restaurants->num_rows > 0): ?>
            <?php while ($rest = $restaurants->fetch_assoc()): ?>
                <div class="rest-card"
                    style="background: #fff; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.05); position: relative; transition: transform 0.3s;"
                    onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='none'">

                    <!-- Favorite Toggle -->
                    <button onclick="toggleFavorite(<?php echo $rest['id']; ?>, this)"
                        style="position: absolute; top: 1rem; right: 1rem; background: rgba(255,255,255,0.9); border: none; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; color: <?php echo in_array($rest['id'], $user_favs) ? '#e74c3c' : '#bdc3c7'; ?>; font-size: 1.2rem; transition: color 0.3s; z-index: 10;">
                        <i class="<?php echo in_array($rest['id'], $user_favs) ? 'fas' : 'far'; ?> fa-heart"></i>
                    </button>

                    <div
                        style="height: 200px; background: url('<?php echo htmlspecialchars($rest['primary_image']); ?>') center/cover;">
                    </div>

                    <div style="padding: 1.5rem;">
                        <div
                            style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                            <h3 style="margin: 0; font-size: 1.25rem;"><?php echo htmlspecialchars($rest['name']); ?></h3>
                        </div>
                        <div
                            style="font-size: 0.9rem; color: #7f8c8d; display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                            <span><i class="fas fa-coins" style="margin-right: 5px;"></i> <span
                                    style="color: #27ae60; font-weight: 700;">₹<?php echo number_format($rest['avg_price'], 0); ?></span>
                                for two</span>
                        </div>
                        <p style="color: #7f8c8d; font-size: 0.9rem; margin-bottom: 1rem;"><i class="fas fa-map-marker-alt"
                                style="margin-right: 0.5rem;"></i><?php echo htmlspecialchars($rest['location']); ?></p>
                        <div style="margin-bottom: 1.5rem;">
                            <span
                                style="background: #f1f2f6; padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.8rem; color: #34495e;"><?php echo htmlspecialchars($rest['cuisine']); ?></span>
                        </div>
                        <a href="book.php?id=<?php echo $rest['id']; ?>"
                            style="display: block; text-align: center; background: #2c3e50; color: #fff; padding: 0.8rem; border-radius: 8px; text-decoration: none; font-weight: 600; transition: background 0.3s;"
                            onmouseover="this.style.background='#34495e'" onmouseout="this.style.background='#2c3e50'">Book a
                            Table</a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="text-align: center; grid-column: span 3; padding: 4rem 1rem;">
                <i class="fas fa-search" style="font-size: 3rem; color: #bdc3c7; margin-bottom: 1rem; display: block;"></i>
                <p style="color: #7f8c8d; font-size: 1.1rem; margin-bottom: 0.5rem;">No restaurants found.</p>
                <p style="color: #95a5a6; font-size: 0.9rem;">Try adjusting your search or location filters.</p>
                <?php if (isset($_GET['q']) || isset($_GET['location'])): ?>
                    <a href="index.php"
                        style="display: inline-block; margin-top: 1.5rem; color: #27ae60; text-decoration: none; font-weight: 600;"><i
                            class="fas fa-arrow-left"></i> View All Restaurants</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
    function toggleFavorite(restId, btn) {
        if (!<?php echo isLoggedIn() ? 'true' : 'false'; ?>) {
            openModal('login-modal');
            return;
        }

        fetch(`toggle_favorite.php?id=${restId}`)
            .then(response => response.json())
            .then(data => {
                const icon = btn.querySelector('i');
                if (data.status === 'added') {
                    icon.className = 'fas fa-heart';
                    btn.style.color = '#e74c3c';
                } else {
                    icon.className = 'far fa-heart';
                    btn.style.color = '#bdc3c7';
                }
            });
    }
</script>

<?php require_once 'footer.php'; ?>