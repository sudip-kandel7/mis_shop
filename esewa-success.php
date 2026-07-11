<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$encoded = $_GET['data'] ?? '';
if (empty($encoded)) {
    setFlash('error', 'Invalid payment response.');
    header('Location: cart.php');
    exit;
}

$decoded  = base64_decode($encoded);
$response = json_decode($decoded, true);

if (!$response) {
    setFlash('error', 'Could not read payment response.');
    header('Location: cart.php');
    exit;
}

$secret_key = "8gBm/:&EnhH.1/q";

$fields  = explode(',', $response['signed_field_names']);
$parts   = [];
foreach ($fields as $field) {
    $parts[] = "$field={$response[$field]}";
}
$message      = implode(',', $parts);
$our_sig      = base64_encode(hash_hmac('sha256', $message, $secret_key, true));

if ($our_sig !== $response['signature']) {
    setFlash('error', 'Payment verification failed. Please contact support.');
    header('Location: cart.php');
    exit;
}

if ($response['status'] !== 'COMPLETE') {
    setFlash('error', 'Payment was not completed.');
    header('Location: cart.php');
    exit;
}

$uuid   = mysqli_real_escape_string($conn, $response['transaction_uuid']);
$result = mysqli_query($conn, "SELECT * FROM orders WHERE transaction_uuid = '$uuid'");
$order  = mysqli_fetch_assoc($result);

if (!$order) {
    setFlash('error', 'Order not found.');
    header('Location: cart.php');
    exit;
}

$order_id = $order['id'];

// If already paid, show success page without reprocessing
if ($order['status'] === 'paid') {
    $alreadyProcessed = true;
    $txn_code = $order['transaction_code'];
} else {
    $alreadyProcessed = false;
    $user_id  = $_SESSION['user_id'];

    mysqli_query($conn, "START TRANSACTION");

    $txn_code = mysqli_real_escape_string($conn, $response['transaction_code']);
    mysqli_query($conn, "UPDATE orders SET status = 'paid', transaction_code = '$txn_code' WHERE id = $order_id");

    $items = mysqli_query($conn, "SELECT * FROM order_items WHERE order_id = $order_id");
    while ($item = mysqli_fetch_assoc($items)) {
        mysqli_query($conn, "UPDATE products SET stock = stock - {$item['quantity']} WHERE id = {$item['product_id']} AND stock >= {$item['quantity']}");
    }

    mysqli_query($conn, "
        DELETE FROM cart 
        WHERE user_id = $user_id 
        AND product_id IN (SELECT product_id FROM order_items WHERE order_id = $order_id)
    ");

    mysqli_query($conn, "COMMIT");
}

$pageTitle = 'Payment Successful';
require_once __DIR__ . '/includes/header.php';
?>

<div class="min-h-[60vh] flex items-center justify-center">
    <div class="text-center max-w-lg mx-auto space-y-6">
        <div class="w-20 h-20 rounded-full bg-emerald-100 dark:bg-emerald-950 flex items-center justify-center mx-auto">
            <svg class="w-10 h-10 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h1 class="font-display font-extrabold text-3xl text-slate-800 dark:text-slate-100">
            Payment Successful!
        </h1>
        <p class="text-slate-500 dark:text-slate-400 text-sm">
            Order #<?php echo $order_id; ?> confirmed.
            <?php if (!$alreadyProcessed): ?>
                Your items will be shipped soon.
            <?php endif; ?>
        </p>
        <p class="text-xs text-slate-400">
            Transaction: <?php echo htmlspecialchars($txn_code); ?>
        </p>
        <div class="flex justify-center gap-3 pt-4">
            <a href="orders.php"
                class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl text-sm transition-all shadow-md">
                View My Orders
            </a>
            <a href="index.php"
                class="px-6 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold rounded-xl text-sm transition-all">
                Continue Shopping
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
