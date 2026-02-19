<?php
require_once __DIR__ . '/includes/lang.php';
$pageTitle   = __('nav_marketplace');
$currentPage = 'marketplace';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/db.php';

$db = getDB();
$products = $db->query("SELECT * FROM marketplace_products ORDER BY rating DESC")->fetchAll();

// Get unique categories
$categories = array_unique(array_column($products, 'category'));
?>

<!-- ═══ Header Banner ═══ -->
<div class="panel animate-in" style="background:var(--gradient);color:#fff;margin-bottom:1.25rem;text-align:center;padding:2rem">
    <div style="font-size:2rem;margin-bottom:0.5rem">🛒</div>
    <h2 style="font-size:1.35rem;font-weight:800;margin-bottom:0.25rem"><?= __('marketplace_title') ?></h2>
    <p style="font-size:0.88rem;opacity:0.9"><?= __('marketplace_desc') ?></p>
</div>

<!-- ═══ Filters ═══ -->
<div class="filter-bar">
    <button class="filter-chip active" onclick="filterProducts('all',this)"><?= __('all') ?></button>
    <?php foreach ($categories as $cat): ?>
        <button class="filter-chip" onclick="filterProducts('<?= htmlspecialchars($cat) ?>',this)"><?= htmlspecialchars($cat) ?></button>
    <?php endforeach; ?>
    <div class="search-box" style="margin-left:auto">
        <input type="text" placeholder="<?= __('search_products') ?>" id="searchInput" oninput="searchProducts(this.value)">
    </div>
</div>

<!-- ═══ Product Grid ═══ -->
<div class="product-grid" id="productGrid">
    <?php
    $icons = [
        'Grains' => '🌾', 'Spices' => '🌶️', 'Oils' => '🫒', 'Fruits' => '🥭',
        'Agri Inputs' => '🧴', 'Fertilizers' => '🌿', 'Equipment' => '⚙️',
        'Seeds' => '🌱', 'Processed' => '🍯',
    ];
    foreach ($products as $p):
        $icon  = $icons[$p['category']] ?? '📦';
        $stars = str_repeat('⭐', round($p['rating']));
        $stock = $p['in_stock'] ? '<span style="color:var(--green);font-weight:600">' . __('in_stock') . '</span>' : '<span style="color:var(--red);font-weight:600">' . __('out_of_stock') . '</span>';
    ?>
    <div class="product-card animate-in" data-category="<?= htmlspecialchars($p['category']) ?>" data-name="<?= htmlspecialchars(strtolower($p['name'])) ?>">
        <div class="product-img"><?= $icon ?></div>
        <div class="product-body">
            <div class="product-cat"><?= htmlspecialchars($p['category']) ?></div>
            <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
            <div class="product-desc"><?= htmlspecialchars($p['description']) ?></div>
            <div class="product-footer" style="margin-bottom:0.5rem">
                <div>
                    <span class="product-price">₹<?= number_format($p['price'], 0) ?></span>
                    <span class="product-unit"><?= htmlspecialchars($p['unit']) ?></span>
                </div>
                <div class="product-rating"><?= $stars ?> <?= $p['rating'] ?></div>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;font-size:0.78rem">
                <div>
                    <div class="product-seller">🏪 <?= htmlspecialchars($p['seller']) ?></div>
                    <div class="product-loc">📍 <?= htmlspecialchars($p['location']) ?></div>
                </div>
                <div><?= $stock ?></div>
            </div>
            <div style="margin-top:0.75rem;display:flex;gap:0.5rem">
                <button class="btn btn-primary btn-sm" style="flex:1" onclick="toast('Order placed! (Demo)')"><?= __('order_now') ?></button>
                <button class="btn btn-outline btn-sm" onclick="toast('❤️')">❤️</button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if (empty($products)): ?>
<div class="empty-state"><div class="es-icon">🛒</div><?= __('no_products') ?></div>
<?php endif; ?>

<?php
$extraScripts = <<<'JS'
<script>
function filterProducts(cat, btn) {
    $$('.filter-chip').forEach(c => c.classList.remove('active'));
    if (btn) btn.classList.add('active');
    $$('.product-card').forEach(card => {
        card.style.display = (cat === 'all' || card.dataset.category === cat) ? '' : 'none';
    });
}

function searchProducts(q) {
    q = q.toLowerCase();
    $$('.product-card').forEach(card => {
        card.style.display = card.dataset.name.includes(q) ? '' : 'none';
    });
}
</script>
JS;
require_once __DIR__ . '/includes/footer.php';
?>
