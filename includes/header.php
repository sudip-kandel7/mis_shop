<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

// Calculate Cart Item Count
$cartCount = 0;
if (isLoggedIn()) {
    $result = mysqli_query($conn, "SELECT SUM(quantity) as total FROM cart WHERE user_id = " . $_SESSION['user_id']);
    $row = mysqli_fetch_assoc($result);
    $cartCount = $row['total'] ?? 0;
} else {
    if (isset($_SESSION['temp_cart'])) {
        foreach ($_SESSION['temp_cart'] as $qty) {
            $cartCount += $qty;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . " | MIS Shop" : "MIS Shop - Premium E-Commerce"; ?>
    </title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Tailwind CSS v3 Play CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Tailwind Configuration -->
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        display: ['Outfit', 'sans-serif'],
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        'brand-primary': '#6366f1',
                        'brand-secondary': '#4f46e5',
                    }
                }
            }
        }
    </script>

    <!-- Custom Style Sheet -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body
    class="font-sans min-h-screen flex flex-col bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-50 transition-colors duration-300">

    <!-- Header Navigation -->
    <header
        class="sticky top-0 z-40 w-full border-b border-slate-200/80 dark:border-slate-800/80 bg-white/70 dark:bg-slate-900/70 backdrop-blur-md transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 gap-4">

                <!-- Logo -->
                <div class="flex-shrink-0 flex items-center">
                    <a href="index.php" class="flex items-center gap-2 group">
                        <div
                            class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-500 to-violet-600 flex items-center justify-center text-white shadow-md shadow-indigo-500/20 group-hover:scale-105 transition-transform duration-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                        </div>
                        <span
                            class="font-display font-extrabold text-xl tracking-tight bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 bg-clip-text text-transparent group-hover:opacity-90 transition-opacity">
                            MIS Shop
                        </span>
                    </a>
                </div>


                <!-- Right Nav Section -->
                <nav class="flex items-center gap-2">
                    <!-- Search Trigger (Mobile) -->
                    <a href="index.php"
                        class="md:hidden p-2 rounded-lg text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                        title="Search">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </a>

                    <!-- Theme Toggle -->
                    <button id="theme-toggle"
                        class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors focus:outline-none"
                        title="Toggle Theme">
                        <span id="theme-toggle-icon"></span>
                    </button>

                    <!-- Cart Link -->
                    <?php if (!isOwner()): ?>
                        <a href="cart.php"
                            class="relative p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                            title="Shopping Cart">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z">
                                </path>
                            </svg>
                            <?php if ($cartCount > 0): ?>
                                <span
                                    class="absolute -top-1 -right-1 w-5 h-5 bg-rose-500 text-white rounded-full flex items-center justify-center text-[10px] font-extrabold shadow-sm animate-pulse">
                                    <?php echo $cartCount; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>

                    <!-- Account / Admin Links -->
                    <div class="h-6 w-px bg-slate-200 dark:bg-slate-800 mx-1"></div>

                    <?php if (isLoggedIn()): ?>
                        <!-- Logged In User Links -->
                        <div class="relative group">
                            <button
                                class="flex items-center gap-1.5 p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors focus:outline-none">
                                <div
                                    class="w-7 h-7 rounded-full bg-gradient-to-tr from-indigo-400 to-purple-500 flex items-center justify-center text-white text-xs font-bold font-display shadow-sm">
                                    <?php echo strtoupper(substr($_SESSION['username'], 0, 2)); ?>
                                </div>
                                <span class="hidden lg:inline text-sm font-medium text-slate-700 dark:text-slate-300">
                                    <?php echo htmlspecialchars($_SESSION['username']); ?>
                                </span>
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <!-- Dropdown Menu -->
                            <div
                                class="absolute right-0 mt-2 w-48 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 py-1.5 z-50">
                                <?php if ($_SESSION['role'] != 'owner'): ?>
                                    <a href="orders.php"
                                        class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                        <svg class="w-[23px] h-[23px]" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01">
                                            </path>
                                        </svg>
                                        My Orders
                                    </a>
                                <?php endif; ?>
                                <?php if (isAdminLoggedIn()): ?>
                                    <a href="admin/index.php"
                                        class="flex items-center gap-2 px-4 py-2 text-sm text-indigo-600 dark:text-indigo-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors font-semibold">
                                        <svg class="w-[23px] h-[23px]" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2。
                                        d=" M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        Admin Panel
                                    </a>
                                <?php endif; ?>
                                <hr class="border-slate-200 dark:border-slate-800 my-1">
                                <a href="logout.php"
                                    class="flex items-center gap-2 px-4 py-2 text-sm text-rose-600 dark:text-rose-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                    <svg class="w-[23px] h-[23px]" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                                        </path>
                                    </svg>
                                    Logout
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Logged Out User Options -->
                        <div class="flex items-center gap-2">
                            <a href="login.php"
                                class="text-sm font-semibold text-slate-700 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 px-3 py-2 rounded-lg transition-colors">
                                Login
                            </a>
                            <a href="register.php"
                                class="text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-600 px-4 py-2 rounded-xl transition-all shadow-md shadow-indigo-500/10 hover:shadow-indigo-500/20 active:scale-[0.98]">
                                Register
                            </a>
                        </div>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>



    <!-- Main Content Container -->
    <main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Toast Container for PHP session flash messages -->
        <div id="toast-container">
            <?php
            $successMsg = getFlash('success');
            $errorMsg = getFlash('error');
            $infoMsg = getFlash('info');

            if ($successMsg): ?>
                <div
                    class="toast-message px-4 py-3 rounded-lg shadow-xl flex items-center gap-3 text-sm font-medium border bg-emerald-50/90 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-200 border-emerald-200 dark:border-emerald-800">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span><?php echo htmlspecialchars($successMsg); ?></span>
                </div>
            <?php endif;

            if ($errorMsg): ?>
                <div
                    class="toast-message px-4 py-3 rounded-lg shadow-xl flex items-center gap-3 text-sm font-medium border bg-rose-50/90 dark:bg-rose-950/80 text-rose-800 dark:text-rose-200 border-rose-200 dark:border-rose-800">
                    <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" stroke-width="2.5"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span><?php echo htmlspecialchars($errorMsg); ?></span>
                </div>
            <?php endif;

            if ($infoMsg): ?>
                <div
                    class="toast-message px-4 py-3 rounded-lg shadow-xl flex items-center gap-3 text-sm font-medium border bg-indigo-50/90 dark:bg-indigo-950/80 text-indigo-800 dark:text-indigo-200 border-indigo-200 dark:border-indigo-800">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" stroke-width="2.5"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span><?php echo htmlspecialchars($infoMsg); ?></span>
                </div>
            <?php endif; ?>
        </div>