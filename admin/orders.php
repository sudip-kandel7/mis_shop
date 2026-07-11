<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isAdminLoggedIn()) {
    setFlash('error', 'Administrator access required. Please log in.');
    header('Location: login.php');
    exit;
}

// -----------------------------------------------------------
// Business Logic
// -----------------------------------------------------------

$pageTitle = 'Manage Orders';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$view_id = isset($_GET['view']) ? (int)$_GET['view'] : 0;
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// Helper: build redirect URL keeping context
function ordersRedirect($view = 0, $uid = 0) {
    $params = [];
    if ($uid > 0) $params['user_id'] = $uid;
    if ($view > 0) $params['view'] = $view;
    header('Location: orders.php' . ($params ? '?' . http_build_query($params) : ''));
    exit;
}

// Update Status
if ($action === 'update') {
    $order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $status = trim($_GET['status'] ?? '');

    $allowedStatuses = ['pending_payment', 'paid', 'shipped', 'delivered', 'cancelled'];

    if ($order_id > 0 && in_array($status, $allowedStatuses)) {
        $status = mysqli_real_escape_string($conn, $status);
        if (mysqli_query($conn, "UPDATE orders SET status = '$status' WHERE id = $order_id")) {
            $label = str_replace('_', ' ', $status);
            setFlash('success', "Order #{$order_id} status updated to \"{$label}\".");
        } else {
            setFlash('error', "Failed to update status: " . mysqli_error($conn));
        }
    }

    ordersRedirect($view_id, $user_id);
}

// Delete Single Order
if ($action === 'delete') {
    $delete_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($delete_id > 0) {
        if (mysqli_query($conn, "DELETE FROM orders WHERE id = $delete_id")) {
            setFlash('success', "Order #{$delete_id} deleted successfully.");
        } else {
            setFlash('error', "Failed to delete order: " . mysqli_error($conn));
        }
    }
    ordersRedirect(0, $user_id);
}

// Delete All Orders of a User
if ($action === 'delete_user') {
    $del_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    if ($del_user_id > 0) {
        $result = mysqli_query($conn, "SELECT username FROM users WHERE id = $del_user_id");
        $user = mysqli_fetch_assoc($result);
        $username = $user ? $user['username'] : "User #$del_user_id";

        if (mysqli_query($conn, "DELETE FROM orders WHERE user_id = $del_user_id")) {
            $affected = mysqli_affected_rows($conn);
            setFlash('success', "All $affected order(s) by \"{$username}\" deleted successfully.");
        } else {
            setFlash('error', "Failed to delete orders: " . mysqli_error($conn));
        }
    }
    ordersRedirect();
}

// -----------------------------------------------------------
// Fetch Data
// -----------------------------------------------------------

