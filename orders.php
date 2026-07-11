<?php
// ============================================================
// MIS Shop - Customer Order History
// ============================================================

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// Require login
requireLogin();

// Only customers can access orders
if (isOwner()) {
    setFlash('error', 'Access denied. This page is for customers only.');
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$orders = [];

// Fetch all user orders
$result = mysqli_query($conn, "SELECT * FROM orders WHERE user_id = $user_id ORDER BY created_at DESC");
$orders = [];
while ($order = mysqli_fetch_assoc($result)) {
    // Fetch items for each order
    $itemsResult = mysqli_query($conn, "
        SELECT oi.*, p.name as product_name, p.image 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = " . $order['id']);

    $order['items'] = [];
    while ($item = mysqli_fetch_assoc($itemsResult)) {
        $order['items'][] = $item;
    }
    $orders[] = $order;
}

$pageTitle = 'My Orders';
require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-8">
    <div>
        <h1 class="font-display font-extrabold text-3xl sm:text-4xl text-slate-800 dark:text-slate-100 tracking-tight">
            Order History</h1>
        <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Review status and details of your purchases</p>
    </div>

    <?php if (empty($orders)): ?>
    <!-- Empty State -->
    <div
        class="text-center py-24 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800/80 shadow-sm max-w-lg mx-auto space-y-6">
        <div
            class="w-20 h-20 rounded-full bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center mx-auto text-indigo-500">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01">
                </path>
            </svg>
        </div>
        <div class="space-y-2">
            <h3 class="font-display font-extrabold text-xl text-slate-800 dark:text-slate-200">No orders placed yet</h3>
            <p class="text-slate-500 dark:text-slate-400 text-sm max-w-xs mx-auto">Once you complete a purchase, your
                order history will show up here.</p>
        </div>
        <a href="index.php"
            class="inline-flex px-6 py-3 bg-indigo-600 hover:bg-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white font-bold rounded-xl shadow-md transition-all">
            Browse Products
        </a>
    </div>
    <?php else: ?>
    <div class="space-y-6">
        <?php foreach ($orders as $order): ?>
        <div
            class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800/80 shadow-sm overflow-hidden transition-all hover:shadow-md">
            <!-- Order Header -->
            <div
                class="p-6 bg-slate-50/50 dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-800 flex flex-wrap justify-between items-center gap-4">
                <div class="flex items-center gap-4">
                    <div>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Order ID</span>
                        <h3 class="font-display font-extrabold text-slate-800 dark:text-slate-100">
                            #<?php echo $order['id']; ?></h3>
                    </div>
                    <div class="h-8 w-px bg-slate-200 dark:bg-slate-800"></div>
                    <div>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Placed On</span>
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">
                            <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></p>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Total Price</span>
                        <p class="font-display font-extrabold text-base text-indigo-600 dark:text-indigo-400">
                            Rs.<?php echo number_format($order['total'], 2); ?></p>
                    </div>

                    <!-- Status Badges -->
                    <div>
                        <?php
                        $statusMap = [
                            'pending_payment' => ['label' => 'Pending Payment', 'class' => 'bg-amber-50 dark:bg-amber-950 border-amber-100 dark:border-amber-900 text-amber-700 dark:text-amber-300'],
                            'paid'           => ['label' => 'Paid',            'class' => 'bg-blue-50 dark:bg-blue-950 border-blue-100 dark:border-blue-900 text-blue-700 dark:text-blue-300'],
                            'shipped'        => ['label' => 'Shipped',         'class' => 'bg-indigo-50 dark:bg-indigo-950 border-indigo-100 dark:border-indigo-900 text-indigo-700 dark:text-indigo-300'],
                            'delivered'      => ['label' => 'Delivered',       'class' => 'bg-emerald-50 dark:bg-emerald-950 border-emerald-100 dark:border-emerald-900 text-emerald-700 dark:text-emerald-300'],
                            'failed'         => ['label' => 'Failed',          'class' => 'bg-rose-50 dark:bg-rose-950 border-rose-100 dark:border-rose-900 text-rose-700 dark:text-rose-300'],
                            'cancelled'      => ['label' => 'Cancelled',       'class' => 'bg-slate-100 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400'],
                        ];
                        $s = $order['status'];
                        $info = isset($statusMap[$s]) ? $statusMap[$s] : ['label' => ucfirst($s), 'class' => 'bg-slate-100 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400'];
                        ?>
                        <span
                            class="inline-flex px-3 py-1.5 rounded-full border text-xs font-bold uppercase tracking-wider <?php echo $info['class']; ?>">
                            <?php echo $info['label']; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="p-6 divide-y divide-slate-100 dark:divide-slate-800">
                <?php foreach ($order['items'] as $item): ?>
                <div class="py-4 first:pt-0 last:pb-0 flex items-center justify-between gap-6">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 rounded-lg overflow-hidden bg-slate-50 dark:bg-slate-950 shrink-0 border border-slate-100 dark:border-slate-800/50">
                            <?php if (!empty($item['image']) && file_exists(__DIR__ . '/img/' . $item['image'])): ?>
                            <img src="img/<?php echo htmlspecialchars($item['image']); ?>"
                                alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                class="w-full h-full object-cover">
                            <?php else: ?>
                            <div
                                class="w-full h-full bg-gradient-to-tr from-indigo-500/10 via-purple-500/10 to-pink-500/10 flex items-center justify-center text-slate-400">
                                <svg class="w-5 h-5 stroke-[1.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375 0 11-.75 0 .375 0 01.75 0z">
                                    </path>
                                </svg>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h4
                                class="font-bold text-slate-800 dark:text-slate-100 text-sm hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                <a
                                    href="product.php?id=<?php echo $item['product_id']; ?>"><?php echo htmlspecialchars($item['product_name']); ?></a>
                            </h4>
                            <p class="text-xs text-slate-400 mt-0.5">Quantity: <?php echo $item['quantity']; ?></p>
                        </div>
                    </div>

                    <div class="text-right">
                        <p class="font-display font-extrabold text-sm text-slate-800 dark:text-slate-200">
                            Rs.<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                        </p>
                        <p class="text-[10px] text-slate-400 mt-0.5">(Rs.<?php echo number_format($item['price'], 2); ?>
                            each)</p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>