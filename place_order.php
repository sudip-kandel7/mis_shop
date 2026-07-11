<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cart.php');
    exit;
}

$user_id          = $_SESSION['user_id'];
$shipping_name    = trim($_POST['shipping_name'] ?? '');
$shipping_address = trim($_POST['shipping_address'] ?? '');
$shipping_phone   = trim($_POST['shipping_phone'] ?? '');

if (empty($shipping_name) || empty($shipping_address) || empty($shipping_phone)) {
    setFlash('error', 'Please fill in all delivery details.');
    header('Location: checkout.php');
    exit;
}

if (preg_match('/\d/', $shipping_name)) {
    setFlash('error', 'Name cannot contain numbers.');
    header('Location: checkout.php');
    exit;
}

$phoneClean = preg_replace('/\D/', '', $shipping_phone);
if (strlen($phoneClean) !== 10 || !preg_match('/^9[87]/', $phoneClean)) {
    setFlash('error', 'Phone must start with 98 or 97 and be exactly 10 digits.');
    header('Location: checkout.php');
    exit;
}
if (preg_match('/^(\d)\1{7}$/', substr($phoneClean, 2))) {
    setFlash('error', 'Invalid phone pattern: digits after 98/97 cannot all be the same.');
    header('Location: checkout.php');
    exit;
}

// Get selected items from POST (from checkout.php form submission)
$selectedItems = isset($_POST['selected_items']) ? (array)$_POST['selected_items'] : [];

// If no items selected, redirect back
if (empty($selectedItems)) {
    setFlash('error', 'Please select at least one item to purchase.');
    header('Location: cart.php');
    exit;
}

// Sanitize selected items
$selectedItems = array_map('intval', $selectedItems);

// 1. Fetch Selected Cart Items
$placeholders = implode(',', array_fill(0, count($selectedItems), '?'));
$stmt = $conn->prepare("
    SELECT c.quantity, p.id as product_id, p.name, p.price, p.stock 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ? AND p.id IN ($placeholders)
");

// Bind parameters
$params = array_merge([$user_id], $selectedItems);
$stmt->bind_param(str_repeat('i', count($params)), ...$params);
$stmt->execute();
$result = $stmt->get_result();

$cartItems = [];
while ($row = mysqli_fetch_assoc($result)) {
    $cartItems[] = $row;
}

if (empty($cartItems)) {
    setFlash('error', 'Your cart is empty.');
    header('Location: cart.php');
    exit;
}

// 2. Check stock & calculate total
$total = 0;
foreach ($cartItems as $item) {
    if ($item['stock'] < $item['quantity']) {
        setFlash('error', "Not enough stock for \"{$item['name']}\". Only {$item['stock']} left.");
        header('Location: cart.php');
        exit;
    }
    $total += $item['price'] * $item['quantity'];
}

// 3. Start DB transaction
mysqli_query($conn, "START TRANSACTION");

// 4. Create order with status 'pending_payment'
$sname  = mysqli_real_escape_string($conn, $shipping_name);
$saddr  = mysqli_real_escape_string($conn, $shipping_address);
$sphone = mysqli_real_escape_string($conn, $shipping_phone);

$ok = mysqli_query($conn, "INSERT INTO orders 
    (user_id, total, status, shipping_name, shipping_address, shipping_phone) 
    VALUES ($user_id, $total, 'pending_payment', '$sname', '$saddr', '$sphone')");

if (!$ok) {
    mysqli_query($conn, "ROLLBACK");
    setFlash('error', 'Failed to create order.');
    header('Location: checkout.php');
    exit;
}

$order_id = mysqli_insert_id($conn);

// 5. Insert order items (no stock deduct yet)
foreach ($cartItems as $item) {
    $ok = mysqli_query($conn, "INSERT INTO order_items 
        (order_id, product_id, quantity, price) 
        VALUES ($order_id, {$item['product_id']}, {$item['quantity']}, {$item['price']})");

    if (!$ok) {
        mysqli_query($conn, "ROLLBACK");
        setFlash('error', 'Failed to save order items.');
        header('Location: checkout.php');
        exit;
    }
}

// 6. Commit order
mysqli_query($conn, "COMMIT");

// 7. Generate transaction UUID
$transaction_uuid = $order_id . '-' . date('YmdHis');

// 8. Save transaction_uuid
mysqli_query($conn, "UPDATE orders SET transaction_uuid = '$transaction_uuid' WHERE id = $order_id");

// 9. eSewa signature — KEY FIX: use number_format so value is exact e.g. "500.00"
$secret_key      = "8gBm/:&EnhH.1/q";
$product_code    = "EPAYTEST";
$tax_amount      = "0";
$service_charge  = "0";
$delivery_charge = "0";
$total_amount    = number_format($total, 2, '.', ''); // e.g. "500.00"
$amount          = $total_amount;

$message   = "total_amount=$total_amount,transaction_uuid=$transaction_uuid,product_code=$product_code";
$signature = base64_encode(hash_hmac('sha256', $message, $secret_key, true));

// 10. URLs
$base_url    = "http://localhost/mis_shop";
$success_url = "$base_url/esewa-success.php";
$failure_url = "$base_url/esewa-failure.php";

?>
<!DOCTYPE html>
<html>

<head>
    <title>Redirecting to eSewa...</title>
</head>

<body onload="document.getElementById('esewa_form').submit()">
    <p style="text-align:center; font-family:sans-serif; margin-top:50px;">
        Redirecting to eSewa payment... please wait.
    </p>
    <form id="esewa_form" action="https://rc-epay.esewa.com.np/api/epay/main/v2/form" method="POST">
        <input type="hidden" name="amount" value="<?php echo $amount; ?>">
        <input type="hidden" name="tax_amount" value="<?php echo $tax_amount; ?>">
        <input type="hidden" name="total_amount" value="<?php echo $total_amount; ?>">
        <input type="hidden" name="transaction_uuid" value="<?php echo $transaction_uuid; ?>">
        <input type="hidden" name="product_code" value="<?php echo $product_code; ?>">
        <input type="hidden" name="product_service_charge" value="<?php echo $service_charge; ?>">
        <input type="hidden" name="product_delivery_charge" value="<?php echo $delivery_charge; ?>">
        <input type="hidden" name="success_url" value="<?php echo $success_url; ?>">
        <input type="hidden" name="failure_url" value="<?php echo $failure_url; ?>">
        <input type="hidden" name="signed_field_names" value="total_amount,transaction_uuid,product_code">
        <input type="hidden" name="signature" value="<?php echo $signature; ?>">
    </form>
</body>

</html>