// Fetch all users with total spent
$usersResult = mysqli_query($conn, "
    SELECT u.id, u.username, u.email,
        COALESCE(SUM(o.total), 0) as total_spent,
        COUNT(o.id) as order_count
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    WHERE u.role = 'customer'
    GROUP BY u.id, u.username, u.email
    ORDER BY total_spent DESC
");
$users = [];
while ($row = mysqli_fetch_assoc($usersResult)) {
    $users[] = $row;
}

// Fetch orders of a specific user
$userOrders = [];
$selectedUser = null;
if ($user_id > 0) {
    $uResult = mysqli_query($conn, "SELECT id, username, email FROM users WHERE id = $user_id");
    $selectedUser = mysqli_fetch_assoc($uResult);

    if ($selectedUser) {
        $oResult = mysqli_query($conn, "
            SELECT * FROM orders WHERE user_id = $user_id ORDER BY created_at DESC
        ");
        while ($row = mysqli_fetch_assoc($oResult)) {
            $userOrders[] = $row;
        }
    }
}

// Fetch single order for detail view
$selectedOrder = null;
if ($view_id > 0) {
    $result = mysqli_query($conn, "
        SELECT o.*, u.username, u.email
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.id = $view_id
    ");
    $selectedOrder = mysqli_fetch_assoc($result);

    if ($selectedOrder) {
        $itemsResult = mysqli_query($conn, "
            SELECT oi.*, p.name as product_name, p.image
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = $view_id
        ");
        $selectedOrder['items'] = [];
        while ($item = mysqli_fetch_assoc($itemsResult)) {
            $selectedOrder['items'][] = $item;
        }
    }
}

// -----------------------------------------------------------
// Output
// -----------------------------------------------------------
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($selectedOrder): ?>
<!-- Level 3: Single Order Detail -->
<div class="max-w-3xl mx-auto">
    <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-4">
        <a href="orders.php" class="hover:underline">Customers</a>
        <span>/</span>
        <a href="orders.php?user_id=<?php echo $selectedOrder['user_id']; ?>"
            class="hover:underline"><?php echo htmlspecialchars($selectedOrder['username']); ?></a>
        <span>/</span>
        <span class="text-slate-600 dark:text-slate-300">Order #<?php echo $selectedOrder['id']; ?></span>
    </div>

    <div
        class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-3xl p-6 shadow-sm space-y-6">
        <div class="flex justify-between items-center border-b border-slate-100 dark:border-slate-800 pb-4">
            <h3 class="font-display font-extrabold text-lg text-slate-800 dark:text-slate-100 tracking-tight">
                Order #<?php echo $selectedOrder['id']; ?></h3>
            <a href="orders.php?user_id=<?php echo $selectedOrder['user_id']; ?>"
                class="text-xs font-bold text-slate-400 hover:underline">&larr; Back to Orders</a>
        </div>

        <!-- Status Controls -->
        <div
            class="p-4 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-100 dark:border-slate-800/50 space-y-2.5">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Update Status</p>
            <div class="flex items-center gap-2 flex-wrap">
                <a href="orders.php?action=update&id=<?php echo $selectedOrder['id']; ?>&status=paid&view=<?php echo $selectedOrder['id']; ?>&user_id=<?php echo $selectedOrder['user_id']; ?>"
                    class="px-3.5 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-sm transition-colors">
                    Mark Paid
                </a>
                <a href="orders.php?action=update&id=<?php echo $selectedOrder['id']; ?>&status=shipped&view=<?php echo $selectedOrder['id']; ?>&user_id=<?php echo $selectedOrder['user_id']; ?>"
                    class="px-3.5 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-sm transition-colors">
                    Mark Shipped
                </a>
                <a href="orders.php?action=update&id=<?php echo $selectedOrder['id']; ?>&status=delivered&view=<?php echo $selectedOrder['id']; ?>&user_id=<?php echo $selectedOrder['user_id']; ?>"
                    class="px-3.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-sm transition-colors">
                    Mark Delivered
                </a>
                <a href="orders.php?action=update&id=<?php echo $selectedOrder['id']; ?>&status=cancelled&view=<?php echo $selectedOrder['id']; ?>&user_id=<?php echo $selectedOrder['user_id']; ?>"
                    class="px-3.5 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold shadow-sm transition-colors">
                    Cancel Order
                </a>
                <a href="orders.php?action=update&id=<?php echo $selectedOrder['id']; ?>&status=pending_payment&view=<?php echo $selectedOrder['id']; ?>&user_id=<?php echo $selectedOrder['user_id']; ?>"
                    class="px-3.5 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-400 text-white text-xs font-bold shadow-sm transition-colors">
                    Reset Pending
                </a>
            </div>
        </div>

        <!-- Customer Info -->
        <div class="space-y-3.5 text-sm">
            <h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider">Customer</h4>
            <p class="text-slate-600 dark:text-slate-300">
                <span
                    class="font-semibold text-slate-800 dark:text-slate-100"><?php echo htmlspecialchars($selectedOrder['username']); ?></span>
                &middot; <?php echo htmlspecialchars($selectedOrder['email']); ?>
            </p>
        </div>

        <!-- Shipping -->
        <div class="space-y-3.5 text-sm">
            <h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider">Shipping</h4>
            <div
                class="p-4 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-100 dark:border-slate-800/50 space-y-2">
                <p class="text-slate-600 dark:text-slate-300"><span
                        class="font-semibold text-slate-800 dark:text-slate-100">Name:</span>
                    <?php echo htmlspecialchars($selectedOrder['shipping_name']); ?></p>
                <p class="text-slate-600 dark:text-slate-300"><span
                        class="font-semibold text-slate-800 dark:text-slate-100">Address:</span>
                    <?php echo nl2br(htmlspecialchars($selectedOrder['shipping_address'])); ?></p>
                <p class="text-slate-600 dark:text-slate-300"><span
                        class="font-semibold text-slate-800 dark:text-slate-100">Phone:</span>
                    <?php echo htmlspecialchars($selectedOrder['shipping_phone']); ?></p>
            </div>
        </div>

        <!-- Payment -->
        <div class="space-y-3.5 text-sm">
            <h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider">Payment</h4>
            <div class="space-y-1">
                <p class="text-slate-600 dark:text-slate-300">
                    <span class="font-semibold text-slate-800 dark:text-slate-100">Method:</span>
                    <?php echo strtoupper(htmlspecialchars($selectedOrder['payment_method'] ?? 'N/A')); ?>
                </p>
                <?php if ($selectedOrder['transaction_uuid']): ?>
                <p class="text-slate-600 dark:text-slate-300"><span
                        class="font-semibold text-slate-800 dark:text-slate-100">Transaction UUID:</span>
                    <?php echo htmlspecialchars($selectedOrder['transaction_uuid']); ?></p>
                <?php endif; ?>
                <?php if ($selectedOrder['transaction_code']): ?>
                <p class="text-slate-600 dark:text-slate-300"><span
                        class="font-semibold text-slate-800 dark:text-slate-100">Transaction Code:</span>
                    <?php echo htmlspecialchars($selectedOrder['transaction_code']); ?></p>
                <?php endif; ?>
                <p class="text-slate-600 dark:text-slate-300 text-base">
                    <span class="font-semibold text-slate-800 dark:text-slate-100">Total:</span>
                    <span
                        class="font-bold text-indigo-600 dark:text-indigo-400">Rs.<?php echo number_format($selectedOrder['total'], 2); ?></span>
                </p>
            </div>
        </div>

        <!-- Items -->
        <div class="space-y-3.5">
            <h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider">Items</h4>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                <?php foreach ($selectedOrder['items'] as $item): ?>
                <div class="py-3 flex justify-between gap-4 text-sm">
                    <div>
                        <span
                            class="font-semibold text-slate-800 dark:text-slate-200 block"><?php echo htmlspecialchars($item['product_name']); ?></span>
                        <span class="text-slate-400 text-xs font-semibold">Qty: <?php echo $item['quantity']; ?></span>
                    </div>
                    <span
                        class="font-bold text-slate-800 dark:text-slate-200 shrink-0">Rs.<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Delete -->
        <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
            <a href="orders.php?action=delete&id=<?php echo $selectedOrder['id']; ?>&user_id=<?php echo $selectedOrder['user_id']; ?>"
                onclick="return confirm('Delete order #<?php echo $selectedOrder['id']; ?>?')"
                class="text-xs font-bold text-rose-500 hover:underline">Delete This Order</a>
        </div>
    </div>
</div>

<?php elseif ($selectedUser): ?>
<!-- Level 2: User's Orders -->
<div class="max-w-4xl mx-auto">
    <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-4">
        <a href="orders.php" class="hover:underline">Customers</a>
        <span>/</span>
        <span
            class="text-slate-600 dark:text-slate-300"><?php echo htmlspecialchars($selectedUser['username']); ?></span>
    </div>

    <div
        class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-3xl p-6 shadow-sm space-y-6">
        <div class="flex justify-between items-center flex-wrap gap-4">
            <div>
                <h3 class="font-display font-extrabold text-lg text-slate-800 dark:text-slate-100 tracking-tight">
                    <?php echo htmlspecialchars($selectedUser['username']); ?> &mdash; Orders</h3>
                <p class="text-slate-400 text-xs font-medium"><?php echo count($userOrders); ?> total order(s)</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="orders.php?action=delete_user&user_id=<?php echo $selectedUser['id']; ?>"
                    onclick="return confirm('Delete ALL orders by <?php echo htmlspecialchars($selectedUser['username']); ?>?')"
                    class="text-xs font-bold text-rose-500 hover:underline">Delete All Orders</a>
                <a href="orders.php" class="text-xs font-bold text-slate-400 hover:underline">&larr; All Customers</a>
            </div>
        </div>

        <?php if (empty($userOrders)): ?>
        <p class="text-slate-500 dark:text-slate-400 text-sm text-center py-6">No orders from this customer.</p>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm border-collapse">
                <thead>
                    <tr
                        class="border-b border-slate-100 dark:border-slate-800 text-xs font-bold text-slate-400 uppercase tracking-wider">
                        <th class="pb-3.5 pl-2">Order</th>
                        <th class="pb-3.5">Amount</th>
                        <th class="pb-3.5">Status</th>
                        <th class="pb-3.5">Payment</th>
                        <th class="pb-3.5 text-right pr-2">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <?php foreach ($userOrders as $order): ?>
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/50 transition-colors">
                        <td class="py-3.5 pl-2">
                            <span
                                class="font-bold text-slate-800 dark:text-slate-200 block">#<?php echo $order['id']; ?></span>
                            <span
                                class="text-[10px] text-slate-400 font-semibold"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
                        </td>
                        <td class="py-3.5 font-bold text-slate-800 dark:text-slate-200">
                            Rs.<?php echo number_format($order['total'], 2); ?></td>
                        <td class="py-3.5">
                            <?php if ($order['status'] === 'pending_payment'): ?>
                            <span
                                class="inline-flex px-2 py-0.5 rounded-full bg-amber-50 dark:bg-amber-950 border border-amber-100 dark:border-amber-900 text-amber-700 dark:text-amber-300 text-[10px] font-bold uppercase">Pending
                                Payment</span>
                            <?php elseif (in_array($order['status'], ['paid', 'shipped', 'delivered'])): ?>
                            <span
                                class="inline-flex px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950 border border-emerald-100 dark:border-emerald-900 text-emerald-700 dark:text-emerald-300 text-[10px] font-bold uppercase"><?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?></span>
                            <?php elseif ($order['status'] === 'failed'): ?>
                            <span
                                class="inline-flex px-2 py-0.5 rounded-full bg-rose-50 dark:bg-rose-950 border border-rose-100 dark:border-rose-900 text-rose-700 dark:text-rose-300 text-[10px] font-bold uppercase">Failed</span>
                            <?php else: ?>
                            <span
                                class="inline-flex px-2 py-0.5 rounded-full bg-rose-50 dark:bg-rose-950 border border-rose-100 dark:border-rose-900 text-rose-700 dark:text-rose-300 text-[10px] font-bold uppercase">Cancelled</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3.5 text-xs text-slate-500 font-semibold">
                            <?php echo strtoupper(htmlspecialchars($order['payment_method'] ?? 'N/A')); ?></td>
                        <td class="py-3.5 text-right pr-2 space-x-1 shrink-0">
                            <a href="orders.php?view=<?php echo $order['id']; ?>&user_id=<?php echo $selectedUser['id']; ?>"
                                class="inline-block text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">View</a>
                            <span class="text-slate-200 dark:text-slate-700">|</span>
                            <a href="orders.php?action=delete&id=<?php echo $order['id']; ?>&user_id=<?php echo $selectedUser['id']; ?>"
                                onclick="return confirm('Delete order #<?php echo $order['id']; ?>?')"
                                class="inline-block text-xs font-bold text-rose-500 hover:underline">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>
<!-- Level 1: User List with Totals -->
<div class="max-w-4xl mx-auto">
    <div
        class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-3xl p-6 shadow-sm space-y-6">
        <div>
            <h3 class="font-display font-extrabold text-lg text-slate-800 dark:text-slate-100 tracking-tight">Customers
            </h3>
            <p class="text-slate-400 text-xs font-medium">All customers and their total spending.</p>
        </div>

        <?php if (empty($users)): ?>
        <p class="text-slate-500 dark:text-slate-400 text-sm text-center py-6">No customers found.</p>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm border-collapse">
                <thead>
                    <tr
                        class="border-b border-slate-100 dark:border-slate-800 text-xs font-bold text-slate-400 uppercase tracking-wider">
                        <th class="pb-3.5 pl-2">Customer</th>
                        <th class="pb-3.5">Orders</th>
                        <th class="pb-3.5">Total Spent</th>
                        <th class="pb-3.5 text-right pr-2">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <?php foreach ($users as $u): ?>
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/50 transition-colors">
                        <td class="py-3.5 pl-2">
                            <span
                                class="font-bold text-slate-800 dark:text-slate-200 block"><?php echo htmlspecialchars($u['username']); ?></span>
                            <span
                                class="text-[10px] text-slate-400 font-semibold"><?php echo htmlspecialchars($u['email']); ?></span>
                        </td>
                        <td class="py-3.5 font-semibold text-slate-700 dark:text-slate-300">
                            <?php echo $u['order_count']; ?></td>
                        <td class="py-3.5 font-bold text-slate-800 dark:text-slate-200">
                            Rs.<?php echo number_format($u['total_spent'], 2); ?></td>
                        <td class="py-3.5 text-right pr-2 space-x-1 shrink-0">
                            <a href="orders.php?user_id=<?php echo $u['id']; ?>"
                                class="inline-block text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">View</a>
                            <span class="text-slate-200 dark:text-slate-700">|</span>
                            <a href="orders.php?action=delete_user&user_id=<?php echo $u['id']; ?>"
                                onclick="return confirm('Delete ALL orders by <?php echo htmlspecialchars($u['username']); ?>?')"
                                class="inline-block text-xs font-bold text-rose-500 hover:underline">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>