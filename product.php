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

// -----------------------------------------------------------
// Handle Review Submission
// -----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'submit_review' && isLoggedIn() && !isOwner()) {
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
        $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
        $uid = $_SESSION['user_id'];

        // Check if user purchased this product
        $chk = mysqli_query($conn, "
            SELECT COUNT(*) as cnt FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            WHERE o.user_id = $uid AND oi.product_id = $id
            AND o.status IN ('paid', 'shipped', 'delivered')
        ");
        $purch = mysqli_fetch_assoc($chk);

        if (!$purch || $purch['cnt'] == 0) {
            setFlash('error', 'You can only review products you have purchased.');
        } elseif (mysqli_num_rows(mysqli_query($conn, "SELECT id FROM reviews WHERE product_id = $id AND user_id = $uid LIMIT 1")) > 0) {
            setFlash('error', 'You have already reviewed this product. Use the edit form below to update your review.');
        } elseif ($rating < 1 || $rating > 5) {
            setFlash('error', 'Please select a rating between 1 and 5.');
        } elseif (empty($comment)) {
            setFlash('error', 'Please write a comment.');
        } else {
            $comment = mysqli_real_escape_string($conn, $comment);
            mysqli_query($conn, "INSERT INTO reviews (product_id, user_id, rating, comment) VALUES ($id, $uid, $rating, '$comment')");
            setFlash('success', 'Your review has been submitted.');
        }
        header('Location: product.php?id=' . $id);
        exit;
    }

    // Update Existing Review
    if ($_POST['action'] === 'update_review' && isLoggedIn() && !isOwner()) {
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
        $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
        $uid = $_SESSION['user_id'];

        if ($rating < 1 || $rating > 5) {
            setFlash('error', 'Please select a rating between 1 and 5.');
        } elseif (empty($comment)) {
            setFlash('error', 'Please write a comment.');
        } else {
            $comment = mysqli_real_escape_string($conn, $comment);
            mysqli_query($conn, "UPDATE reviews SET rating = $rating, comment = '$comment' WHERE product_id = $id AND user_id = $uid");
            setFlash('success', 'Your review has been updated.');
        }
        header('Location: product.php?id=' . $id);
        exit;
    }

    // Owner Reply to Review
    if ($_POST['action'] === 'reply_review' && isOwner()) {
        $review_id = isset($_POST['review_id']) ? (int)$_POST['review_id'] : 0;
        $reply = isset($_POST['reply']) ? trim($_POST['reply']) : '';

        if ($review_id > 0 && !empty($reply)) {
            $reply = mysqli_real_escape_string($conn, $reply);
            // Check if reply already exists
            $existing = mysqli_query($conn, "SELECT id FROM review_replies WHERE review_id = $review_id");
            if (mysqli_num_rows($existing) > 0) {
                mysqli_query($conn, "UPDATE review_replies SET reply = '$reply' WHERE review_id = $review_id");
            } else {
                mysqli_query($conn, "INSERT INTO review_replies (review_id, reply) VALUES ($review_id, '$reply')");
            }
            setFlash('success', 'Reply submitted.');
        } else {
            setFlash('error', 'Reply cannot be empty.');
        }
        header('Location: product.php?id=' . $id);
        exit;
    }

    // Owner Delete Review (soft delete)
    if ($_POST['action'] === 'delete_review' && isOwner()) {
        $review_id = isset($_POST['review_id']) ? (int)$_POST['review_id'] : 0;
        if ($review_id > 0) {
            mysqli_query($conn, "UPDATE reviews SET is_deleted = 1 WHERE id = $review_id");
            setFlash('success', 'Review has been removed.');
        }
        header('Location: product.php?id=' . $id);
        exit;
    }
}

