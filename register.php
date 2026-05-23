<?php
// ============================================================
// MIS Shop - Customer Registration
// ============================================================

$pageTitle = 'Register';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Check if username already exists
        $username_escaped = mysqli_real_escape_string($conn, $username);
        $result = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username_escaped'");

        if (mysqli_fetch_assoc($result)) {
            $error = 'Username is already taken.';
        } else {
            // Check if email already exists
            $email_escaped = mysqli_real_escape_string($conn, $email);
            $result = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email_escaped'");

            if (mysqli_fetch_assoc($result)) {
                $error = 'Email is already registered.';
            } else {
                // Create User
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $hashedPassword_escaped = mysqli_real_escape_string($conn, $hashedPassword);

                $query = "INSERT INTO users (username, email, password) VALUES ('$username_escaped', '$email_escaped', '$hashedPassword_escaped')";

                if (mysqli_query($conn, $query)) {
                    setFlash('success', 'Registration successful! You can now log in.');
                    header('Location: login.php');
                    exit;
                } else {
                    $error = 'An error occurred. Please try again later.';
                }
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="flex items-center justify-center min-h-[70vh]">
    <div class="w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200/80 dark:border-slate-800/80 p-8 transition-all duration-300">

        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="font-display font-bold text-3xl text-slate-800 dark:text-slate-100">Create Account</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-2">Join us and start shopping today</p>
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
        <form action="register.php" method="POST" class="space-y-5">
            <div>
                <label for="username" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-sm">
            </div>

            <div>
                <label for="email" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Email Address</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-sm">
            </div>

            <div>
                <label for="password" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Password</label>
                <input type="password" id="password" name="password" required
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-sm">
            </div>

            <div>
                <label for="confirm_password" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-sm">
            </div>

            <button type="submit"
                class="w-full mt-2 py-3 px-4 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold rounded-xl transition-all shadow-md shadow-indigo-500/10 hover:shadow-indigo-500/20 active:scale-[0.99]">
                Register
            </button>
        </form>

        <!-- Redirect -->
        <p class="text-center text-sm text-slate-500 dark:text-slate-400 mt-6">
            Already have an account?
            <a href="login.php" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">Log in</a>
        </p>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>