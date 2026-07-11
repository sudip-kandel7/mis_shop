<?php


require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

// Force Admin Authentication
if (!isAdminLoggedIn()) {
    setFlash('error', 'Administrator access required. Please log in.');
    header('Location: login.php');
    exit;
}

// Get active class for sidebar navigation
$current_page = basename($_SERVER['PHP_SELF']);
function is_active($page, $current) {
    return ($page === $current) ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-100';
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . " | MIS Admin" : "MIS Shop Admin Dashboard"; ?>
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
                }
            }
        }
    }
    </script>

    <!-- Custom CSS (Shares global style assets) -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body
    class="font-sans min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-50 transition-colors duration-300 flex">

    <!-- Sidebar Dashboard Navigation -->
    <aside
        class="w-64 border-r border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-slate-900 shrink-0 sticky top-0 h-screen flex flex-col justify-between transition-colors duration-300">
        <div>
            <!-- Sidebar Header -->
            <div class="h-16 flex items-center gap-2.5 px-6 border-b border-slate-100 dark:border-slate-800">
                <div
                    class="w-8 h-8 rounded-lg bg-gradient-to-tr from-indigo-500 to-violet-600 flex items-center justify-center text-white font-extrabold shadow-md">
                    M
                </div>
                <span
                    class="font-display font-extrabold text-base tracking-tight bg-gradient-to-r from-indigo-500 to-purple-500 bg-clip-text text-transparent">
                    MIS Shop Admin
                </span>
            </div>

            <!-- Navigation Links -->
            <nav class="p-4 space-y-1">
                <a href="index.php"
                    class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all <?php echo is_active('index.php', $current_page); ?>">
                    <svg class="w-[26px] h-[26px]" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z">
                        </path>
                    </svg>
                    Dashboard
                </a>

                <a href="categories.php"
                    class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all <?php echo is_active('categories.php', $current_page); ?>">
                    <svg class="w-[26px] h-[26px]" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M7 7h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Categories
                </a>

                <a href="products.php"
                    class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all <?php echo is_active('products.php', $current_page); ?>">
                    <svg class="w-[26px] h-[26px]" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    Products
                </a>

                <a href="orders.php"
                    class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all <?php echo is_active('orders.php', $current_page); ?>">
                    <svg class="w-[26px] h-[26px]" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01">
                        </path>
                    </svg>
                    Orders
                </a>
            </nav>
        </div>

        <!-- Sidebar Footer Admin Profile -->
        <div class="p-4 border-t border-slate-100 dark:border-slate-800">
            <div class="flex items-center justify-between gap-2 p-2 bg-slate-50 dark:bg-slate-950 rounded-2xl">
                <div class="flex items-center gap-2">
                    <div
                        class="w-8 h-8 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-xs">
                        <?php echo strtoupper(substr($_SESSION['admin_username'], 0, 2)); ?>
                    </div>
                    <div class="truncate w-24">
                        <p class="text-xs font-bold text-slate-700 dark:text-slate-300 leading-none truncate">
                            <?php echo htmlspecialchars($_SESSION['admin_username']); ?></p>
                        <span
                            class="text-[9px] text-slate-400 font-semibold tracking-wider uppercase leading-none">Admin</span>
                    </div>
                </div>
                <a href="../logout.php"
                    class="p-1.5 text-slate-400 hover:text-rose-500 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    title="Logout">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                        </path>
                    </svg>
                </a>
            </div>

            <a href="../index.php"
                class="mt-3 flex items-center justify-center gap-1.5 text-[11px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path>
                </svg>
                Back to Front Store
            </a>
        </div>
    </aside>

    <!-- Main Admin Workspace Container -->
    <div class="flex-grow flex flex-col min-w-0">

        <!-- Admin Bar Header -->
        <header
            class="h-16 border-b border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-slate-900 sticky top-0 z-30 px-8 flex items-center justify-between transition-colors duration-300">
            <h2 class="font-display font-extrabold text-lg text-slate-800 dark:text-slate-100">
                <?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Admin Panel'; ?>
            </h2>

            <div class="flex items-center gap-4">
                <!-- Theme Toggle Button -->
                <button id="theme-toggle"
                    class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors focus:outline-none"
                    title="Toggle Theme">
                    <span id="theme-toggle-icon"></span>
                </button>
            </div>
        </header>

        <!-- Main Workspace -->
        <main class="flex-grow p-8 max-w-6xl w-full mx-auto relative">

            <!-- Toast Container -->
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