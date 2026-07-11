<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireOwner();
require_once __DIR__ . '/../includes/auth.php';

if (!isAdminLoggedIn()) {
    setFlash('error', 'Administrator access required. Please log in.');
    header('Location: login.php');
    exit;
}

// -----------------------------------------------------------
// Business Logic (before header to allow redirects)
// -----------------------------------------------------------

$pageTitle = 'Manage Products';
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

$editProduct = null;
$error = '';

$uploadDir = __DIR__ . '/../img/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$result = mysqli_query($conn, "SELECT * FROM categories ORDER BY name ASC");
$categories = [];
while ($row = mysqli_fetch_assoc($result)) {
    $categories[] = $row;
}

// Save Product (Add or Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save') {

    $name = trim($_POST['name'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0.00);
    $stock = (int)($_POST['stock'] ?? 0);
    $id = (int)($_POST['id'] ?? 0);

    if (empty($name) || $category_id <= 0 || $price < 0 || $stock < 0) {
        $error = 'Please fill out all required fields with valid values.';
    } else {
        $imageName = $_POST['existing_image'] ?? null;

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['image']['tmp_name'];
            $fileName = $_FILES['image']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($fileExtension, $allowedExtensions)) {
                $newFileName = uniqid('prod_', true) . '.' . $fileExtension;
                if (move_uploaded_file($fileTmpPath, $uploadDir . $newFileName)) {
                    if ($imageName && file_exists($uploadDir . $imageName)) {
                        unlink($uploadDir . $imageName);
                    }
                    $imageName = $newFileName;
                } else {
                    $error = 'Error moving uploaded image file.';
                }
            } else {
                $error = 'Invalid image file type. Allowed: jpg, jpeg, png, gif, webp.';
            }
        }

        if (empty($error)) {
            $name = mysqli_real_escape_string($conn, $name);
            $description = mysqli_real_escape_string($conn, $description);
            $imageName_esc = mysqli_real_escape_string($conn, $imageName);

            if ($id > 0) {
                $query = "UPDATE products SET category_id = $category_id, name = '$name', description = '$description', price = $price, image = '$imageName_esc', stock = $stock WHERE id = $id";
                if (mysqli_query($conn, $query)) {
                    setFlash('success', 'Product updated successfully.');
                    header('Location: products.php');
                    exit;
                } else {
                    $error = 'Database error: ' . mysqli_error($conn);
                }
            } else {
                $query = "INSERT INTO products (category_id, name, description, price, image, stock) VALUES ($category_id, '$name', '$description', $price, '$imageName_esc', $stock)";
                if (mysqli_query($conn, $query)) {
                    setFlash('success', 'Product added successfully.');
                    header('Location: products.php');
                    exit;
                } else {
                    $error = 'Database error: ' . mysqli_error($conn);
                }
            }
        }
    }

    if (!empty($error)) {
        $submittedId = (int)$_POST['id'];
        if ($submittedId > 0) {
            $editProduct = [
                'id' => $submittedId,
                'name' => $_POST['name'] ?? '',
                'category_id' => (int)($_POST['category_id'] ?? 0),
                'description' => $_POST['description'] ?? '',
                'price' => (float)($_POST['price'] ?? 0),
                'stock' => (int)($_POST['stock'] ?? 0),
                'image' => $_POST['existing_image'] ?? '',
            ];
        } else {
            $action = 'new';
        }
    }
}

// Delete Product
if ($action === 'delete') {
    $delete_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($delete_id > 0) {
        $result = mysqli_query($conn, "SELECT image FROM products WHERE id = $delete_id");
        $row = mysqli_fetch_assoc($result);
        $img = $row ? $row['image'] : null;
        if ($img && file_exists($uploadDir . $img)) {
            unlink($uploadDir . $img);
        }
        if (mysqli_query($conn, "DELETE FROM products WHERE id = $delete_id")) {
            setFlash('success', 'Product deleted successfully.');
        } else {
            setFlash('error', 'Error deleting product: ' . mysqli_error($conn));
        }
    }
}

