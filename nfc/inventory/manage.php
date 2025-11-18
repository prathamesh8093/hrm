<?php
// inventory/manage.php (styled)
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM inventory WHERE id = ?")->execute([$id]);
    header("Location: manage.php?deleted=1");
    exit;
}

// Messages
$msg = '';
if (isset($_GET['deleted']))  $msg = "Item deleted successfully.";
if (isset($_GET['borrowed'])) $msg = "Item borrowed successfully.";
if (isset($_GET['returned'])) $msg = "Item returned successfully.";

// Fetch inventory
$items = $pdo->query("
    SELECT *
    FROM inventory
    ORDER BY id DESC
")->fetchAll();

// Fetch active borrows (status = borrowed)
$borrowMap = [];
$borrows = $pdo->query("
    SELECT id AS borrow_id, inventory_id
    FROM inventory_borrows
    WHERE status = 'borrowed'
")->fetchAll();

foreach ($borrows as $b) {
    // Map inventory_id → borrow_id
    $borrowMap[$b['inventory_id']] = $b['borrow_id'];
}
?>

<!-- Page Content (offset for sidebar) -->
<div class="flex-1 p-6">
  <div class="max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-semibold">Inventory Items</h2>
        <p class="text-sm text-gray-500">Add, manage and track inventory items and borrows.</p>
      </div>
      <div class="flex items-center gap-3">
        <a href="add.php" class="inline-flex items-center gap-2 bg-violet-600 text-white px-3 py-2 rounded-md shadow hover:bg-violet-700">
          Add New Item
        </a>
      </div>
    </div>

    <!-- Message -->
    <?php if ($msg): ?>
      <div class="mb-4 p-3 rounded-md bg-green-50 border border-green-200 text-green-800">
        <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <!-- Search & Stats -->
    <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div class="flex items-center gap-2">
        <input id="searchBox" type="search" placeholder="Search by name, SKU or description..." class="w-72 p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-violet-200" />
        <button onclick="resetSearch()" class="px-3 py-2 border rounded-md text-sm hover:bg-gray-50">Reset</button>
      </div>
      <div class="text-sm text-gray-500">Total items: <span class="font-medium"><?= count($items) ?></span></div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto bg-white rounded-lg shadow">
      <table id="inventoryTable" class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Available</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>

        <tbody class="bg-white divide-y divide-gray-100">
          <?php if (empty($items)): ?>
            <tr>
              <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500">No items found.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($items as $it): ?>
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700"><?= (int)$it['id'] ?></td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars($it['sku']) ?></td>
                <td class="px-4 py-3 text-sm text-gray-800"><?= htmlspecialchars($it['name']) ?></td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700"><?= (int)$it['quantity'] ?></td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700"><?= (int)$it['available'] ?></td>
                <td class="px-4 py-3 text-sm text-gray-600" style="max-width:360px;"><?= nl2br(htmlspecialchars($it['description'])) ?></td>

                <td class="px-4 py-3 whitespace-nowrap text-sm text-center">
                  <div class="inline-flex gap-2">
                    <!-- Borrow -->
                    <a href="borrow.php?item=<?= (int)$it['id'] ?>" class="px-3 py-1 rounded-md text-sm border hover:bg-gray-50">Borrow</a>

                    <!-- Return (only if borrowed) -->
                    <?php if (isset($borrowMap[$it['id']])): ?>
                      <a href="return.php?borrow=<?= (int)$borrowMap[$it['id']] ?>" class="px-3 py-1 rounded-md text-sm bg-green-50 text-green-700 border border-green-100 hover:bg-green-100">Return</a>
                    <?php endif; ?>

                    <!-- Delete -->
                    <a href="?delete=<?= (int)$it['id'] ?>"
                       onclick="return confirm('Delete this item?')"
                       class="px-3 py-1 rounded-md text-sm bg-red-50 text-red-700 border border-red-100 hover:bg-red-100">
                       Delete
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Footer / Pagination placeholder -->
    <div class="mt-4 flex items-center justify-between text-sm text-gray-600">
      <div>Showing <?= count($items) ?> items</div>
      <div><!-- pagination can go here --></div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<!-- Small client-side search for convenience -->
<script>
  (function(){
    const searchBox = document.getElementById('searchBox');
    const rows = Array.from(document.querySelectorAll('#inventoryTable tbody tr')).filter(r => r.querySelectorAll('td').length);
    if (!searchBox) return;

    searchBox.addEventListener('input', function(){
      const q = this.value.trim().toLowerCase();
      rows.forEach(row => {
        const txt = row.innerText.toLowerCase();
        row.style.display = q === '' || txt.indexOf(q) !== -1 ? '' : 'none';
      });
    });

    window.resetSearch = function(){
      searchBox.value = '';
      searchBox.dispatchEvent(new Event('input'));
    };
  })();
</script>
