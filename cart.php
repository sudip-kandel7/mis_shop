<?php


require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// Only customers can access cart
if (isLoggedIn() && isOwner()) {
    setFlash('error', 'Access denied. This page is for customers only.');
    header('Location: index.php');
    exit;
}

// Require login for add to cart action
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['action'])) {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    if ($action === 'add' && !isLoggedIn()) {
        setFlash('error', 'Please log in to add items to cart.');
        header('Location: login.php');
        exit;
    }
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $action === 'remove' || $action === 'clear') {

    // Add Item
    if ($action === 'add') {
        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

        
        if ($product_id > 0 && $quantity > 0) {
            // Verify product exists and check stock
            $result = mysqli_query($conn, "SELECT stock, name FROM products WHERE id = $product_id");
            $product = mysqli_fetch_assoc($result);

            if ($product) {
                if ($product['stock'] < $quantity) {
                    $quantity = $product['stock'];
                    setFlash('warning', "Only {$product['stock']} items available. Cart quantity adjusted.");
                }

                if (isLoggedIn()) {
                    // Check if already in cart
                    $result = mysqli_query($conn, "SELECT id, quantity FROM cart WHERE user_id = " . $_SESSION['user_id'] . " AND product_id = $product_id");
                    $cartItem = mysqli_fetch_assoc($result);

                    if ($cartItem) {
                        $newQty = $cartItem['quantity'] + $quantity;
                        if ($newQty > $product['stock']) $newQty = $product['stock'];

                        mysqli_query($conn, "UPDATE cart SET quantity = $newQty WHERE id = " . $cartItem['id']);
                    } else {
                        mysqli_query($conn, "INSERT INTO cart (user_id, product_id, quantity) VALUES (" . $_SESSION['user_id'] . ", $product_id, $quantity)");
                    }
                } else {
                    // Guest Cart (Session)
                    if (!isset($_SESSION['temp_cart'])) {
                        $_SESSION['temp_cart'] = [];
                    }
                    if (isset($_SESSION['temp_cart'][$product_id])) {
                        $_SESSION['temp_cart'][$product_id] += $quantity;
                        if ($_SESSION['temp_cart'][$product_id] > $product['stock']) {
                            $_SESSION['temp_cart'][$product_id] = $product['stock'];
                        }
                    } else {
                        $_SESSION['temp_cart'][$product_id] = $quantity;
                    }
                }
                setFlash('success', "Added \"{$product['name']}\" to cart.");
            } else {
                setFlash('error', "Product not found.");
            }
        }
        header('Location: cart.php');
        exit;
    }

    // Update Quantity
    if ($action === 'update') {
        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

        if ($product_id > 0 && $quantity > 0) {
            // Verify stock
            $result = mysqli_query($conn, "SELECT stock FROM products WHERE id = $product_id");
            $product = mysqli_fetch_assoc($result);

            if ($product) {
                if ($quantity > $product['stock']) {
                    $quantity = $product['stock'];
                    setFlash('warning', "Selected quantity exceeds available stock. Adjusted to max.");
                }

                if (isLoggedIn()) {
                    mysqli_query($conn, "UPDATE cart SET quantity = $quantity WHERE user_id = " . $_SESSION['user_id'] . " AND product_id = $product_id");
                } else {
                    $_SESSION['temp_cart'][$product_id] = $quantity;
                }
                setFlash('success', "Cart updated.");
            }
        }
        header('Location: cart.php');
        exit;
    }

    // Remove Item
    if ($action === 'remove') {
        $product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

        if ($product_id > 0) {
            if (isLoggedIn()) {
                mysqli_query($conn, "DELETE FROM cart WHERE user_id = " . $_SESSION['user_id'] . " AND product_id = $product_id");
            } else {
                unset($_SESSION['temp_cart'][$product_id]);
            }
            setFlash('success', "Item removed from cart.");
        }
        header('Location: cart.php');
        exit;
    }

    // Clear Cart
    if ($action === 'clear') {
        if (isLoggedIn()) {
            mysqli_query($conn, "DELETE FROM cart WHERE user_id = " . $_SESSION['user_id']);
        } else {
            $_SESSION['temp_cart'] = [];
        }
        setFlash('success', "Cart cleared.");
        header('Location: cart.php');
        exit;
    }
}

// -----------------------------------------------------------
// Fetch Cart Data
// -----------------------------------------------------------

$cartItems = [];
$subtotal = 0;

