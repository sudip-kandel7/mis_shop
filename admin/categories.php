<?php


require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Only owners/admins can access
requireOwner();

$pageTitle = 'Manage Categories';
require_once __DIR__ . '/includes/header.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

$editCategory = null;
$error = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Add Category
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($name)) {
            $error = 'Category name cannot be empty.';
        } else {
            $name = mysqli_real_escape_string($conn, $name);
            $description = mysqli_real_escape_string($conn, $description);
            $query = "INSERT INTO categories (name, description) VALUES ('$name', '$description')";
            if (mysqli_query($conn, $query)) {
                setFlash('success', "Category \"{$name}\" added successfully.");
                header('Location: categories.php');
                exit;
            } else {
                $error = 'Category already exists or database error.';
            }
        }
    }

    // Edit Category
    if ($action === 'edit' && $edit_id > 0) {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($name)) {
            $error = 'Category name cannot be empty.';
        } else {
            $name = mysqli_real_escape_string($conn, $name);
            $description = mysqli_real_escape_string($conn, $description);
            $query = "UPDATE categories SET name = '$name', description = '$description' WHERE id = $edit_id";
            if (mysqli_query($conn, $query)) {
                setFlash('success', "Category updated successfully.");
                header('Location: categories.php');
                exit;
            } else {
                $error = 'Category name must be unique or database error.';
            }
        }
    }
}



// Delete Category
if ($action === 'delete') {
    $delete_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($delete_id > 0) {
        if (mysqli_query($conn, "DELETE FROM categories WHERE id = $delete_id")) {
            setFlash('success', 'Category deleted successfully.');
        } else {
            setFlash('error', 'Error deleting category: ' . mysqli_error($conn));
        }
    }
    header('Location: categories.php');
    exit;
}

// Fetch details for editing
if ($edit_id > 0) {
    $result = mysqli_query($conn, "SELECT * FROM categories WHERE id = $edit_id");
    $editCategory = mysqli_fetch_assoc($result);
    if (!$editCategory) {
        setFlash('error', 'Category not found.');
        header('Location: categories.php');
        exit;
    }
}

// Fetch all categories
$result = mysqli_query($conn, "SELECT c.*, COUNT(p.id) as product_count FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id ORDER BY c.name ASC");
$categories = [];
while ($row = mysqli_fetch_assoc($result)) {
    $categories[] = $row;
}
?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

    <!-- Category List (LG: col-span-8) -->
    <div
        class="lg:col-span-8 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-3xl p-6 shadow-sm space-y-6">
        <h3 class="font-display font-extrabold text-lg text-slate-800 dark:text-slate-100 tracking-tight">Active
            Categories</h3>

        <?php if (empty($categories)): ?>
            <p class="text-slate-500 dark:text-slate-400 text-sm text-center py-6">No categories defined yet.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead>
                        <tr
                            class="border-b border-slate-100 dark:border-slate-800 text-xs font-bold text-slate-400 uppercase tracking-wider">
                            <th class="pb-3.5 pl-2">Name</th>
                            <th class="pb-3.5">Description</th>
                            <th class="pb-3.5 text-center">Products</th>
                            <th class="pb-3.5 text-right pr-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <?php foreach ($categories as $cat): ?>
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/50 transition-colors">
                                <td class="py-3.5 pl-2 font-semibold text-slate-800 dark:text-slate-100">
                                    <?php echo htmlspecialchars($cat['name']); ?></td>
                                <td class="py-3.5 text-slate-500 dark:text-slate-400 max-w-xs truncate text-xs">
                                    <?php echo htmlspecialchars($cat['description'] ?? ''); ?></td>
                                <td class="py-3.5 text-center font-bold text-slate-700 dark:text-slate-300">
                                    <span
                                        class="inline-flex px-2 py-1 bg-slate-100 dark:bg-slate-800 rounded-lg text-xs font-semibold">
                                        <?php echo $cat['product_count']; ?>
                                    </span>
                                </td>
                                <td class="py-3.5 text-right pr-2 space-x-1 shrink-0">
                                    <a href="categories.php?edit=<?php echo $cat['id']; ?>"
                                        class="inline-block text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">Edit</a>
                                    <span class="text-slate-200 dark:text-slate-700">|</span>
                                    <a href="categories.php?action=delete&id=<?php echo $cat['id']; ?>"
                                        onclick="return confirm('Are you sure you want to delete this category? All associated products will be deleted as well!')"
                                        class="inline-block text-xs font-bold text-rose-500 hover:underline">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Category Form (LG: col-span-4) -->
    <div
        class="lg:col-span-4 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-3xl p-6 shadow-sm space-y-6">
        <h3 class="font-display font-extrabold text-lg text-slate-800 dark:text-slate-100 tracking-tight">
            <?php echo $editCategory ? 'Edit Category' : 'Create Category'; ?>
        </h3>

        <!-- Form error message -->
        <?php if (!empty($error)): ?>
            <div
                class="px-4 py-2.5 rounded-xl bg-rose-50 dark:bg-rose-950/80 border border-rose-100 dark:border-rose-800 text-rose-800 dark:text-rose-200 text-xs font-semibold">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="categories.php?action=<?php echo $editCategory ? 'edit&edit=' . $editCategory['id'] : 'add'; ?>"
            method="POST" class="space-y-4">
            <div>
                <label for="name"
                    class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Category
                    Name</label>
                <input type="text" id="name" name="name" required
                    value="<?php echo htmlspecialchars($editCategory ? $editCategory['name'] : ''); ?>"
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-sm">
            </div>

            <div>
                <label for="description"
                    class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Description</label>
                <textarea id="description" name="description" rows="4"
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-sm"><?php echo htmlspecialchars($editCategory ? $editCategory['description'] : ''); ?></textarea>
            </div>

            <button type="submit"
                class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl transition-all shadow-md shadow-indigo-500/10 active:scale-[0.99] text-sm">
                <?php echo $editCategory ? 'Save Changes' : 'Create Category'; ?>
            </button>

            <?php if ($editCategory): ?>
                <a href="categories.php" class="block text-center text-xs font-bold text-slate-400 hover:underline">Cancel
                    Edit</a>
            <?php endif; ?>
        </form>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>