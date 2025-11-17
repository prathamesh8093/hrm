<?php
// inventory/return.php
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../includes/header.php';

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

<h2>Return Borrowed Item</h2>

<?php if ($success): ?>
    <div style="color:green;margin-bottom:12px;"><?=htmlspecialchars($success)?></div>
<?php endif; ?>

<?php if ($errors): ?>
    <div style="color:#b00020;margin-bottom:12px;">
        <?php foreach ($errors as $err) echo htmlspecialchars($err) . "<br>"; ?>
    </div>
<?php endif; ?>

<?php if (empty($borrowedRows)): ?>
    <div>No borrowed items found. <a href="/nfc/inventory/manage.php">Back to Inventory</a></div>
<?php else: ?>
    <form method="post" style="max-width:720px">
        <label>Select Borrow Record to Return</label><br>
        <select name="borrow_id" required>
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
        <br><br>

        <button type="submit" class="btn">Mark as Returned</button>
        <a class="btn" href="manage.php" style="background:#666;margin-left:8px">Back to Inventory</a>
    </form>

    <h3 style="margin-top:24px">Currently Borrowed</h3>
    <table>
        <thead>
            <tr><th>ID</th><th>Item</th><th>Student</th><th>Borrowed On</th><th>Due Date</th><th>Notes</th></tr>
        </thead>
        <tbody>
            <?php foreach ($borrowedRows as $br): ?>
                <tr>
                    <td><?= (int)$br['borrow_id'] ?></td>
                    <td><?= htmlspecialchars($br['item_name'].' ('.$br['sku'].')') ?></td>
                    <td><?= htmlspecialchars($br['roll_no'].' - '.$br['first_name'].' '.$br['last_name']) ?></td>
                    <td><?= htmlspecialchars(date('Y-m-d', strtotime($br['borrow_date']))) ?></td>
                    <td><?= htmlspecialchars($br['due_date'] ?? '-') ?></td>
                    <td><?= nl2br(htmlspecialchars($br['notes'] ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
