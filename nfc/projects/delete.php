<?php
// projects/delete.php
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../includes/header.php';

$errors = [];
$projectId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($projectId <= 0) {
    die("Invalid project id.");
}

// load project for context
$stmt = $pdo->prepare("SELECT id, name FROM projects WHERE id = ?");
$stmt->execute([$projectId]);
$project = $stmt->fetch();
if (!$project) {
    die("Project not found.");
}

// If POST -> perform delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $del = $pdo->prepare("DELETE FROM projects WHERE id = ?");
        $del->execute([$projectId]);
        header("Location: manage.php?deleted=1");
        exit;
    } catch (Exception $e) {
        $errors[] = "Delete failed: " . $e->getMessage();
    }
}
?>

<h2>Delete Project</h2>

<?php if (!empty($errors)): ?>
    <div style="color:#b00020;margin-bottom:12px;">
        <?php foreach ($errors as $er) echo htmlspecialchars($er) . "<br>"; ?>
    </div>
<?php endif; ?>

<p>Are you sure you want to <strong>permanently delete</strong> the project:</p>
<p><strong><?= htmlspecialchars($project['name']) ?></strong> (ID: <?= (int)$project['id'] ?>)</p>

<form method="post" style="display:inline">
    <button type="submit" class="btn" style="background:#c0392b">Yes, Delete</button>
</form>

<a class="btn" href="edit.php?id=<?= (int)$projectId ?>" style="background:#666;margin-left:8px">Cancel</a>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
