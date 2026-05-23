<?php


$pageTitle = 'Home';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// Fetch categories for filtering
$categoriesResult = mysqli_query($conn, "SELECT * FROM categories ORDER BY name ASC");
$categories = $categoriesResult ? mysqli_fetch_all($categoriesResult, MYSQLI_ASSOC) : [];

// Build query for products
$categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$searchFilter = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE 1=1";

if ($categoryFilter > 0) {
    $sql .= " AND p.category_id = " . (int)$categoryFilter;
}

if (!empty($searchFilter)) {
    $search = mysqli_real_escape_string($conn, '%' . $searchFilter . '%');
    $sql .= " AND (p.name LIKE '$search' OR p.description LIKE '$search')";
}

$sql .= " ORDER BY p.created_at DESC";

$productsResult = mysqli_query($conn, $sql);
$products = $productsResult ? mysqli_fetch_all($productsResult, MYSQLI_ASSOC) : [];

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Banner Section -->
<?php if (empty($searchFilter) && $categoryFilter === 0): ?>
    <section
        class="mb-12 rounded-3xl overflow-hidden bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 text-white shadow-xl shadow-indigo-500/10 relative">
        <!-- Abstract shape decorations -->
        <div
            class="absolute inset-0 opacity-10 bg-[radial-gradient(circle_at_30%_20%,rgba(255,255,255,1)_0%,transparent_50%)]">
        </div>
        <div class="max-w-4xl mx-auto px-8 py-16 sm:py-20 text-center relative z-10 space-y-6">
            <span
                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md text-xs font-semibold uppercase tracking-wider">
                ✨ Summer Collections Live
            </span>
            <h1 class="font-display font-extrabold text-4xl sm:text-6xl tracking-tight leading-tight">
                Discover Premium Quality Products
            </h1>
            <p class="text-indigo-100 max-w-lg mx-auto text-base sm:text-lg font-light leading-relaxed">
                Shop the latest trends with super fast delivery, secure checkout, and dedicated 24/7 customer support.
            </p>
            <div class="pt-4 flex justify-center gap-4">
                <a href="#catalog"
                    class="px-6 py-3 bg-white text-indigo-700 font-semibold rounded-xl hover:shadow-lg hover:scale-105 active:scale-[0.99] transition-all">
                    Explore Catalog
                </a>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- Catalog Header & Search (Mobile) -->
<div id="catalog" class="scroll-mt-20 flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
    <div>
        <h2 class="font-display font-extrabold text-3xl text-slate-800 dark:text-slate-100 tracking-tight">
            <?php
            if ($categoryFilter > 0) {
                // Find category name
                $catName = 'Category';
                foreach ($categories as $cat) {
                    if ($cat['id'] === $categoryFilter) {
                        $catName = $cat['name'];
                        break;
                    }
                }
                echo htmlspecialchars($catName);
            } elseif (!empty($searchFilter)) {
                echo 'Search Results for "' . htmlspecialchars($searchFilter) . '"';
            } else {
                echo 'Trending Products';
            }
            ?>
        </h2>
        <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">
            Showing <?php echo count($products); ?> premium products
        </p>
    </div>

    <!-- Search Form (Mobile only) -->
    <div class="md:hidden w-full">
        <form action="index.php" method="GET" class="relative">
            <input type="text" name="search" placeholder="Search products..."
                value="<?php echo htmlspecialchars($searchFilter); ?>"
                class="w-full px-4 py-2.5 pl-10 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-sm">
            <span class="absolute left-3.5 top-3 text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </span>
        </form>
    </div>
</div>

<!-- Category Carousel Filter -->
<div class="mb-10 overflow-x-auto pb-3 -mx-4 px-4 scrollbar-none flex items-center gap-2">
    <a href="index.php<?php echo !empty($searchFilter) ? '?search=' . urlencode($searchFilter) : ''; ?>"
        class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-semibold transition-all border shrink-0 <?php echo $categoryFilter === 0 ? 'bg-indigo-600 text-white border-indigo-600 shadow-md shadow-indigo-500/10' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:border-slate-300 dark:hover:border-slate-700' ?>">
        All Categories
    </a>
    <?php foreach ($categories as $cat): ?>
        <a href="index.php?category=<?php echo $cat['id']; ?><?php echo !empty($searchFilter) ? '&search=' . urlencode($searchFilter) : ''; ?>"
            class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-semibold transition-all border shrink-0 <?php echo $categoryFilter === $cat['id'] ? 'bg-indigo-600 text-white border-indigo-600 shadow-md shadow-indigo-500/10' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:border-slate-300 dark:hover:border-slate-700' ?>">
            <?php echo htmlspecialchars($cat['name']); ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- Products Grid -->
