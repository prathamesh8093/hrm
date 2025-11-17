<?php
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../includes/header.php';

$errors = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $sku  = trim($_POST['sku'] ?? '');
    $qty  = (int)($_POST['quantity'] ?? 1);
    $desc = trim($_POST['description'] ?? '');

    if ($name === "") $errors[] = "Item name is required";
    if ($qty < 1) $errors[] = "Quantity must be at least 1";

    // Insert into DB
    if (!$errors) {
        try {
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
                $name,
                $desc,
                $qty,
                $qty   // available = quantity initially
            ]);

            $success = "Item added successfully!";
        } catch (PDOException $e) {
            $errors[] = "Database Error: " . $e->getMessage();
        }
    }
}
?>

<h2>Add Inventory Item</h2>

<?php if ($success): ?>
<div style="color:green;margin-bottom:12px;"><?= $success ?></div>
<?php endif; ?>

<?php if ($errors): ?>
<div style="color:#b00020;margin-bottom:12px;">
    <?php foreach ($errors as $err) echo $err . "<br>"; ?>
</div>
<?php endif; ?>

<form method="post" style="max-width:400px;">
    <label>SKU (optional)</label><br>
    <input type="text" name="sku" placeholder="Leave empty for auto SKU"><br><br>

    <label>Item Name*</label><br>
    <input type="text" name="name" required><br><br>

    <label>Quantity*</label><br>
    <input type="number" name="quantity" value="1" min="1"><br><br>

    <label>Description</label><br>
    <textarea name="description" rows="3"></textarea><br><br>

    <button type="submit" class="btn">Add Item</button>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
