<?php
// ============================================================
// MIS Shop - Checkout Form
// ============================================================

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// Force Login with Redirect URLs
if (!isLoggedIn()) {
    $_SESSION['redirect_url'] = 'checkout.php';
    setFlash('info', 'Please log in to complete your checkout.');
    header('Location: login.php');
    exit;
}

// Fetch Cart items to verify it's not empty
$result = mysqli_query($conn, "
    SELECT c.quantity, p.id as product_id, p.name, p.price, p.stock 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = " . $_SESSION['user_id']);

$cartItems = [];
while ($row = mysqli_fetch_assoc($result)) {
    $cartItems[] = $row;
}

if (empty($cartItems)) {
    setFlash('error', 'Your shopping cart is empty.');
    header('Location: cart.php');
    exit;
}

// Calculate total
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$pageTitle = 'Checkout';
require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-8">
    <div>
        <h1 class="font-display font-extrabold text-3xl sm:text-4xl text-slate-800 dark:text-slate-100 tracking-tight">
            Checkout</h1>
        <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Provide delivery and mock payment information</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        <!-- Forms (LG: col-span-8) -->
        <div class="lg:col-span-8 space-y-6">
            <form action="place_order.php" method="POST" class="space-y-6">
                <!-- Delivery Info Section -->
                <div
                    class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800/80 p-6 sm:p-8 space-y-5">
                    <h2
                        class="font-display font-extrabold text-lg text-slate-800 dark:text-slate-100 tracking-tight flex items-center gap-2">
                        <span
                            class="w-7 h-7 rounded-lg bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-sm font-bold">1</span>
                        Delivery Information
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label for="shipping_name"
                                class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Recipient
                                Full Name</label>
                            <input type="text" id="shipping_name" name="shipping_name" required
                                value="<?php echo htmlspecialchars($_SESSION['username']); ?>"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-sm">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="shipping_address"
                                class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Delivery
                                Address</label>
                            <textarea id="shipping_address" name="shipping_address" rows="3" required
                                placeholder="Street address, apartment, city, state, postal code"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-sm"></textarea>
                        </div>

                        <div class="sm:col-span-2">
                            <label for="shipping_phone"
                                class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Phone
                                Number</label>
                            <input type="tel" id="shipping_phone" name="shipping_phone" required
                                placeholder="e.g. +1 555-0199"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-sm">
                        </div>
                    </div>
                </div>



                <!-- Submit Button -->
                <button type="submit"
                    class="w-full h-12 flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white font-bold rounded-xl transition-all shadow-lg shadow-indigo-500/10 hover:shadow-indigo-500/20 active:scale-[0.99]">
                    Place Order
                </button>
            </form>
        </div>

        <!-- Order summary Sidebar (LG: col-span-4) -->
        <div
            class="lg:col-span-4 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800/80 shadow-sm p-6 space-y-6">
            <h3 class="font-display font-extrabold text-lg text-slate-800 dark:text-slate-100 tracking-tight">Order
                Details</h3>

            <div class="divide-y divide-slate-100 dark:divide-slate-800 max-h-60 overflow-y-auto pr-1">
                <?php foreach ($cartItems as $item): ?>
                    <div class="py-3.5 flex justify-between gap-4 text-xs sm:text-sm">
                        <div class="space-y-0.5">
                            <p class="font-semibold text-slate-800 dark:text-slate-200 line-clamp-1">
                                <?php echo htmlspecialchars($item['name']); ?></p>
                            <p class="text-slate-400 font-medium">Qty: <?php echo $item['quantity']; ?></p>
                        </div>
                        <span
                            class="font-bold text-slate-800 dark:text-slate-200 shrink-0">Rs.<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="border-t border-slate-100 dark:border-slate-800 pt-4 space-y-3.5 text-sm">
                <div class="flex justify-between text-slate-500">
                    <span>Subtotal</span>
                    <span
                        class="font-semibold text-slate-800 dark:text-slate-200">$<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="flex justify-between text-slate-500">
                    <span>Shipping</span>
                    <span class="font-semibold text-slate-800 dark:text-slate-200">Free</span>
                </div>
                <hr class="border-slate-100 dark:border-slate-800">
                <div class="flex justify-between font-display text-base">
                    <span class="font-bold text-slate-800 dark:text-slate-100">Grand Total</span>
                    <span
                        class="font-extrabold text-lg text-indigo-600 dark:text-indigo-400">$<?php echo number_format($subtotal, 2); ?></span>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>