<?php
// inventory/return.php (styled)
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$errors = [];
$success = '';
$preselectBorrow = isset($_GET['borrow']) ? (int)$_GET['borrow'] : 0;

// Fetch currently borrowed records
$borrowedStmt = $pdo->prepare("
    SELECT b.id AS borrow_id, b.inventory_id, b.student_id, b.borrow_date, b.due_date, b.notes,
           s.first_name, s.last_name, s.roll_no,
           i.name AS item_name, i.sku
    FROM inventory_borrows b
    JOIN students s ON s.id = b.student_id
    JOIN inventory i ON i.id = b.inventory_id
    WHERE b.status = 'borrowed'
    ORDER BY b.borrow_date DESC
");
$borrowedStmt->execute();
$borrowedRows = $borrowedStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $borrow_id = (int)($_POST['borrow_id'] ?? 0);
    if ($borrow_id <= 0) {
        $errors[] = "Please select a borrow record to return.";
    } else {
        // Verify borrow record exists and is currently 'borrowed'
        $check = $pdo->prepare("SELECT id, inventory_id, status FROM inventory_borrows WHERE id = ? FOR UPDATE");
        try {
            $pdo->beginTransaction();
            $check->execute([$borrow_id]);
            $b = $check->fetch();
            if (!$b) {
                throw new Exception("Borrow record not found.");
            }
            if ($b['status'] !== 'borrowed') {
                throw new Exception("This item is not marked as borrowed (maybe already returned).");
            }

            // Update borrow record: set return_date and status
            $updBorrow = $pdo->prepare("UPDATE inventory_borrows SET return_date = NOW(), status = 'returned' WHERE id = ?");
            $updBorrow->execute([$borrow_id]);

            // Increment inventory available count (lock row first)
            $lockInv = $pdo->prepare("SELECT available FROM inventory WHERE id = ? FOR UPDATE");
            $lockInv->execute([$b['inventory_id']]);
            $inv = $lockInv->fetch();
            if (!$inv) {
                // Shouldn't happen, but handle
                throw new Exception("Inventory item for this borrow record was not found.");
            }
            $inc = $pdo->prepare("UPDATE inventory SET available = available + 1 WHERE id = ?");
            $inc->execute([$b['inventory_id']]);

            $pdo->commit();
            $success = "Item returned successfully.";
            // Refresh borrowed list after commit
            $borrowedStmt->execute();
            $borrowedRows = $borrowedStmt->fetchAll();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = "Return failed: " . $e->getMessage();
        }
    }
}
?>

<!-- Page Content (offset for sidebar) -->
<div class="flex-1 p-6">
  <div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-semibold">Return Borrowed Item</h2>
        <p class="text-sm text-gray-500">Mark borrowed items as returned and restore inventory availability.</p>
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
        <strong class="block font-medium">Errors</strong>
        <ul class="mt-2 list-disc ml-5 text-sm">
          <?php foreach ($errors as $err): ?>
            <li><?= htmlspecialchars($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if (empty($borrowedRows)): ?>
      <div class="bg-white rounded-2xl shadow p-6 text-center text-gray-600">
        <p class="mb-3">No borrowed items found.</p>
        <a href="manage.php" class="inline-flex items-center gap-2 border px-3 py-2 rounded-md hover:bg-gray-50">Back to Inventory</a>
      </div>
    <?php else: ?>
      <div class="bg-white rounded-2xl shadow p-6">
        <form method="post" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Select Borrow Record to Return</label>
            <select name="borrow_id" required
                    class="mt-1 block w-full p-2 rounded-md border-gray-300 shadow-sm focus:ring-violet-300 focus:border-violet-400">
              <option value="">-- select --</option>
              <?php foreach ($borrowedRows as $br):
                $label = sprintf(
                    "%s | %s - %s (Borrowed: %s%s)",
                    $br['borrow_id'],
                    $br['item_name'],
                    trim($br['roll_no'].' '.$br['first_name'].' '.$br['last_name']),
                    date('Y-m-d', strtotime($br['borrow_date'])),
                    !empty($br['due_date']) ? " | Due: ".htmlspecialchars($br['due_date']) : ''
                );
                $sel = ($preselectBorrow && $preselectBorrow == $br['borrow_id']) ? ' selected' : '';
              ?>
                <option value="<?= (int)$br['borrow_id'] ?>"<?= $sel ?>>
                  <?= htmlspecialchars($label) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="flex items-center gap-3">
            <button type="submit" class="inline-flex items-center gap-2 bg-violet-600 text-white px-4 py-2 rounded-lg shadow hover:bg-violet-700">
              Mark as Returned
            </button>
            <a href="manage.php" class="ml-3 inline-flex items-center px-4 py-2 border rounded-lg text-sm">Cancel</a>
          </div>
        </form>
      </div>

      <h3 class="mt-6 mb-3 text-lg font-semibold">Currently Borrowed</h3>

      <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Borrowed On</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Notes</th>
            </tr>
          </thead>

          <tbody class="bg-white divide-y divide-gray-100">
            <?php foreach ($borrowedRows as $br): ?>
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-sm text-gray-700"><?= (int)$br['borrow_id'] ?></td>
                <td class="px-4 py-3 text-sm text-gray-800"><?= htmlspecialchars($br['item_name'].' ('.$br['sku'].')') ?></td>
                <td class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars($br['roll_no'].' - '.$br['first_name'].' '.$br['last_name']) ?></td>
                <td class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars(date('Y-m-d', strtotime($br['borrow_date']))) ?></td>
                <td class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars($br['due_date'] ?? '-') ?></td>
                <td class="px-4 py-3 text-sm text-gray-600"><?= nl2br(htmlspecialchars($br['notes'] ?? '')) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
