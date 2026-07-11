<?php

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

if ($product_id <= 0 || $quantity <= 0) {
    setFlash('error', 'Invalid product.');
    header('Location: index.php');
    exit;
}

$result = mysqli_query($conn, "SELECT stock, name FROM products WHERE id = $product_id");
$product = mysqli_fetch_assoc($result);

if (!$product) {
    setFlash('error', 'Product not found.');
    header('Location: index.php');
    exit;
}

if ($product['stock'] < $quantity) {
    setFlash('error', 'Not enough stock available.');
    header('Location: product.php?id=' . $product_id);
    exit;
}

// Add to cart
$result = mysqli_query($conn, "SELECT id, quantity FROM cart WHERE user_id = " . $_SESSION['user_id'] . " AND product_id = $product_id");
$cartItem = mysqli_fetch_assoc($result);

if ($cartItem) {
    $newQty = $cartItem['quantity'] + $quantity;
    if ($newQty > $product['stock']) $newQty = $product['stock'];
    mysqli_query($conn, "UPDATE cart SET quantity = $newQty WHERE id = " . $cartItem['id']);
} else {
    mysqli_query($conn, "INSERT INTO cart (user_id, product_id, quantity) VALUES (" . $_SESSION['user_id'] . ", $product_id, $quantity)");
}

// Set flag for checkout to pre-select this item
$_SESSION['buy_now_product'] = $product_id;

setFlash('success', 'Proceeding to checkout...');
header('Location: checkout.php');
exit;