// Fetch single product for Edit Form
if ($edit_id > 0 && !$editProduct) {
    $result = mysqli_query($conn, "SELECT * FROM products WHERE id = $edit_id");
    $editProduct = mysqli_fetch_assoc($result);
    if (!$editProduct) {
        setFlash('error', 'Product not found.');
        header('Location: products.php');
        exit;
    }
}

// Fetch all products with categories
$result = mysqli_query($conn, "
    SELECT p.*, c.name as category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    ORDER BY p.created_at DESC
");
$products = [];
while ($row = mysqli_fetch_assoc($result)) {
    $products[] = $row;
}

// -----------------------------------------------------------
// Output starts here
// -----------------------------------------------------------
require_once __DIR__ . '/includes/header.php';
?>

<!-- Render Form if adding or editing -->
<?php if ($action === 'new' || $editProduct): ?>

    <div
        class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6 max-w-2xl mx-auto">
        <div class="flex items-center justify-between">
            <h3 class="font-display font-extrabold text-xl text-slate-800 dark:text-slate-100 tracking-tight">
                <?php echo $editProduct ? 'Edit Product Details' : 'Create Product'; ?>
            </h3>
            <a href="products.php" class="text-xs font-bold text-slate-400 hover:underline">&larr; Back to Catalog</a>
        </div>

        <?php if (!empty($error)): ?>
            <div
                class="px-4 py-2.5 rounded-xl bg-rose-50 dark:bg-rose-950/80 border border-rose-100 dark:border-rose-800 text-rose-800 dark:text-rose-200 text-xs font-semibold">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="products.php" method="POST" enctype="multipart/form-data" class="space-y-5">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?php echo $editProduct ? $editProduct['id'] : 0; ?>">
            <input type="hidden" name="existing_image" value="<?php echo $editProduct ? $editProduct['image'] : ''; ?>">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label for="name"
                        class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Product
                        Name *</label>
                    <input type="text" id="name" name="name" required
                        value="<?php echo htmlspecialchars($editProduct ? $editProduct['name'] : ''); ?>"
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-sm">
                </div>

                <div>
                    <label for="category_id"
                        class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Category
                        *</label>
                    <select id="category_id" name="category_id" required
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-sm">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"
                                <?php echo ($editProduct && $editProduct['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="price"
                            class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Price
                            ($) *</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" required
                            value="<?php echo $editProduct ? $editProduct['price'] : '0.00'; ?>"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-sm">
                    </div>
                    <div>
                        <label for="stock"
                            class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Stock
                            *</label>
                        <input type="number" id="stock" name="stock" min="0" required
                            value="<?php echo $editProduct ? $editProduct['stock'] : '0'; ?>"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-sm">
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <label for="description"
                        class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Description</label>
                    <textarea id="description" name="description" rows="4"
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-sm"><?php echo htmlspecialchars($editProduct ? $editProduct['description'] : ''); ?></textarea>
                </div>

                <div class="sm:col-span-2 space-y-2">
                    <label for="image"
                        class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Product
                        Image</label>

                    <?php if ($editProduct && !empty($editProduct['image'])): ?>
                        <div class="flex items-center gap-4 mb-2">
                            <div class="w-14 h-14 rounded-lg overflow-hidden border border-slate-200 dark:border-slate-800">
                                <img src="../img/<?php echo htmlspecialchars($editProduct['image']); ?>"
                                    class="w-full h-full object-cover">
                            </div>
                            <span class="text-xs text-slate-400">Current Image:
                                <?php echo htmlspecialchars($editProduct['image']); ?></span>
                        </div>
                    <?php endif; ?>

                    <input type="file" id="image" name="image" accept="image/*"
                        class="w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-600 dark:file:bg-indigo-950 dark:file:text-indigo-400 hover:file:opacity-90 file:cursor-pointer">
                </div>
            </div>

            <button type="submit"
                class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl transition-all shadow-md shadow-indigo-500/10 active:scale-[0.99] text-sm mt-4">
                Save Product Details
            </button>
        </form>
    </div>

<?php else: ?>

    <!-- Product List (Default state) -->
    <div
        class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-3xl p-6 shadow-sm space-y-6">
        <div class="flex justify-between items-center flex-wrap gap-4">
            <div>
                <h3 class="font-display font-extrabold text-lg text-slate-800 dark:text-slate-100 tracking-tight">Product
                    Catalog</h3>
                <p class="text-slate-400 text-xs font-medium">Manage product inventory list, pricing, images, and category
                    assignments.</p>
            </div>

            <a href="products.php?action=new"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl shadow-md transition-all">
                + Add Product
            </a>
        </div>

        <?php if (empty($products)): ?>
            <p class="text-slate-500 dark:text-slate-400 text-sm text-center py-6">No products registered yet. Click "+ Add
                Product" to create one.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead>
                        <tr
                            class="border-b border-slate-100 dark:border-slate-800 text-xs font-bold text-slate-400 uppercase tracking-wider">
                            <th class="pb-3.5 pl-2">Product</th>
                            <th class="pb-3.5">Category</th>
                            <th class="pb-3.5">Price</th>
                            <th class="pb-3.5 text-center">Stock</th>
                            <th class="pb-3.5 text-right pr-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <?php foreach ($products as $prod): ?>
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/50 transition-colors">
                                <td class="py-3.5 pl-2 flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 rounded-lg overflow-hidden bg-slate-50 dark:bg-slate-950 shrink-0 border border-slate-100 dark:border-slate-800/50">
                                        <?php if (!empty($prod['image']) && file_exists($uploadDir . $prod['image'])): ?>
                                            <img src="../img/<?php echo htmlspecialchars($prod['image']); ?>"
                                                class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <div
                                                class="w-full h-full bg-gradient-to-tr from-indigo-500/10 via-purple-500/10 to-pink-500/10 flex items-center justify-center text-slate-400">
                                                <svg class="w-4 h-4 stroke-[1.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375 0 11-.75 0 .375 0 01.75 0z">
                                                    </path>
                                                </svg>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <span
                                        class="font-semibold text-slate-800 dark:text-slate-100 line-clamp-1 max-w-[200px]"><?php echo htmlspecialchars($prod['name']); ?></span>
                                </td>

                                <td class="py-3.5 text-slate-500 dark:text-slate-400 font-medium text-xs">
                                    <?php echo htmlspecialchars($prod['category_name']); ?></td>

                                <td class="py-3.5 font-bold text-slate-800 dark:text-slate-200">
                                    Rs.<?php echo number_format($prod['price'], 2); ?></td>

                                <td class="py-3.5 text-center">
                                    <?php if ($prod['stock'] == 0): ?>
                                        <span
                                            class="inline-flex px-2 py-1 rounded bg-rose-500/10 text-rose-500 text-[10px] font-bold uppercase tracking-wider">Out
                                            of stock</span>
                                    <?php elseif ($prod['stock'] <= 5): ?>
                                        <span
                                            class="inline-flex px-2 py-1 rounded bg-amber-500/10 text-amber-500 text-[10px] font-bold uppercase tracking-wider">Low
                                            (<?php echo $prod['stock']; ?>)</span>
                                    <?php else: ?>
                                        <span
                                            class="inline-flex px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[10px] font-bold uppercase tracking-wider"><?php echo $prod['stock']; ?></span>
                                    <?php endif; ?>
                                </td>

                                <td class="py-3.5 text-right pr-2 space-x-1 shrink-0">
                                    <a href="products.php?edit=<?php echo $prod['id']; ?>"
                                        class="inline-block text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">Edit</a>
                                    <span class="text-slate-200 dark:text-slate-700">|</span>
                                    <a href="products.php?action=delete&id=<?php echo $prod['id']; ?>"
                                        onclick="return confirm('Are you sure you want to delete this product?')"
                                        class="inline-block text-xs font-bold text-rose-500 hover:underline">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
