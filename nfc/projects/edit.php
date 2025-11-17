<?php
// projects/edit.php
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../includes/header.php';

$errors = [];
$success = '';
// get project id from GET or POST
$projectId = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
if ($projectId <= 0) {
    die("Invalid project id.");
}

// load project
$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$projectId]);
$project = $stmt->fetch();
if (!$project) {
    die("Project not found.");
}

// fetch all students for multi-select
$students = $pdo->query("SELECT id, roll_no, first_name, last_name FROM students ORDER BY first_name, roll_no")->fetchAll();

// fetch currently assigned students
$assignedStmt = $pdo->prepare("SELECT student_id FROM project_students WHERE project_id = ?");
$assignedStmt->execute([$projectId]);
$assignedRows = $assignedStmt->fetchAll();
$assignedIds = array_column($assignedRows, 'student_id');

// handle POST (update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // collect input
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $start_date = trim($_POST['start_date'] ?? '') ?: null;
    $end_date = trim($_POST['end_date'] ?? '') ?: null;
    $tech_stack = trim($_POST['tech_stack'] ?? '') ?: null;
    $progress = isset($_POST['progress_percent']) ? (int) $_POST['progress_percent'] : null;
    $selectedStudents = $_POST['student_ids'] ?? [];

    // validations
    if ($name === '') $errors[] = "Project name is required.";
    if ($progress !== null && ($progress < 0 || $progress > 100)) $errors[] = "Progress must be between 0 and 100.";

    if (empty($errors)) {
        try {
            // update project and assignment inside a transaction
            $pdo->beginTransaction();

            $upd = $pdo->prepare("
                UPDATE projects
                SET name = ?, description = ?, start_date = ?, end_date = ?, tech_stack = ?, progress_percent = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $upd->execute([
                $name,
                $description ?: null,
                $start_date,
                $end_date,
                $tech_stack,
                $progress ?? 0,
                $projectId
            ]);

            // Replace assignments: remove existing and insert new ones
            $del = $pdo->prepare("DELETE FROM project_students WHERE project_id = ?");
            $del->execute([$projectId]);

            if (!empty($selectedStudents)) {
                $ins = $pdo->prepare("INSERT IGNORE INTO project_students (project_id, student_id, role, assigned_at) VALUES (?, ?, NULL, NOW())");
                foreach ($selectedStudents as $sid) {
                    $sid = (int)$sid;
                    if ($sid > 0) $ins->execute([$projectId, $sid]);
                }
            }

            $pdo->commit();
            // refresh project & assignedIds
            $stmt->execute([$projectId]);
            $project = $stmt->fetch();
            $assignedStmt->execute([$projectId]);
            $assignedRows = $assignedStmt->fetchAll();
            $assignedIds = array_column($assignedRows, 'student_id');

            $success = "Project updated successfully.";
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = "Update failed: " . $e->getMessage();
        }
    }
}
?>

<h2>Edit Project</h2>

<?php if ($success): ?>
    <div style="color:green;margin-bottom:12px;"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div style="color:#b00020;margin-bottom:12px;">
        <?php foreach ($errors as $er) echo htmlspecialchars($er) . "<br>"; ?>
    </div>
<?php endif; ?>

<form method="post" style="max-width:800px">
    <input type="hidden" name="id" value="<?= (int)$projectId ?>">

    <label>Project Name*</label><br>
    <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? $project['name']) ?>" required><br><br>

    <label>Description</label><br>
    <textarea name="description" rows="4"><?= htmlspecialchars($_POST['description'] ?? $project['description']) ?></textarea><br><br>

    <label>Start Date</label><br>
    <input type="date" name="start_date" value="<?= htmlspecialchars($_POST['start_date'] ?? $project['start_date']) ?>"><br><br>

    <label>End Date</label><br>
    <input type="date" name="end_date" value="<?= htmlspecialchars($_POST['end_date'] ?? $project['end_date']) ?>"><br><br>

    <label>Tech Stack (comma separated)</label><br>
    <input type="text" name="tech_stack" value="<?= htmlspecialchars($_POST['tech_stack'] ?? $project['tech_stack']) ?>"><br><br>

    <label>Progress (%)</label><br>
    <input type="number" name="progress_percent" min="0" max="100" value="<?= htmlspecialchars($_POST['progress_percent'] ?? $project['progress_percent']) ?>"><br><br>

    <label>Assigned Students (Ctrl/Cmd+click to select multiple)</label><br>
    <select name="student_ids[]" multiple style="width:100%;height:180px">
        <?php
        $current = $_POST['student_ids'] ?? $assignedIds;
        foreach ($students as $s):
            $selected = in_array($s['id'], $current) ? 'selected' : '';
        ?>
            <option value="<?= (int)$s['id'] ?>" <?= $selected ?>>
                <?= htmlspecialchars($s['roll_no'] . ' - ' . $s['first_name'] . ' ' . $s['last_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <br><br>

    <button class="btn" type="submit">Save Changes</button>
    <a class="btn" href="manage.php" style="background:#666;margin-left:8px">Back</a>
    <a class="btn" href="delete.php?id=<?= (int)$projectId ?>" style="background:#c0392b;margin-left:8px">Delete Project</a>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
