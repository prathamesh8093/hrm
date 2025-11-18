<?php
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

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

// Fetch students
$students = $pdo->query("
    SELECT id, roll_no, first_name, last_name
    FROM students
    ORDER BY first_name, roll_no
")->fetchAll();

// Current assigned
$assigned = [];
if ($project) {
    $rs = $pdo->prepare("SELECT student_id FROM project_students WHERE project_id = ?");
    $rs->execute([$projectId]);
    $assigned = array_column($rs->fetchAll(), 'student_id');
}

// POST Assign
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $project) {
    $studentIdsRaw = $_POST['student_ids'] ?? [];
    $studentIds = [];

    foreach ($studentIdsRaw as $sid)
        if (($sid = (int)$sid) > 0)
            $studentIds[] = $sid;

    $studentIds = array_values(array_unique($studentIds));

    if (empty($studentIds)) {
        $errors[] = "No students selected.";
    } else {
        try {
            // Validate IDs
            $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
            $check = $pdo->prepare("SELECT id FROM students WHERE id IN ($placeholders)");
            $check->execute($studentIds);
            $validIds = array_map('intval', $check->fetchAll(PDO::FETCH_COLUMN, 0));

            $toInsert = array_diff($validIds, $assigned);

            if (empty($toInsert)) {
                $success = "No new students to assign.";
            } else {
                $ins = $pdo->prepare("
                    INSERT IGNORE INTO project_students
                    (project_id, student_id, role, assigned_at)
                    VALUES (?, ?, NULL, NOW())
                ");

                $pdo->beginTransaction();
                foreach ($toInsert as $sid) {
                    $ins->execute([$projectId, $sid]);
                }
                $pdo->commit();

                $success = "Assigned " . count($toInsert) . " student(s).";
            }

            // Refresh assigned list
            $rs->execute([$projectId]);
            $assigned = array_column($rs->fetchAll(), 'student_id');

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = "Assign failed: " . $e->getMessage();
        }
    }
}
?>

<main class="flex-1 p-6">
  <div class="max-w-3xl mx-auto">

    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-semibold">Assign Students</h2>
        <p class="text-sm text-gray-500">Attach multiple students to this project</p>
      </div>
      <a href="manage.php" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Back</a>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-800 rounded-lg">
        <?php foreach ($errors as $er): ?>
          <div><?= htmlspecialchars($er) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-800 rounded-lg">
        <?= htmlspecialchars($success) ?>
      </div>
    <?php endif; ?>

    <?php if ($project): ?>
    <div class="bg-white p-6 rounded-2xl shadow">

      <h3 class="text-xl font-medium mb-4 text-gray-700">
        Project: <?= htmlspecialchars($project['name']) ?>
      </h3>

      <form method="post" class="space-y-6">

        <div>
          <label class="block text-sm font-medium text-gray-600 mb-1">Select Students</label>

          <select name="student_ids[]" multiple
            class="w-full h-56 border border-gray-200 rounded-lg p-2 shadow-sm focus:ring-violet-300 focus:border-violet-400">
            <?php foreach ($students as $s): ?>
              <option value="<?= $s['id'] ?>"
                <?= in_array($s['id'], $assigned) ? 'selected' : '' ?>>
                <?= htmlspecialchars($s['roll_no']." - ".$s['first_name']." ".$s['last_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <p class="text-xs text-gray-400 mt-1">Hold Ctrl / Command to select multiple.</p>
        </div>

        <div class="flex items-center gap-3">
          <button type="submit"
            class="bg-violet-600 text-white px-4 py-2 rounded-lg shadow hover:bg-violet-700">
            Assign Selected
          </button>

          <a href="manage.php" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Back</a>
        </div>

      </form>
    </div>
    <?php endif; ?>

  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
