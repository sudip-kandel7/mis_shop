<?php
// ============================================================
// MIS Shop - Product Details
// ============================================================

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$result = mysqli_query($conn, "SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = $id");
$product = mysqli_fetch_assoc($result);

if (!$product) {
    setFlash('error', 'Product not found.');
    header('Location: index.php');
    exit;
}

$pageTitle = $product['name'];
require_once __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumbs -->
<nav class="flex mb-8 text-xs sm:text-sm font-medium text-slate-500 dark:text-slate-400 gap-2 items-center">
    <a href="index.php" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Home</a>
    <svg class="w-3.5 h-3.5 text-slate-300 dark:text-slate-700" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
    </svg>
    <a href="index.php?category=<?php echo $product['category_id']; ?>" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
        <?php echo htmlspecialchars($product['category_name']); ?>
    </a>
    <svg class="w-3.5 h-3.5 text-slate-300 dark:text-slate-700" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
    </svg>
    <span class="text-slate-800 dark:text-slate-200 truncate max-w-[150px] sm:max-w-xs"><?php echo htmlspecialchars($product['name']); ?></span>
</nav>

<!-- Product Details Grid -->
<div class="grid grid-cols-1 md:grid-cols-12 gap-8 lg:gap-12 bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800/80 shadow-sm">

    <!-- Image Section -->
    <div class="md:col-span-6 flex items-center justify-center bg-slate-50 dark:bg-slate-950 rounded-2xl overflow-hidden aspect-square border border-slate-100 dark:border-slate-800/50">
        <?php if (!empty($product['image']) && file_exists(__DIR__ . '/uploads/' . $product['image'])): ?>
            <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>"
                class="w-full h-full object-cover">
        <?php else: ?>
            <div class="w-full h-full bg-gradient-to-tr from-indigo-500/10 via-purple-500/10 to-pink-500/10 flex items-center justify-center text-slate-400">
                <svg class="w-24 h-24 stroke-[1.2] text-indigo-400/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375 0 11-.75 0 .375 0 01.75 0z"></path>
                </svg>
            </div>
        <?php endif; ?>
    </div>

    <!-- Product Info Section -->
    <div class="md:col-span-6 flex flex-col justify-between py-2">
        <div class="space-y-6">
            <!-- Badges -->
            <div class="flex items-center gap-2">
                <span class="inline-flex px-3 py-1 rounded-full bg-indigo-50 dark:bg-indigo-950/80 border border-indigo-100 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300 text-xs font-semibold uppercase tracking-wider">
                    <?php echo htmlspecialchars($product['category_name']); ?>
                </span>

                <?php if ($product['stock'] <= 0): ?>
                    <span class="inline-flex px-3 py-1 rounded-full bg-rose-50 dark:bg-rose-950/80 border border-rose-100 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs font-semibold uppercase tracking-wider">
                        Out of Stock
                    </span>
                <?php elseif ($product['stock'] <= 5): ?>
                    <span class="inline-flex px-3 py-1 rounded-full bg-amber-50 dark:bg-amber-950/80 border border-amber-100 dark:border-amber-800 text-amber-700 dark:text-amber-300 text-xs font-semibold uppercase tracking-wider">
                        Low Stock: <?php echo $product['stock']; ?> left!
                    </span>
                <?php else: ?>
                    <span class="inline-flex px-3 py-1 rounded-full bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-100 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 text-xs font-semibold uppercase tracking-wider">
                        In Stock
                    </span>
                <?php endif; ?>
            </div>

            <!-- Title & Price -->
            <div class="space-y-2">
                <h1 class="font-display font-extrabold text-3xl sm:text-4xl text-slate-800 dark:text-slate-100 tracking-tight leading-tight">
                    <?php echo htmlspecialchars($product['name']); ?>
                </h1>
                <p class="font-display font-extrabold text-2xl sm:text-3xl text-slate-800 dark:text-slate-100">
                    Rs.<?php echo number_format($product['price'], 2); ?>
                </p>
            </div>

            <!-- Description -->
            <div class="space-y-2.5">
                <h3 class="font-bold text-sm text-slate-800 dark:text-slate-200 tracking-wide uppercase">Description</h3>
                <p class="text-slate-600 dark:text-slate-400 text-sm sm:text-base leading-relaxed whitespace-pre-line">
                    <?php echo htmlspecialchars($product['description']); ?>
                </p>
            </div>
        </div>

        <!-- Add to Cart Form -->
        <div class="mt-8 pt-6 border-t border-slate-100 dark:border-slate-800">
            <?php if ($product['stock'] > 0): ?>
                <form action="cart.php?action=add" method="POST" class="space-y-4">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">

                    <div class="flex items-center gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label for="quantity" class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Quantity</label>
                            <div class="flex items-center border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden bg-slate-50 dark:bg-slate-900 w-32 h-11">
                                <button type="button" onclick="adjustQty(-1)" class="w-10 h-full flex items-center justify-center font-bold text-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 focus:outline-none transition-colors">-</button>
                                <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" readonly
                                    class="w-12 h-full text-center bg-transparent font-semibold text-sm focus:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                <button type="button" onclick="adjustQty(1)" class="w-10 h-full flex items-center justify-center font-bold text-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 focus:outline-none transition-colors">+</button>
                            </div>
                        </div>
                    </div>

                    <button type="submit"
                        class="w-full h-12 flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white font-bold rounded-xl transition-all shadow-lg shadow-indigo-500/10 hover:shadow-indigo-500/20 active:scale-[0.99]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                        Add to Shopping Cart
                    </button>
                </form>
            <?php else: ?>
                <button disabled
                    class="w-full h-12 flex items-center justify-center gap-2 bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-600 font-bold rounded-xl cursor-not-allowed">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                    </svg>
                    Out of Stock
                </button>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
    function adjustQty(val) {
        const qtyInput = document.getElementById('quantity');
        const maxVal = parseInt(qtyInput.getAttribute('max'));
        let currentVal = parseInt(qtyInput.value);

        currentVal += val;
        if (currentVal < 1) currentVal = 1;
        if (currentVal > maxVal) currentVal = maxVal;

        qtyInput.value = currentVal;
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>