// Check if current user has purchased this product
$hasPurchased = false;
$alreadyReviewed = false;
if (isLoggedIn() && !isOwner()) {
    $uid = $_SESSION['user_id'];
    $purchasedResult = mysqli_query($conn, "
        SELECT COUNT(*) as cnt FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE o.user_id = $uid AND oi.product_id = $id
        AND o.status IN ('paid', 'shipped', 'delivered')
    ");
    $purchasedRow = mysqli_fetch_assoc($purchasedResult);
    $hasPurchased = ($purchasedRow['cnt'] > 0);

    $existingReview = mysqli_query($conn, "SELECT id, rating, comment FROM reviews WHERE product_id = $id AND user_id = $uid LIMIT 1");
    $alreadyReviewed = (mysqli_num_rows($existingReview) > 0);
    $myReview = $alreadyReviewed ? mysqli_fetch_assoc($existingReview) : null;
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
        <?php if (!empty($product['image']) && file_exists(__DIR__ . '/img/' . $product['image'])): ?>
            <img src="img/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>"
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
            <?php if (isLoggedIn() && !isOwner()): ?>
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

                        <div class="grid grid-cols-2 gap-3">
                            <button type="submit"
                                class="h-12 flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white font-bold rounded-xl transition-all shadow-lg shadow-indigo-500/10 hover:shadow-indigo-500/20 active:scale-[0.99]">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                Add to Cart
                            </button>
                            <button type="submit" formaction="buy_now.php"
                                class="h-12 flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-500 dark:bg-emerald-500 dark:hover:bg-emerald-600 text-white font-bold rounded-xl transition-all shadow-lg shadow-emerald-500/10 hover:shadow-emerald-500/20 active:scale-[0.99]">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                Buy Now
                            </button>
                        </div>
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
            <?php elseif (!isLoggedIn()): ?>
                <a href="login.php"
                    class="w-full h-12 flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white font-bold rounded-xl transition-all shadow-lg shadow-indigo-500/10 hover:shadow-indigo-500/20 active:scale-[0.99] text-center no-underline">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    Login to Shop
                </a>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- ============================================================
     Reviews & Ratings Section
     ============================================================ -->
<div class="mt-12 space-y-8">
    <?php if (isOwner()): ?>
        <div class="px-4 py-2 rounded-lg bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 text-xs font-semibold inline-block">
            &#10003; Owner mode active — you can reply to and manage reviews
        </div>
    <?php endif; ?>
    <div class="flex items-center justify-between">
        <div>
            <h2 class="font-display font-extrabold text-2xl sm:text-3xl text-slate-800 dark:text-slate-100 tracking-tight">
                Customer Reviews
            </h2>
            <?php
            $countResult = mysqli_query($conn, "SELECT COUNT(*) as cnt, AVG(rating) as avg_rating FROM reviews WHERE product_id = $id AND is_deleted = 0");
            $reviewStats = mysqli_fetch_assoc($countResult);
            $reviewCount = $reviewStats['cnt'] ?? 0;
            $avgRating = $reviewStats['avg_rating'] ?? 0;
            ?>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">
                <?php echo $reviewCount; ?> review<?php echo $reviewCount !== 1 ? 's' : ''; ?>
                <?php if ($reviewCount > 0): ?>
                    &middot; Average: <?php echo number_format($avgRating, 1); ?>/5
                <?php endif; ?>
            </p>
        </div>
        <?php if ($reviewCount > 0): ?>
            <div class="flex items-center gap-1">
                <?php
                $avgRounded = round($avgRating);
                for ($i = 1; $i <= 5; $i++):
                ?>
                    <svg class="w-5 h-5 <?php echo $i <= $avgRounded ? 'text-amber-400' : 'text-slate-200 dark:text-slate-700'; ?>" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Review Submission Form (Customers only — must have purchased) -->
    <?php if (isLoggedIn() && !isOwner()): ?>
        <?php if ($hasPurchased && !$alreadyReviewed): ?>
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 p-6">
                <h3 class="font-bold text-slate-800 dark:text-slate-200 mb-4">Write a Review</h3>
                <form action="product.php?id=<?php echo $id; ?>" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="submit_review">
                    <div>
                        <label class="block text-sm font-semibold text-slate-600 dark:text-slate-400 mb-2">Rating</label>
                        <div class="flex items-center gap-1 star-rating" onmouseleave="hoverRating(0)">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <button type="button" onclick="setRating(<?php echo $i; ?>)" onmouseenter="hoverRating(<?php echo $i; ?>)" class="star-btn text-3xl leading-none text-slate-200 dark:text-slate-700 transition-colors focus:outline-none" data-value="<?php echo $i; ?>">&#9733;</button>
                            <?php endfor; ?>
                            <input type="hidden" name="rating" id="rating-input" value="0">
                        </div>
                    </div>
                    <div>
                        <label for="comment" class="block text-sm font-semibold text-slate-600 dark:text-slate-400 mb-2">Your Review</label>
                        <textarea id="comment" name="comment" rows="3" required
                            placeholder="Share your thoughts about this product..."
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-sm"></textarea>
                    </div>
                    <button type="submit"
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white font-bold rounded-xl transition-all shadow-md text-sm">
                        Submit Review
                    </button>
                </form>
            </div>
        <?php elseif ($alreadyReviewed && $myReview): ?>
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 p-6">
                <h3 class="font-bold text-slate-800 dark:text-slate-200 mb-4">Edit Your Review</h3>
                <form action="product.php?id=<?php echo $id; ?>" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="update_review">
                    <div>
                        <label class="block text-sm font-semibold text-slate-600 dark:text-slate-400 mb-2">Rating</label>
                        <div class="flex items-center gap-1 star-rating" onmouseleave="hoverRating(0)">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <button type="button" onclick="setRating(<?php echo $i; ?>)" onmouseenter="hoverRating(<?php echo $i; ?>)" class="star-btn text-3xl leading-none text-slate-200 dark:text-slate-700 transition-colors focus:outline-none" data-value="<?php echo $i; ?>">&#9733;</button>
                            <?php endfor; ?>
                            <input type="hidden" name="rating" id="rating-input" value="<?php echo $myReview['rating']; ?>">
                        </div>
                    </div>
                    <div>
                        <label for="comment" class="block text-sm font-semibold text-slate-600 dark:text-slate-400 mb-2">Your Review</label>
                        <textarea id="comment" name="comment" rows="3" required
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-sm"><?php echo htmlspecialchars($myReview['comment']); ?></textarea>
                    </div>
                    <button type="submit"
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white font-bold rounded-xl transition-all shadow-md text-sm">
                        Update Review
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 p-6 text-center">
                <p class="text-slate-500 dark:text-slate-400 text-sm">You can only review products you have purchased.</p>
            </div>
        <?php endif; ?>
    <?php elseif (!isLoggedIn()): ?>
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 p-6 text-center">
            <p class="text-slate-500 dark:text-slate-400 text-sm">
                <a href="login.php" class="text-indigo-600 dark:text-indigo-400 font-semibold hover:underline">Log in</a> to leave a review.
            </p>
        </div>
    <?php endif; ?>

    <!-- Reviews List -->
    <div class="space-y-4">
        <?php
        $reviewsQuery = mysqli_query($conn, "
            SELECT r.*, u.username 
            FROM reviews r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.product_id = $id 
            ORDER BY r.created_at DESC
        ");
        $hasReviews = false;
        while ($review = mysqli_fetch_assoc($reviewsQuery)):
            $hasReviews = true;
            $isDeleted = $review['is_deleted'];
        ?>
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 p-6 <?php echo $isDeleted ? 'opacity-70' : ''; ?>">
                <?php if ($isDeleted): ?>
                    <div class="flex items-center gap-3 text-slate-400 dark:text-slate-500 italic">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m0 0v2m0-2h2m-2 0H10m9.364-7.364A9 9 0 1110.636 4.636M18 9a9 9 0 00-9-9m2.5 3.5L12 3l1.5 1.5"/>
                        </svg>
                        <span class="text-sm font-medium">This review has been removed by the owner.</span>
                    </div>
                <?php else: ?>
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-indigo-400 to-purple-500 flex items-center justify-center text-white text-xs font-bold shrink-0">
                                <?php echo strtoupper(substr($review['username'], 0, 2)); ?>
                            </div>
                            <div>
                                <span class="font-semibold text-slate-800 dark:text-slate-200 text-sm"><?php echo htmlspecialchars($review['username']); ?></span>
                                <div class="flex items-center gap-0.5 mt-0.5">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <svg class="w-4 h-4 <?php echo $i <= $review['rating'] ? 'text-amber-400' : 'text-slate-200 dark:text-slate-700'; ?>" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                        <span class="text-xs text-slate-400 shrink-0"><?php echo date('M j, Y', strtotime($review['created_at'])); ?></span>
                    </div>
                    <p class="mt-3 text-slate-600 dark:text-slate-400 text-sm leading-relaxed"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>

                    <!-- Owner Reply -->
                    <?php
                    $replyResult = mysqli_query($conn, "SELECT * FROM review_replies WHERE review_id = " . $review['id']);
                    $reply = mysqli_fetch_assoc($replyResult);
                    ?>
                    <?php if ($reply): ?>
                        <div class="mt-4 ml-6 pl-4 border-l-2 border-indigo-200 dark:border-indigo-800">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Owner Response</span>
                            </div>
                            <p class="text-sm text-slate-600 dark:text-slate-400"><?php echo nl2br(htmlspecialchars($reply['reply'])); ?></p>
                            <p class="text-xs text-slate-400 mt-1"><?php echo date('M j, Y', strtotime($reply['created_at'])); ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Owner Actions (Reply / Delete) -->
                    <?php if (isOwner()): ?>
                        <div class="mt-4 flex items-center gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                            <button type="button" onclick="toggleReplyForm(<?php echo $review['id']; ?>)"
                                class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline"><?php echo $reply ? 'Edit Reply' : 'Reply'; ?></button>
                            <form action="product.php?id=<?php echo $id; ?>" method="POST" onsubmit="return confirm('Delete this review?');" class="inline">
                                <input type="hidden" name="action" value="delete_review">
                                <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                <button type="submit" class="text-xs font-semibold text-rose-600 dark:text-rose-400 hover:underline">Delete</button>
                            </form>
                        </div>
                        <!-- Reply Form (hidden by default) -->
                        <form id="reply-form-<?php echo $review['id']; ?>" action="product.php?id=<?php echo $id; ?>" method="POST" class="mt-3 space-y-2 <?php echo $reply ? '' : 'hidden'; ?>">
                            <input type="hidden" name="action" value="reply_review">
                            <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                            <textarea name="reply" rows="2" required
                                placeholder="Write your reply..."
                                class="w-full px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-sm"><?php echo $reply ? htmlspecialchars($reply['reply']) : ''; ?></textarea>
                            <div class="flex items-center gap-2">
                                <button type="submit"
                                    class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-lg transition-all">Submit Reply</button>
                                <?php if ($reply): ?>
                                    <button type="button" onclick="toggleReplyForm(<?php echo $review['id']; ?>)"
                                        class="px-4 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-bold rounded-lg transition-all">Cancel</button>
                                <?php endif; ?>
                            </div>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
        <?php if (!$hasReviews): ?>
            <div class="text-center py-12 bg-white dark:bg-slate-900 rounded-2xl border border-dashed border-slate-200 dark:border-slate-800">
                <p class="text-slate-500 dark:text-slate-400 text-sm">No reviews yet. Be the first to review this product!</p>
            </div>
        <?php endif; ?>
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

    var selectedRating = 0;

    function setRating(val) {
        selectedRating = val;
        document.getElementById('rating-input').value = val;
        highlightStars(val);
    }

    function hoverRating(val) {
        highlightStars(val || selectedRating);
    }

    function highlightStars(val) {
        document.querySelectorAll('.star-btn').forEach(btn => {
            const v = parseInt(btn.getAttribute('data-value'));
            if (v <= val) {
                btn.classList.remove('text-slate-200', 'dark:text-slate-700');
                btn.classList.add('text-amber-400');
            } else {
                btn.classList.remove('text-amber-400');
                btn.classList.add('text-slate-200', 'dark:text-slate-700');
            }
        });
    }

    function toggleReplyForm(reviewId) {
        const form = document.getElementById('reply-form-' + reviewId);
        if (form) {
            form.classList.toggle('hidden');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        var input = document.getElementById('rating-input');
        if (input && parseInt(input.value) > 0) {
            setRating(parseInt(input.value));
        }
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>