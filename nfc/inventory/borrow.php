<?php
// inventory/borrow.php (FIXED)
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../includes/header.php';

$errors = [];
$itemId = isset($_GET['item']) ? (int) $_GET['item'] : 0;

// Fetch inventory item (if preselected)
$item = null;
if ($itemId) {
    $stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ?");
    $stmt->execute([$itemId]);
    $item = $stmt->fetch();
    if (!$item) {
        $errors[] = "Inventory item not found.";
    }
}

// Fetch students for dropdown
$students = $pdo->query("SELECT id, roll_no, first_name, last_name FROM students ORDER BY first_name, roll_no")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inventory_id = (int)($_POST['inventory_id'] ?? 0);
    $student_id   = (int)($_POST['student_id'] ?? 0);
    $due_date     = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    $notes        = trim($_POST['notes'] ?? '');

    // Basic validation
    if ($inventory_id <= 0) $errors[] = "Please select an inventory item.";
    if ($student_id <= 0)   $errors[] = "Please select a student.";

    // Confirm student exists (prevents FK violation)
    if (empty($errors)) {
        $sStmt = $pdo->prepare("SELECT id FROM students WHERE id = ?");
        $sStmt->execute([$student_id]);
        $sRow = $sStmt->fetch();
        if (!$sRow) {
            $errors[] = "Selected student does not exist. Add the student first.";
        }
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            // Lock inventory row
            $lockStmt = $pdo->prepare("SELECT available FROM inventory WHERE id = ? FOR UPDATE");
            $lockStmt->execute([$inventory_id]);
            $inv = $lockStmt->fetch();
            if (!$inv) {
                throw new Exception("Inventory item not found.");
            }
            if ((int)$inv['available'] < 1) {
                throw new Exception("Item is currently unavailable.");
            }

            // Correct INSERT: columns and parameter order must match
            $insertSql = "INSERT INTO inventory_borrows (inventory_id, student_id, borrow_date, due_date, status, notes)
                          VALUES (:inventory_id, :student_id, NOW(), :due_date, 'borrowed', :notes)";
            $insertStmt = $pdo->prepare($insertSql);
            $insertStmt->execute([
                ':inventory_id' => $inventory_id,
                ':student_id'   => $student_id,
                ':due_date'     => $due_date,
                ':notes'        => $notes
            ]);

            // Decrement available
            $upd = $pdo->prepare("UPDATE inventory SET available = available - 1 WHERE id = ?");
            $upd->execute([$inventory_id]);

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

<h2>Borrow Inventory Item</h2>

<?php if ($errors): ?>
    <div style="color:#b00020;margin-bottom:12px;">
        <?php foreach ($errors as $err) echo htmlspecialchars($err) . "<br>"; ?>
    </div>
<?php endif; ?>

<form method="post" style="max-width:520px">
    <label>Inventory Item</label><br>
    <select name="inventory_id" required>
        <option value="">-- Select item --</option>
        <?php
        if ($item) {
            echo '<option value="'.(int)$item['id'].'" selected>'
                 .htmlspecialchars($item['name']).' (Available: '.(int)$item['available'].')</option>';
        }
        $allItems = $pdo->query("SELECT id, name, available FROM inventory ORDER BY name")->fetchAll();
        foreach ($allItems as $it) {
            if ($item && $it['id'] == $item['id']) continue;
            echo '<option value="'.(int)$it['id'].'">'.htmlspecialchars($it['name']).' (Available: '.(int)$it['available'].')</option>';
        }
        ?>
    </select>
    <br><br>

    <label>Select Student</label><br>
    <?php if (empty($students)): ?>
        <div style="color:#b00020">No students found. <a href="/nfc/students/add.php">Add a student first</a></div>
    <?php else: ?>
        <select name="student_id" required>
            <option value="">-- Select student --</option>
            <?php foreach ($students as $s): ?>
                <option value="<?= (int)$s['id'] ?>">
                    <?= htmlspecialchars("{$s['roll_no']} - {$s['first_name']} {$s['last_name']}") ?>
                </option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>
    <br><br>

    <label>Due Date (optional)</label><br>
    <input type="date" name="due_date"><br><br>

    <label>Notes (optional)</label><br>
    <textarea name="notes" rows="3"></textarea><br><br>

    <button type="submit" class="btn">Borrow Item</button>
    <a class="btn" href="manage.php" style="background:#666;margin-left:8px">Back</a>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
