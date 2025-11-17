<?php
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../includes/header.php';

$errors = [];
$success = '';
$projectId = isset($_GET['project']) ? (int)$_GET['project'] : 0;

// Validate project id and load project
if ($projectId <= 0) {
    $errors[] = "No project selected.";
    $project = null;
} else {
    $pStmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $pStmt->execute([$projectId]);
    $project = $pStmt->fetch();
    if (!$project) {
        $errors[] = "Project not found.";
    }
}

// Fetch students for multi-select (always load so the form can show)
$students = $pdo->query("SELECT id, roll_no, first_name, last_name FROM students ORDER BY first_name, roll_no")->fetchAll();

// Fetch currently assigned students (if project exists)
$assigned = [];
if ($project) {
    $rs = $pdo->prepare("SELECT student_id FROM project_students WHERE project_id = ?");
    $rs->execute([$projectId]);
    $assigned = array_column($rs->fetchAll(), 'student_id');
}

// Handle POST: assign students
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $project) {

    $studentIdsRaw = $_POST['student_ids'] ?? [];
    // normalize and filter integer ids
    $studentIds = [];
    foreach ($studentIdsRaw as $sid) {
        $sid = (int)$sid;
        if ($sid > 0) $studentIds[] = $sid;
    }
    $studentIds = array_values(array_unique($studentIds)); // unique & reindex

    if (empty($studentIds)) {
        $errors[] = "No students selected to assign.";
    } else {
        try {
            // Verify that the selected student IDs exist in students table
            $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
            $checkStmt = $pdo->prepare("SELECT id FROM students WHERE id IN ($placeholders)");
            $checkStmt->execute($studentIds);
            $validStudentRows = $checkStmt->fetchAll(PDO::FETCH_COLUMN, 0);
            $validStudentIds = array_map('intval', $validStudentRows);

            // Determine which IDs from the submitted list are valid and not already assigned
            $toInsert = array_diff($validStudentIds, $assigned);

            if (empty($toInsert)) {
                $success = "No new students to assign (either invalid or already assigned).";
            } else {
                // Prepare insert statement
                $ins = $pdo->prepare("INSERT IGNORE INTO project_students (project_id, student_id, role, assigned_at) VALUES (?, ?, NULL, NOW())");

                $pdo->beginTransaction();
                foreach ($toInsert as $sid) {
                    $ins->execute([$projectId, $sid]);
                }
                $pdo->commit();

                $success = "Assigned " . count($toInsert) . " student(s) to the project.";
            }

            // refresh assigned list
            $rs->execute([$projectId]);
            $assigned = array_column($rs->fetchAll(), 'student_id');

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = "Assign failed: " . $e->getMessage();
        }
    }
}
?>

<h2>Assign Students to Project</h2>

<?php if (!empty($errors)): ?>
    <div style="color:#b00020;margin-bottom:10px">
        <?php foreach ($errors as $er) echo htmlspecialchars($er) . "<br>"; ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div style="color:green;margin-bottom:10px"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if (!empty($project)): ?>
    <h3><?= htmlspecialchars($project['name']) ?></h3>

    <form method="post">
        <label>Select Students</label><br>
        <select name="student_ids[]" multiple style="width:100%;height:140px">
            <?php foreach ($students as $s): 
                $isSelected = in_array($s['id'], $assigned) ? 'selected' : '';
            ?>
                <option value="<?= (int)$s['id'] ?>" <?= $isSelected ?>>
                    <?= htmlspecialchars($s['roll_no'].' - '.$s['first_name'].' '.$s['last_name']) ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <button class="btn" type="submit">Assign Selected</button>
        <a class="btn" href="manage.php" style="background:#666;margin-left:8px">Back</a>
    </form>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
