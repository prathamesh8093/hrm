<?php
// projects/edit.php
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

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

<main class="flex-1 p-6">
  <div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-semibold">Edit Project</h2>
        <p class="text-sm text-gray-500">Update project details and assigned students</p>
      </div>
      <div class="flex items-center gap-2">
        <a href="manage.php" class="px-3 py-2 border rounded-md hover:bg-gray-50">Back to Projects</a>
        <a href="delete.php?id=<?= (int)$projectId ?>" class="px-3 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Delete Project</a>
      </div>
    </div>

    <?php if ($success): ?>
      <div class="mb-4 p-3 rounded-md bg-green-50 border border-green-200 text-green-800">
        <?= htmlspecialchars($success) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
      <div class="mb-4 p-3 rounded-md bg-red-50 border border-red-200 text-red-800">
        <strong class="block font-medium">Please fix the following:</strong>
        <ul class="mt-2 list-disc ml-5 text-sm">
          <?php foreach ($errors as $er): ?>
            <li><?= htmlspecialchars($er) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow p-6">
      <form method="post" class="space-y-6">
        <input type="hidden" name="id" value="<?= (int)$projectId ?>">

        <div>
          <label class="block text-sm font-medium text-gray-700">Project Name*</label>
          <input type="text" name="name" required
                 value="<?= htmlspecialchars($_POST['name'] ?? $project['name']) ?>"
                 class="mt-1 block w-full rounded-md border-gray-200 shadow-sm p-2 focus:ring-violet-300 focus:border-violet-400" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Description</label>
          <textarea name="description" rows="4"
                    class="mt-1 block w-full rounded-md border-gray-200 shadow-sm p-2 focus:ring-violet-300 focus:border-violet-400"><?= htmlspecialchars($_POST['description'] ?? $project['description']) ?></textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Start Date</label>
            <input type="date" name="start_date"
                   value="<?= htmlspecialchars($_POST['start_date'] ?? $project['start_date']) ?>"
                   class="mt-1 block w-full rounded-md border-gray-200 shadow-sm p-2" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">End Date</label>
            <input type="date" name="end_date"
                   value="<?= htmlspecialchars($_POST['end_date'] ?? $project['end_date']) ?>"
                   class="mt-1 block w-full rounded-md border-gray-200 shadow-sm p-2" />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Tech Stack (comma separated)</label>
          <input type="text" name="tech_stack"
                 value="<?= htmlspecialchars($_POST['tech_stack'] ?? $project['tech_stack']) ?>"
                 class="mt-1 block w-full rounded-md border-gray-200 shadow-sm p-2" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Progress (%)</label>
          <div class="mt-2 flex items-center gap-4">
            <input type="range" min="0" max="100" name="progress_percent" id="progressRange"
                   value="<?= (int)($_POST['progress_percent'] ?? $project['progress_percent']) ?>"
                   class="w-full" />
            <div class="w-14 text-right text-sm font-medium" id="progressVal"><?= (int)($_POST['progress_percent'] ?? $project['progress_percent']) ?>%</div>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Assign Students <span class="text-xs text-gray-400">(hold Ctrl/Cmd to select multiple)</span></label>
          <div class="mt-2 relative">
            <?php
              $current = $_POST['student_ids'] ?? $assignedIds;
            ?>
            <select id="studentSelect" name="student_ids[]" multiple class="block w-full rounded-md border-gray-200 shadow-sm p-2 h-48">
              <?php foreach ($students as $s):
                $selected = in_array($s['id'], $current) ? 'selected' : '';
              ?>
                <option value="<?= (int)$s['id'] ?>" <?= $selected ?>>
                  <?= htmlspecialchars($s['roll_no'] . ' - ' . $s['first_name'] . ' ' . $s['last_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>

            <div id="selectHint" class="absolute right-3 top-3 text-xs text-gray-400 hidden">Selected: <span id="selCount">0</span></div>
          </div>
          <p class="text-xs text-gray-400 mt-2">Tip: you can assign students now or later from the project page.</p>
        </div>

        <div class="flex items-center gap-3">
          <button type="submit" class="inline-flex items-center gap-2 bg-violet-600 text-white px-4 py-2 rounded-lg shadow hover:bg-violet-700">
            Save Changes
          </button>

          <a href="manage.php" class="inline-flex items-center px-4 py-2 border rounded-lg text-sm hover:bg-gray-50">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</main>

<script>
  // Progress range live value
  (function(){
    const range = document.getElementById('progressRange');
    const val = document.getElementById('progressVal');
    if (range && val) {
      range.addEventListener('input', () => {
        val.textContent = range.value + '%';
      });
    }

    // student select count hint
    const sel = document.getElementById('studentSelect');
    const hint = document.getElementById('selectHint');
    const count = document.getElementById('selCount');
    function updateSel(){
      const n = Array.from(sel.selectedOptions).length;
      if (n > 0) {
        hint.classList.remove('hidden');
        count.textContent = n;
      } else {
        hint.classList.add('hidden');
        count.textContent = 0;
      }
    }
    if (sel) {
      sel.addEventListener('change', updateSel);
      updateSel();
    }
  })();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
