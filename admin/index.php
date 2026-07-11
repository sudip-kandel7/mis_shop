<?php


$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Only owners/admins can access admin panel
requireOwner();

require_once __DIR__ . '/includes/header.php';

// Initialize stats
$totalOrders = 0;
$totalRevenue = 0;
$totalProducts = 0;
$totalCategories = 0;
$totalUsers = 0;
$lowStockAlerts = [];
$latestOrders = [];

try {
    // 1. Get Counts
    $result = mysqli_query($conn, "SELECT COUNT(*) FROM orders");
    $row = mysqli_fetch_row($result);
    $totalOrders = $row[0];

    $result = mysqli_query($conn, "SELECT SUM(total) FROM orders WHERE status = 'paid'");
    $row = mysqli_fetch_row($result);
    $totalRevenue = $row[0] ?? 0;

    $result = mysqli_query($conn, "SELECT COUNT(*) FROM products");
    $row = mysqli_fetch_row($result);
    $totalProducts = $row[0];

    $result = mysqli_query($conn, "SELECT COUNT(*) FROM categories");
    $row = mysqli_fetch_row($result);
    $totalCategories = $row[0];

    $result = mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE role = 'customer'");
    $row = mysqli_fetch_row($result);
    $totalUsers = $row[0];

    // 2. Get Low Stock Products (stock <= 5)
    $result = mysqli_query($conn, "
        SELECT p.*, c.name as category_name 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        WHERE p.stock <= 5 
        ORDER BY p.stock ASC 
        LIMIT 5
    ");
    $lowStockAlerts = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $lowStockAlerts[] = $row;
    }

    // 3. Get Latest 5 Orders
    $result = mysqli_query($conn, "
        SELECT o.*, u.username 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    $latestOrders = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $latestOrders[] = $row;
    }
} catch (\Exception $e) {
    setFlash('error', 'Error compiling stats: ' . $e->getMessage());
}
?>

<!-- Statistics Overview -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

    <!-- Revenue -->
    <div
        class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
        <div
            class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 text-xl font-bold">
            Rs.
        </div>
        <div>
            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest leading-none">Total Revenue</span>
            <h3 class="font-display font-extrabold text-2xl text-slate-800 dark:text-slate-100 mt-1">
                Rs.<?php echo number_format($totalRevenue, 2); ?></h3>
        </div>
    </div>

    <!-- Orders -->
    <div
        class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
        <div
            class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z">
                </path>
            </svg>
        </div>
        <div>
            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest leading-none">Total Orders</span>
            <h3 class="font-display font-extrabold text-2xl text-slate-800 dark:text-slate-100 mt-1">
                <?php echo $totalOrders; ?></h3>
        </div>
    </div>

    <!-- Products -->
    <div
        class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
        <div
            class="w-12 h-12 rounded-xl bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
            </svg>
        </div>
        <div>
            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest leading-none">Products</span>
            <h3 class="font-display font-extrabold text-2xl text-slate-800 dark:text-slate-100 mt-1">
                <?php echo $totalProducts; ?></h3>
        </div>
    </div>

    <!-- Customers -->
    <div
        class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
        <div
            class="w-12 h-12 rounded-xl bg-pink-50 dark:bg-pink-950/50 text-pink-600 dark:text-pink-400 flex items-center justify-center shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                </path>
            </svg>
        </div>
        <div>
            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest leading-none">Customers</span>
            <h3 class="font-display font-extrabold text-2xl text-slate-800 dark:text-slate-100 mt-1">
                <?php echo $totalUsers; ?></h3>
        </div>
    </div>

