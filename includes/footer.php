<?php
// ============================================================
// MIS Shop - Page Footer Template
// ============================================================
?>
</main>

<!-- Footer -->
<footer
    class="bg-white dark:bg-slate-900 border-t border-slate-200/80 dark:border-slate-800/80 text-slate-600 dark:text-slate-400 text-sm mt-auto transition-colors duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- Brand section -->
            <div class="md:col-span-2 space-y-4">
                <a href="index.php" class="flex items-center gap-2">
                    <div
                        class="w-8 h-8 rounded-lg bg-gradient-to-tr from-indigo-500 to-violet-600 flex items-center justify-center text-white shadow-md">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                    </div>
                    <span
                        class="font-display font-extrabold text-lg bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 bg-clip-text text-transparent">
                        MIS Shop
                    </span>
                </a>
                <p class="max-w-xs text-slate-500 dark:text-slate-400 leading-relaxed text-xs">
                    A modern, high-performance web storefront designed for speed, beauty, and seamless management of
                    custom products.
                </p>
            </div>

            <!-- Quick Links -->
            <div>
                <h3 class="font-semibold text-slate-800 dark:text-slate-200 mb-3.5 tracking-wider uppercase text-xs">
                    Shop</h3>
                <ul class="space-y-2">
                    <li><a href="index.php"
                            class="hover:text-indigo-500 dark:hover:text-indigo-400 transition-colors">Catalog</a></li>
                    <?php if (!isOwner()): ?>
                    <li><a href="cart.php"
                            class="hover:text-indigo-500 dark:hover:text-indigo-400 transition-colors">Shopping Cart</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Account Links -->
            <div>
                <h3 class="font-semibold text-slate-800 dark:text-slate-200 mb-3.5 tracking-wider uppercase text-xs">
                    Account</h3>
                <ul class="space-y-2">
                    <?php if (isLoggedIn()): ?>
                    <?php if (!isOwner()): ?>
                    <li><a href="orders.php"
                            class="hover:text-indigo-500 dark:hover:text-indigo-400 transition-colors">My Orders</a>
                    </li>
                    <?php endif; ?>
                    <li><a href="logout.php"
                            class="hover:text-indigo-500 dark:hover:text-indigo-400 transition-colors text-rose-500">Logout</a>
                    </li>
                    <?php else: ?>
                    <li><a href="login.php"
                            class="hover:text-indigo-500 dark:hover:text-indigo-400 transition-colors">Login</a></li>
                    <li><a href="register.php"
                            class="hover:text-indigo-500 dark:hover:text-indigo-400 transition-colors">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div
            class="border-t border-slate-200/80 dark:border-slate-800/80 mt-10 pt-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500 dark:text-slate-400">
            <p>&copy; <?php echo date('Y'); ?> MIS Shop. All rights reserved.</p>
        </div>
    </div>
</footer>

<!-- Global JavaScript File -->
<script src="assets/js/main.js"></script>
</body>

</html>