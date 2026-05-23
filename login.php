<?php
// ============================================================
// MIS Shop - Customer Login
// ============================================================

$pageTitle = 'Login';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$login_input = ''; // can be username or email

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = trim($_POST['login_input'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login_input) || empty($password)) {
        $error = 'Please enter your login details.';
    } else {
        // Fetch user by username or email
        $login_input_escaped = mysqli_real_escape_string($conn, $login_input);
        $result = mysqli_query($conn, "SELECT * FROM users WHERE username = '$login_input_escaped' OR email = '$login_input_escaped'");
        $user = mysqli_fetch_assoc($result);

        if ($user && password_verify($password, $user['password'])) {
            // Set Session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];

            // Merge temporary session cart to database cart
            if (isset($_SESSION['temp_cart']) && !empty($_SESSION['temp_cart'])) {
                foreach ($_SESSION['temp_cart'] as $p_id => $qty) {
                    $result = mysqli_query($conn, "SELECT id, quantity FROM cart WHERE user_id = " . $user['id'] . " AND product_id = " . (int)$p_id);
                    $cart_item = mysqli_fetch_assoc($result);

                    if ($cart_item) {
                        $new_qty = $cart_item['quantity'] + $qty;
                        mysqli_query($conn, "UPDATE cart SET quantity = $new_qty WHERE id = " . $cart_item['id']);
                    } else {
                        mysqli_query($conn, "INSERT INTO cart (user_id, product_id, quantity) VALUES (" . $user['id'] . ", " . (int)$p_id . ", $qty)");
                    }
                }
                unset($_SESSION['temp_cart']);
            }

            setFlash('success', 'Logged in successfully! Welcome back, ' . $user['username'] . '.');

            // Redirect back to previous page or home
            $redirect = $_SESSION['redirect_url'] ?? 'index.php';
            unset($_SESSION['redirect_url']);
            header("Location: $redirect");
            exit;
        } else {
            $error = 'Invalid username/email or password.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="flex items-center justify-center min-h-[70vh]">
    <div class="w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200/80 dark:border-slate-800/80 p-8 transition-all duration-300">

        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="font-display font-bold text-3xl text-slate-800 dark:text-slate-100">Welcome Back</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-2">Login to manage your orders and cart</p>
        </div>

        <!-- Error Message -->
        <?php if (!empty($error)): ?>
            <div class="mb-6 px-4 py-3 rounded-xl bg-rose-50 dark:bg-rose-950/80 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-200 text-sm font-medium flex items-center gap-2">
                <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form action="login.php" method="POST" class="space-y-5">
            <div>
                <label for="login_input" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Username or Email</label>
                <input type="text" id="login_input" name="login_input" value="<?php echo htmlspecialchars($login_input); ?>" required
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-sm">
            </div>

            <div>
                <div class="flex justify-between items-center mb-1.5">
                    <label for="password" class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Password</label>
                </div>
                <input type="password" id="password" name="password" required
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-sm">
            </div>

            <button type="submit"
                class="w-full mt-2 py-3 px-4 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold rounded-xl transition-all shadow-md shadow-indigo-500/10 hover:shadow-indigo-500/20 active:scale-[0.99]">
                Log In
            </button>
        </form>

        <!-- Redirect -->
        <p class="text-center text-sm text-slate-500 dark:text-slate-400 mt-6">
            Don't have an account?
            <a href="register.php" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">Register</a>
        </p>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>