</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

    <!-- Latest Orders (LG: col-span-8) -->
    <div
        class="lg:col-span-8 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-3xl p-6 shadow-sm space-y-6">
        <div class="flex justify-between items-center">
            <h3 class="font-display font-extrabold text-lg text-slate-800 dark:text-slate-100 tracking-tight">Recent
                Transactions</h3>
            <a href="orders.php" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">View All
                Orders &rarr;</a>
        </div>

        <?php if (empty($latestOrders)): ?>
            <p class="text-slate-500 dark:text-slate-400 text-sm text-center py-6">No orders registered yet.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead>
                        <tr
                            class="border-b border-slate-100 dark:border-slate-800 text-xs font-bold text-slate-400 uppercase tracking-wider">
                            <th class="pb-3.5 pl-2">Order ID</th>
                            <th class="pb-3.5">Customer</th>
                            <th class="pb-3.5">Date</th>
                            <th class="pb-3.5">Amount</th>
                            <th class="pb-3.5">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <?php foreach ($latestOrders as $order): ?>
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/50 transition-colors">
                                <td class="py-3.5 pl-2 font-bold text-slate-800 dark:text-slate-200">
                                    #<?php echo $order['id']; ?></td>
                                <td class="py-3.5 font-semibold text-slate-700 dark:text-slate-300">
                                    <?php echo htmlspecialchars($order['username']); ?></td>
                                <td class="py-3.5 text-slate-500 dark:text-slate-400 text-xs">
                                    <?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td class="py-3.5 font-bold text-slate-800 dark:text-slate-200">
                                    Rs.<?php echo number_format($order['total'], 2); ?></td>
                                <td class="py-3.5">
                                    <?php if ($order['status'] === 'pending'): ?>
                                        <span
                                            class="inline-flex px-2 py-1 rounded-full bg-amber-50 dark:bg-amber-950 border border-amber-100 dark:border-amber-900 text-amber-700 dark:text-amber-300 text-[10px] font-bold uppercase tracking-wider">Pending</span>
                                    <?php elseif ($order['status'] === 'completed'): ?>
                                        <span
                                            class="inline-flex px-2 py-1 rounded-full bg-emerald-50 dark:bg-emerald-950 border border-emerald-100 dark:border-emerald-900 text-emerald-700 dark:text-emerald-300 text-[10px] font-bold uppercase tracking-wider">Completed</span>
                                    <?php else: ?>
                                        <span
                                            class="inline-flex px-2 py-1 rounded-full bg-rose-50 dark:bg-rose-950 border border-rose-100 dark:border-rose-900 text-rose-700 dark:text-rose-300 text-[10px] font-bold uppercase tracking-wider">Cancelled</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Stock Alerts Sidebar (LG: col-span-4) -->
    <div
        class="lg:col-span-4 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-3xl p-6 shadow-sm space-y-6">
        <h3
            class="font-display font-extrabold text-lg text-slate-800 dark:text-slate-100 tracking-tight flex items-center gap-2">
            <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" stroke-width="2.5"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                </path>
            </svg>
            Inventory Alerts
        </h3>

        <?php if (empty($lowStockAlerts)): ?>
            <p class="text-slate-500 dark:text-slate-400 text-sm text-center py-6">All products are adequately stocked.</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($lowStockAlerts as $alert): ?>
                    <div
                        class="flex items-center justify-between gap-4 p-3.5 bg-slate-50 dark:bg-slate-950/80 rounded-2xl border border-slate-100 dark:border-slate-800/50">
                        <div class="truncate max-w-[150px]">
                            <h4 class="font-bold text-xs text-slate-800 dark:text-slate-200 truncate">
                                <?php echo htmlspecialchars($alert['name']); ?></h4>
                            <span
                                class="text-[9px] text-slate-400 font-bold uppercase tracking-wider"><?php echo htmlspecialchars($alert['category_name']); ?></span>
                        </div>
                        <span
                            class="inline-flex px-2 py-1 rounded-lg text-xs font-bold shrink-0 <?php echo ($alert['stock'] == 0) ? 'bg-rose-500 text-white shadow-sm' : 'bg-amber-100 dark:bg-amber-950/80 text-amber-700 dark:text-amber-300'; ?>">
                            <?php echo ($alert['stock'] == 0) ? 'Out of Stock' : $alert['stock'] . ' Left'; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>