if (isLoggedIn()) {
    $result = mysqli_query($conn, "
        SELECT c.quantity, p.id as product_id, p.name, p.price, p.image, p.stock 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = " . $_SESSION['user_id']);

    $cartItems = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $cartItems[] = $row;
    }
} else {
    if (isset($_SESSION['temp_cart']) && !empty($_SESSION['temp_cart'])) {
        $ids = array_keys($_SESSION['temp_cart']);
        $ids_str = implode(',', $ids);

        $result = mysqli_query($conn, "SELECT id as product_id, name, price, image, stock FROM products WHERE id IN ($ids_str)");

        $cartItems = [];
        while ($prod = mysqli_fetch_assoc($result)) {
            $qty = $_SESSION['temp_cart'][$prod['product_id']];
            $prod['quantity'] = $qty;
            $cartItems[] = $prod;
        }
    }
}


// Calculate subtotal
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$pageTitle = 'Shopping Cart';
require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-8">
    <div>
        <h1 class="font-display font-extrabold text-3xl sm:text-4xl text-slate-800 dark:text-slate-100 tracking-tight">
            Shopping Cart</h1>
        <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Manage items selected for purchase</p>
    </div>

    <?php if (empty($cartItems)): ?>
    <!-- Empty State -->
    <div
        class="text-center py-24 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800/80 shadow-sm max-w-lg mx-auto space-y-6">
        <div
            class="w-20 h-20 rounded-full bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center mx-auto text-indigo-500">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z">
                </path>
            </svg>
        </div>
        <div class="space-y-2">
            <h3 class="font-display font-extrabold text-xl text-slate-800 dark:text-slate-200">Your cart is empty</h3>
            <p class="text-slate-500 dark:text-slate-400 text-sm max-w-xs mx-auto">Looks like you haven't added anything
                to your cart yet. Let's find some amazing items!</p>
        </div>
        <a href="index.php"
            class="inline-flex px-6 py-3 bg-indigo-600 hover:bg-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white font-bold rounded-xl shadow-md transition-all">
            Continue Shopping
        </a>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        <!-- Cart Items List (LG: col-span-8) -->
        <div class="lg:col-span-8 space-y-4">
            <form id="cart-form" method="POST" action="checkout.php" class="space-y-4">
                <div
                    class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800/80 shadow-sm overflow-hidden">
                    <div
                        class="p-6 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50">
                        <span class="font-bold text-sm text-slate-500 uppercase tracking-wider">Product Summary</span>
                        <a href="cart.php?action=clear"
                            onclick="return confirm('Are you sure you want to clear your cart?')"
                            class="text-xs font-bold text-rose-500 hover:underline">Clear Cart</a>
                    </div>

                    <div class="divide-y divide-slate-100 dark:divide-slate-800">
                        <?php foreach ($cartItems as $item): ?>
                        <div class="p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-6 cart-item"
                            data-price="<?php echo $item['price']; ?>" data-quantity="<?php echo $item['quantity']; ?>">
                            <!-- Checkbox -->
                            <div class="flex items-center gap-4">
                                <input type="checkbox" name="selected_items[]"
                                    value="<?php echo $item['product_id']; ?>"
                                    class="w-5 h-5 rounded border-slate-300 text-emerald-600 focus:ring-2 focus:ring-emerald-500 cursor-pointer item-checkbox"
                                    checked>

                                <!-- Info details -->
                                <div
                                    class="w-16 h-16 rounded-xl overflow-hidden bg-slate-50 dark:bg-slate-950 shrink-0 border border-slate-100 dark:border-slate-800/50">
                                    <?php if (!empty($item['image']) && file_exists(__DIR__ . '/img/' . $item['image'])): ?>
                                    <img src="img/<?php echo htmlspecialchars($item['image']); ?>"
                                        alt="<?php echo htmlspecialchars($item['name']); ?>"
                                        class="w-full h-full object-cover">
                                    <?php else: ?>
                                    <div
                                        class="w-full h-full bg-gradient-to-tr from-indigo-500/10 via-purple-500/10 to-pink-500/10 flex items-center justify-center text-slate-400">
                                        <svg class="w-6 h-6 stroke-[1.5]" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375 0 11-.75 0 .375 0 01.75 0z">
                                            </path>
                                        </svg>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h3
                                        class="font-bold text-slate-800 dark:text-slate-100 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                        <a
                                            href="product.php?id=<?php echo $item['product_id']; ?>"><?php echo htmlspecialchars($item['name']); ?></a>
                                    </h3>
                                    <p class="text-xs text-slate-400 mt-0.5">Unit Price:
                                        Rs.<?php echo number_format($item['price'], 2); ?></p>
                                </div>
                            </div>

                            <!-- Quantity and Actions -->
                            <div class="flex items-center justify-between sm:justify-end gap-6 w-full sm:w-auto">
                                <!-- Qty Selector -->
                                <form action="cart.php?action=update" method="POST"
                                    class="flex items-center border border-slate-200 dark:border-slate-800 rounded-lg overflow-hidden bg-slate-50 dark:bg-slate-900 w-28 h-9 shrink-0">
                                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                    <button type="submit" name="quantity"
                                        value="<?php echo max(1, $item['quantity'] - 1); ?>"
                                        class="w-8 h-full flex items-center justify-center font-bold hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500"
                                        <?php echo ($item['quantity'] <= 1) ? 'disabled' : ''; ?>>-</button>
                                    <span
                                        class="w-12 h-full flex items-center justify-center text-xs font-semibold select-none"><?php echo $item['quantity']; ?></span>
                                    <button type="submit" name="quantity"
                                        value="<?php echo min($item['stock'], $item['quantity'] + 1); ?>"
                                        class="w-8 h-full flex items-center justify-center font-bold hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500"
                                        <?php echo ($item['quantity'] >= $item['stock']) ? 'disabled' : ''; ?>>+</button>
                                </form>

                                <!-- Price calculations -->
                                <div class="text-right min-w-[70px]">
                                    <p class="font-display font-extrabold text-sm text-slate-800 dark:text-slate-200">
                                        Rs.<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                    </p>
                                </div>

                                <!-- Delete Button -->
                                <button
                                    onclick="if(confirm('Remove this item from cart?')) window.location.href='cart.php?action=remove&product_id=<?php echo $item['product_id']; ?>'"
                                    class="px-3 py-2 bg-rose-50 dark:bg-rose-950/30 text-rose-600 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-950/50 rounded-lg transition-colors font-semibold text-sm flex items-center gap-1.5"
                                    title="Remove Item from Cart">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                        </path>
                                    </svg>
                                    Remove
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </form>
        </div>

        <!-- Checkout Box (LG: col-span-4) -->
        <div
            class="lg:col-span-4 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800/80 shadow-sm p-6 space-y-6">
            <h3 class="font-display font-extrabold text-lg text-slate-800 dark:text-slate-100 tracking-tight">Order
                Summary</h3>

            <div class="space-y-3.5 text-sm">
                <div class="flex justify-between text-slate-500">
                    <span>Subtotal</span>
                    <span id="selected-subtotal"
                        class="font-semibold text-slate-800 dark:text-slate-200">Rs.<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="flex justify-between text-slate-500">
                    <span>Shipping</span>
                    <span class="font-semibold text-slate-800 dark:text-slate-200">Free</span>
                </div>
                <hr class="border-slate-100 dark:border-slate-800">
                <div class="flex justify-between font-display text-base">
                    <span class="font-bold text-slate-800 dark:text-slate-100">Estimated Total</span>
                    <span id="selected-total"
                        class="font-extrabold text-lg text-indigo-600 dark:text-indigo-400">Rs.<?php echo number_format($subtotal, 2); ?></span>
                </div>
            </div>

            <button form="cart-form" type="submit"
                class="w-full h-11 flex items-center justify-center bg-emerald-600 hover:bg-emerald-500 dark:bg-emerald-600 dark:hover:bg-emerald-500 text-white font-bold rounded-xl transition-all shadow-md shadow-emerald-500/10 hover:shadow-emerald-500/20 active:scale-[0.99]">
                Buy Selected Items
            </button>

            <div class="flex items-center gap-2.5 justify-center text-xs text-slate-400">
                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                    </path>
                </svg>
                <span>Secure Checkout Guarantee</span>
            </div>
        </div>

    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
// Calculate total based on selected items only
function updateTotal() {
    let selectedTotal = 0;
    const checkboxes = document.querySelectorAll('.item-checkbox:checked');

    checkboxes.forEach(checkbox => {
        const cartItem = checkbox.closest('.cart-item');
        const price = parseFloat(cartItem.getAttribute('data-price'));
        const quantity = parseInt(cartItem.getAttribute('data-quantity'));
        selectedTotal += price * quantity;
    });

    // Format and update the display
    const formatted = selectedTotal.toFixed(2);
    document.getElementById('selected-subtotal').textContent = 'Rs.' + parseFloat(formatted).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    document.getElementById('selected-total').textContent = 'Rs.' + parseFloat(formatted).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Add event listeners to all checkboxes
document.querySelectorAll('.item-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateTotal);
});

// Initialize on page load
updateTotal();
</script>