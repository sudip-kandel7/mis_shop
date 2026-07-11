<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Redirect if already logged in
if (isAdminLoggedIn()) {
    header('Location: index.php');
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $login_input = trim($_POST['login_input'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login_input) || empty($password)) {

        $error = 'All fields are required.';
    } else {

        $stmt = mysqli_prepare(
            $conn,
            "SELECT * FROM users
             WHERE (username = ? OR email = ?)
             AND role = 'owner'
             LIMIT 1"
        );

        mysqli_stmt_bind_param($stmt, "ss", $login_input, $login_input);

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $user = mysqli_fetch_assoc($result);

        if ($user) {

            // Check if banned
            if ($user['is_banned']) {

                $error = 'Your account has been banned.';
            } elseif (password_verify($password, $user['password'])) {

                // Admin Session
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];

                // Normal User Session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                setFlash('success', "Welcome back, {$user['username']}.");

                header('Location: index.php');
                exit;
            } else {

                $error = 'Invalid username or password.';
            }
        } else {

            $error = 'Invalid username or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Login | MIS Shop</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

</head>

<body class="font-sans min-h-screen flex items-center justify-center bg-slate-900 text-slate-100 p-4">

    <div class="w-full max-w-md bg-slate-800 rounded-3xl shadow-2xl border border-slate-700/80 p-8 space-y-6">

        <!-- Header -->
        <div class="text-center space-y-2">

            <div
                class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-500 to-violet-600 flex items-center justify-center text-white text-xl font-black shadow-lg shadow-indigo-500/20 mx-auto">
                M
            </div>

            <h1 class="font-extrabold text-2xl tracking-tight">
                MIS Shop Owner Panel
            </h1>

            <p class="text-xs text-slate-400 font-semibold tracking-wider uppercase">
                Secure Login
            </p>

        </div>

        <!-- Error -->
        <?php if (!empty($error)): ?>

            <div class="px-4 py-3 rounded-2xl bg-rose-950/80 border border-rose-800 text-rose-200 text-xs font-semibold">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <!-- Form -->
        <form method="POST" class="space-y-4">

            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">
                    Username or Email
                </label>

                <input type="text" name="login_input" value="<?php echo htmlspecialchars($login_input ?? ''); ?>"
                    required
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-700 bg-slate-900/50 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">
                    Password
                </label>

                <input type="password" name="password" required
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-700 bg-slate-900/50 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm">
            </div>

            <button type="submit"
                class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl transition-all shadow-md shadow-indigo-500/10">

                Access Dashboard

            </button>

        </form>

        <div class="text-center pt-2">

            <a href="../index.php" class="text-xs font-bold text-indigo-400 hover:underline">
                &larr; Back to Store
            </a>

        </div>

    </div>

</body>

</html>