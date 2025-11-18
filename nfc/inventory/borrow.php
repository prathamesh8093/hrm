<?php
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$errors = [];
$itemId = isset($_GET['item']) ? (int) $_GET['item'] : 0;

// Fetch preselected item
$item = null;
if ($itemId) {
    $stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ?");
    $stmt->execute([$itemId]);
    $item = $stmt->fetch();
    if (!$item) {
        $errors[] = "Inventory item not found.";
    }
}

// Fetch students
$students = $pdo->query("SELECT id, roll_no, first_name, last_name FROM students ORDER BY first_name, roll_no")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inventory_id = (int)($_POST['inventory_id'] ?? 0);
    $student_id   = (int)($_POST['student_id'] ?? 0);
    $due_date     = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    $notes        = trim($_POST['notes'] ?? '');

    if ($inventory_id <= 0) $errors[] = "Please select an inventory item.";
    if ($student_id <= 0)   $errors[] = "Please select a student.";

    // Confirm student exists
    if (!$errors) {
        $sStmt = $pdo->prepare("SELECT id FROM students WHERE id = ?");
        $sStmt->execute([$student_id]);
        if (!$sStmt->fetch()) {
            $errors[] = "Selected student does not exist.";
        }
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            // Lock inventory row
            $lock = $pdo->prepare("SELECT available FROM inventory WHERE id = ? FOR UPDATE");
            $lock->execute([$inventory_id]);
            $inv = $lock->fetch();

            if (!$inv) throw new Exception("Inventory item not found.");
            if ((int)$inv['available'] < 1) throw new Exception("Item is currently unavailable.");

            // Insert borrow log
            $insert = $pdo->prepare("
                INSERT INTO inventory_borrows (inventory_id, student_id, borrow_date, due_date, status, notes)
                VALUES (:inventory_id, :student_id, NOW(), :due_date, 'borrowed', :notes)
            ");
            $insert->execute([
                ':inventory_id' => $inventory_id,
                ':student_id'   => $student_id,
                ':due_date'     => $due_date,
                ':notes'        => $notes
            ]);

            // Decrement available
            $pdo->prepare("UPDATE inventory SET available = available - 1 WHERE id = ?")
                ->execute([$inventory_id]);

            $pdo->commit();
            header("Location: manage.php?borrowed=1");
            exit;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = "Borrow failed: " . $e->getMessage();
        }
    }
}
?>

<!-- Page Content -->
<div class="flex-1 p-6">
  <div class="max-w-3xl mx-auto">

    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-semibold">Borrow Inventory Item</h2>
        <p class="text-sm text-gray-500">Select a student and borrow an available item.</p>
      </div>
      <a href="manage.php" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Back</a>
    </div>

    <!-- Errors -->
    <?php if ($errors): ?>
      <div class="mb-4 p-3 rounded-md bg-red-50 border border-red-200 text-red-800">
        <strong class="font-medium">Please fix the following:</strong>
        <ul class="list-disc ml-5 mt-2 text-sm">
          <?php foreach ($errors as $err): ?>
            <li><?= htmlspecialchars($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="bg-white p-6 rounded-2xl shadow">
      <form method="post" class="space-y-5">

        <!-- Inventory Dropdown -->
        <div>
          <label class="block text-sm font-medium text-gray-700">Inventory Item</label>
          <select name="inventory_id"
                  class="mt-1 w-full p-2 rounded-md border-gray-300 shadow-sm focus:ring-violet-300 focus:border-violet-400"
                  required>

            <option value="">-- Select item --</option>

            <?php if ($item): ?>
              <option value="<?= $item['id'] ?>" selected>
                <?= htmlspecialchars($item['name']) ?> (Available: <?= (int)$item['available'] ?>)
              </option>
            <?php endif; ?>

            <?php
            $allItems = $pdo->query("SELECT id, name, available FROM inventory ORDER BY name")->fetchAll();
            foreach ($allItems as $it) {
                if ($item && $it['id'] == $item['id']) continue;
                echo '<option value="'.$it['id'].'">'.htmlspecialchars($it['name']).' (Available: '.$it['available'].')</option>';
            }
            ?>
          </select>
        </div>

        <!-- Student Dropdown -->
        <div>
          <label class="block text-sm font-medium text-gray-700">Select Student</label>

          <?php if (empty($students)): ?>
            <p class="text-red-600 text-sm mt-2">
              No students found.  
              <a href="/hrm-system/students/add.php" class="underline">Add a student first</a>
            </p>
          <?php else: ?>
            <select name="student_id"
                    class="mt-1 w-full p-2 rounded-md border-gray-300 shadow-sm focus:ring-violet-300 focus:border-violet-400"
                    required>

              <option value="">-- Select student --</option>

              <?php foreach ($students as $s): ?>
                <option value="<?= $s['id'] ?>">
                  <?= htmlspecialchars($s['roll_no'].' - '.$s['first_name'].' '.$s['last_name']) ?>
                </option>
              <?php endforeach; ?>

            </select>
          <?php endif; ?>
        </div>

        <!-- Due Date -->
        <div>
          <label class="block text-sm font-medium text-gray-700">Due Date (optional)</label>
          <input type="date" name="due_date"
                 class="mt-1 w-full p-2 rounded-md border-gray-300 shadow-sm focus:ring-violet-300 focus:border-violet-400">
        </div>

        <!-- Notes -->
        <div>
          <label class="block text-sm font-medium text-gray-700">Notes (optional)</label>
          <textarea name="notes" rows="3"
                    class="mt-1 w-full p-2 rounded-md border-gray-300 shadow-sm focus:ring-violet-300 focus:border-violet-400"></textarea>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-3">
          <button type="submit"
                  class="px-4 py-2 bg-violet-600 text-white rounded-lg shadow hover:bg-violet-700">
            Borrow Item
          </button>

          <a href="manage.php"
             class="px-4 py-2 border rounded-lg hover:bg-gray-50">
            Cancel
          </a>
        </div>

      </form>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