<?php if (empty($products)): ?>
    <div
        class="text-center py-20 bg-white dark:bg-slate-900 rounded-3xl border border-dashed border-slate-200 dark:border-slate-800 max-w-md mx-auto space-y-4">
        <div
            class="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto text-slate-400">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M2.25 13.5h3.86a2.25 2.25 0 012.008 1.24l.885 1.77a2.25 2.25 0 002.007 1.24h1.98a2.25 2.25 0 002.007-1.24l.885-1.77a2.25 2.25 0 012.007-1.24h3.86m-18 0h18m-18 0l-1.08 7.5a1.875 1.875 0 001.875 2h15.75a1.875 1.875 0 001.875-2l-1.08-7.5m-18 0l1.08-7.5A1.875 1.875 0 015.625 4.5h12.75a1.875 1.875 0 011.875 1.5l1.08 7.5">
                </path>
            </svg>
        </div>
        <div>
            <h3 class="font-bold text-lg text-slate-800 dark:text-slate-200">No products found</h3>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Try resetting your filters or keyword query.</p>
        </div>
        <a href="index.php"
            class="inline-block px-5 py-2 bg-indigo-600 text-white font-semibold rounded-xl text-sm shadow-md">
            Clear Filters
        </a>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
        <?php foreach ($products as $product): ?>
            <div
                class="group bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 overflow-hidden shadow-sm hover:shadow-xl hover:border-slate-300 dark:hover:border-slate-700 transition-all duration-300 flex flex-col h-full">
                <!-- Product Image -->
                <a href="product.php?id=<?php echo $product['id']; ?>"
                    class="block aspect-square w-full relative overflow-hidden bg-slate-100 dark:bg-slate-950">
                    <?php if (!empty($product['image']) && file_exists(__DIR__ . '/uploads/' . $product['image'])): ?>
                        <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>"
                            alt="<?php echo htmlspecialchars($product['name']); ?>"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    <?php else: ?>
                        <!-- Premium CSS placeholder -->
                        <div
                            class="w-full h-full bg-gradient-to-tr from-indigo-500/10 via-purple-500/10 to-pink-500/10 flex items-center justify-center text-slate-400 group-hover:scale-105 transition-transform duration-500">
                            <svg class="w-12 h-12 stroke-[1.2] text-indigo-400/50" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375 0 11-.75 0 .375 0 01.75 0z">
                                </path>
                            </svg>
                        </div>
                    <?php endif; ?>

                    <!-- Stock Alert Badge -->
                    <?php if ($product['stock'] <= 0): ?>
                        <span
                            class="absolute top-3 right-3 px-2.5 py-1 rounded-full bg-slate-900/80 text-white text-[10px] font-extrabold uppercase tracking-wider backdrop-blur-sm">
                            Out of Stock
                        </span>
                    <?php elseif ($product['stock'] <= 5): ?>
                        <span
                            class="absolute top-3 right-3 px-2.5 py-1 rounded-full bg-amber-500/90 text-white text-[10px] font-extrabold uppercase tracking-wider backdrop-blur-sm">
                            Only <?php echo $product['stock']; ?> Left
                        </span>
                    <?php endif; ?>
                </a>

                <!-- Details -->
                <div class="p-5 flex flex-col flex-grow">
                    <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-1">
                        <?php echo htmlspecialchars($product['category_name']); ?>
                    </span>
                    <h3
                        class="font-display font-bold text-lg text-slate-800 dark:text-slate-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors line-clamp-1 mb-2">
                        <a href="product.php?id=<?php echo $product['id']; ?>">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </a>
                    </h3>
                    <p class="text-slate-500 dark:text-slate-400 text-xs line-clamp-2 leading-relaxed mb-4">
                        <?php echo htmlspecialchars($product['description']); ?>
                    </p>

                    <!-- Price and Button -->
                    <div class="flex items-center justify-between mt-auto pt-4 border-t border-slate-100 dark:border-slate-800">
                        <span class="font-display font-extrabold text-xl text-slate-800 dark:text-slate-100">
                            Rs.<?php echo number_format($product['price'], 2); ?>
                        </span>

                        <?php if ($product['stock'] > 0): ?>
                            <form action="cart.php?action=add" method="POST" class="inline-block">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit"
                                    class="p-2 rounded-xl bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-600 hover:text-white dark:hover:bg-indigo-500 dark:hover:text-white active:scale-95 transition-all"
                                    title="Add to Cart">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"></path>
                                    </svg>
                                </button>
                            </form>
                        <?php else: ?>
                            <button disabled
                                class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-600 cursor-not-allowed"
                                title="Out of Stock">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636">
                                    </path>
                                </svg>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>