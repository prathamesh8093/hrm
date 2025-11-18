<?php
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$errors = [];
$success = "";
// keep old values so form preserves input on validation error
$old = [
    'sku' => '',
    'name' => '',
    'quantity' => 1,
    'description' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $old['sku'] = trim($_POST['sku'] ?? '');
    $old['name'] = trim($_POST['name'] ?? '');
    $old['quantity'] = (int)($_POST['quantity'] ?? 1);
    $old['description'] = trim($_POST['description'] ?? '');

    if ($old['name'] === "") $errors[] = "Item name is required";
    if ($old['quantity'] < 1) $errors[] = "Quantity must be at least 1";

    // Insert into DB
    if (!$errors) {
        try {
            $sku = $old['sku'];
            if ($sku === "") {
                // Auto SKU example: "ITEM_17082025"
                $sku = "ITEM_" . time();
            }

            $stmt = $pdo->prepare("
                INSERT INTO inventory (sku, name, description, quantity, available)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $sku,
                $old['name'],
                $old['description'],
                $old['quantity'],
                $old['quantity']   // available = quantity initially
            ]);

            $success = "Item added successfully!";
            // reset form values
            $old = ['sku'=>'','name'=>'','quantity'=>1,'description'=>''];
        } catch (PDOException $e) {
            $errors[] = "Database Error: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<!-- Page Content (offset for sidebar) -->
<div class="flex-1 p-6">
  <div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-semibold">Add Inventory Item</h2>
        <p class="text-sm text-gray-500">Add new inventory items, set SKU, quantity and description.</p>
      </div>
      <div>
        <a href="manage.php" class="inline-flex items-center gap-2 border px-3 py-2 rounded-md hover:bg-gray-50">Back to Inventory</a>
      </div>
    </div>

    <?php if ($success): ?>
      <div class="mb-4 p-3 rounded-md bg-green-50 border border-green-200 text-green-800">
        <?= htmlspecialchars($success) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
      <div class="mb-4 p-3 rounded-md bg-red-50 border border-red-200 text-red-800">
        <strong class="block font-medium">Please fix the following:</strong>
        <ul class="mt-2 list-disc ml-5 text-sm">
          <?php foreach ($errors as $err): ?>
            <li><?= htmlspecialchars($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow p-6">
      <form method="post" class="space-y-5">
        <div>
          <label class="block text-sm font-medium text-gray-700">SKU (optional)</label>
          <input name="sku" type="text"
                 value="<?= htmlspecialchars($old['sku']) ?>"
                 placeholder="Leave empty for auto SKU"
                 class="mt-1 block w-full rounded-md border-gray-200 shadow-sm focus:border-violet-400 focus:ring focus:ring-violet-200 focus:ring-opacity-50 p-2" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Item Name*</label>
          <input name="name" type="text" required
                 value="<?= htmlspecialchars($old['name']) ?>"
                 class="mt-1 block w-full rounded-md border-gray-200 shadow-sm focus:border-violet-400 focus:ring focus:ring-violet-200 focus:ring-opacity-50 p-2" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Quantity*</label>
            <input name="quantity" type="number" min="1" required
                   value="<?= (int)$old['quantity'] ?>"
                   class="mt-1 block w-full rounded-md border-gray-200 shadow-sm p-2 focus:border-violet-400 focus:ring focus:ring-violet-200 focus:ring-opacity-50" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Available (auto-set)</label>
            <input type="text" disabled value="<?= (int)$old['quantity'] ?>" class="mt-1 block w-full rounded-md border-gray-100 bg-gray-50 text-gray-500 shadow-sm p-2" />
            <p class="text-xs text-gray-400 mt-1">Available is set equal to quantity on create. Borrowing will decrement it.</p>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Description</label>
          <textarea name="description" rows="4" class="mt-1 block w-full rounded-md border-gray-200 shadow-sm p-2 focus:border-violet-400 focus:ring focus:ring-violet-200 focus:ring-opacity-50"><?= htmlspecialchars($old['description']) ?></textarea>
        </div>

        <div class="flex items-center gap-3">
          <button type="submit" class="inline-flex items-center gap-2 bg-violet-600 text-white px-4 py-2 rounded-lg shadow hover:bg-violet-700">
            Add Item
          </button>

          <a href="manage.php" class="ml-3 inline-flex items-center px-4 py-2 border rounded-lg text-sm">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<!-- small client-side enhancement: update Available preview when Quantity changes -->
<script>
  (function(){
    const qty = document.querySelector('input[name="quantity"]');
    const avail = document.querySelector('input[disabled]');
    if (!qty || !avail) return;
    qty.addEventListener('input', function(){
      const v = parseInt(this.value, 10);
      avail.value = Number.isInteger(v) && v > 0 ? v : 0;
    });
  })();
</script>
