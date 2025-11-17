<?php
// inventory/manage.php (UPDATED)
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../includes/header.php';

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

<h2>Inventory Items</h2>

<?php if ($msg): ?>
<div style="color:green;margin-bottom:12px;"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<p><a class="btn" href="add.php">Add New Item</a></p>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>SKU</th>
            <th>Name</th>
            <th>Qty</th>
            <th>Available</th>
            <th>Description</th>
            <th>Actions</th>
        </tr>
    </thead>

    <tbody>
    <?php if (empty($items)): ?>
        <tr><td colspan="7">No items found.</td></tr>
    <?php endif; ?>

    <?php foreach ($items as $it): ?>
        <tr>
            <td><?= $it['id'] ?></td>
            <td><?= htmlspecialchars($it['sku']) ?></td>
            <td><?= htmlspecialchars($it['name']) ?></td>
            <td><?= $it['quantity'] ?></td>
            <td><?= $it['available'] ?></td>
            <td><?= nl2br(htmlspecialchars($it['description'])) ?></td>

            <td>
                <!-- Borrow Button -->
                <a class="btn" href="borrow.php?item=<?= $it['id'] ?>">Borrow</a>

                <!-- Return Button (only if borrowed) -->
                <?php if (isset($borrowMap[$it['id']])): ?>
                    <a class="btn" href="return.php?borrow=<?= $borrowMap[$it['id']] ?>" 
                       style="background:#28a745;">
                        Return
                    </a>
                <?php endif; ?>

                <!-- Delete Button -->
                <a class="btn" href="?delete=<?= $it['id'] ?>" 
                   onclick="return confirm('Delete this item?');"
                   style="background:#c0392b;">
                    Delete
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
