<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$encoded = $_GET['data'] ?? '';
if (!empty($encoded)) {
    $response = json_decode(base64_decode($encoded), true);
    if ($response && isset($response['transaction_uuid'])) {
        $uuid = mysqli_real_escape_string($conn, $response['transaction_uuid']);
        mysqli_query($conn, "UPDATE orders SET status = 'failed' WHERE transaction_uuid = '$uuid' AND status = 'pending_payment'");
    }
}

$pageTitle = 'Payment Failed';
require_once __DIR__ . '/includes/header.php';
?>

<div class="min-h-[60vh] flex items-center justify-center">
    <div class="text-center max-w-lg mx-auto space-y-6">
        <div class="w-20 h-20 rounded-full bg-rose-100 dark:bg-rose-950 flex items-center justify-center mx-auto">
            <svg class="w-10 h-10 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h1 class="font-display font-extrabold text-3xl text-slate-800 dark:text-slate-100">
            Payment Failed
        </h1>
        <p class="text-slate-500 dark:text-slate-400 text-sm">
            Your payment was not completed. Your cart is still saved.
        </p>
        <div class="flex justify-center gap-3 pt-4">
            <a href="cart.php"
                class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl text-sm transition-all shadow-md">
                Try Again
            </a>
            <a href="index.php"
                class="px-6 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold rounded-xl text-sm transition-all">
                Continue Shopping
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
