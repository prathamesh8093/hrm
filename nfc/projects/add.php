<?php
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$errors = [];
$old = ['name'=>'','description'=>'','start_date'=>'','end_date'=>'','tech_stack'=>'','students'=>[]];

// Fetch students for multi-select
$students = $pdo->query("SELECT id, roll_no, first_name, last_name FROM students ORDER BY first_name, roll_no")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['name'] = trim($_POST['name'] ?? '');
    $old['description'] = trim($_POST['description'] ?? '');
    $old['start_date'] = trim($_POST['start_date'] ?? '');
    $old['end_date'] = trim($_POST['end_date'] ?? '');
    $old['tech_stack'] = trim($_POST['tech_stack'] ?? '');
    $old['students'] = $_POST['student_ids'] ?? [];

    if ($old['name'] === '') $errors[] = "Project name is required.";

    if (empty($errors)) {
        try {
            // Create project
            $stmt = $pdo->prepare("INSERT INTO projects (name, description, start_date, end_date, tech_stack) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$old['name'], $old['description'] ?: null, $old['start_date'] ?: null, $old['end_date'] ?: null, $old['tech_stack'] ?: null]);
            $projectId = $pdo->lastInsertId();

            // Assign students if any
            if (!empty($old['students'])) {
                // prepare insert ignore
                $ins = $pdo->prepare("INSERT IGNORE INTO project_students (project_id, student_id, role, assigned_at) VALUES (?, ?, NULL, NOW())");
                $pdo->beginTransaction();
                foreach ($old['students'] as $sid) {
                    $sid = (int)$sid;
                    if ($sid > 0) $ins->execute([$projectId, $sid]);
                }
                $pdo->commit();
            }

            header("Location: manage.php?created=1");
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}
?>

<!-- Page Content (offset for sidebar) -->
<div class="lg:ml-72 px-4 py-6">
  <div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-semibold">Create Project</h2>
        <p class="text-sm text-gray-500">Create a project and optionally assign students to it.</p>
      </div>
      <div>
        <a href="manage.php" class="inline-flex items-center gap-2 border px-3 py-2 rounded-md hover:bg-gray-50">Back to Projects</a>
      </div>
    </div>

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
        <div>
          <label class="block text-sm font-medium text-gray-700">Project Name*</label>
          <input name="name" type="text" required
                 value="<?= htmlspecialchars($old['name']) ?>"
                 class="mt-1 block w-full rounded-md border-gray-200 shadow-sm focus:border-violet-400 focus:ring focus:ring-violet-200 focus:ring-opacity-50 p-2" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Description</label>
          <textarea name="description" rows="4"
                    class="mt-1 block w-full rounded-md border-gray-200 shadow-sm focus:border-violet-400 focus:ring focus:ring-violet-200 focus:ring-opacity-50 p-2"><?= htmlspecialchars($old['description']) ?></textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Start Date</label>
            <input name="start_date" type="date" value="<?= htmlspecialchars($old['start_date']) ?>"
                   class="mt-1 block w-full rounded-md border-gray-200 shadow-sm p-2" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">End Date</label>
            <input name="end_date" type="date" value="<?= htmlspecialchars($old['end_date']) ?>"
                   class="mt-1 block w-full rounded-md border-gray-200 shadow-sm p-2" />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Tech Stack (comma separated)</label>
          <input name="tech_stack" type="text" value="<?= htmlspecialchars($old['tech_stack']) ?>"
                 class="mt-1 block w-full rounded-md border-gray-200 shadow-sm p-2" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Assign Students <span class="text-xs text-gray-400">(hold Ctrl/Cmd to select multiple)</span></label>
          <div class="mt-2 relative">
            <select id="studentSelect" name="student_ids[]" multiple
                    class="block w-full rounded-md border-gray-200 shadow-sm p-2 h-40">
              <?php foreach ($students as $s): ?>
                <option value="<?= (int)$s['id'] ?>" <?= in_array($s['id'],$old['students']) ? 'selected' : '' ?>>
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
            Create Project
          </button>

          <a href="manage.php" class="ml-3 inline-flex items-center px-4 py-2 border rounded-lg text-sm">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
  // show count of selected students in hint
  (function(){
    const sel = document.getElementById('studentSelect');
    const hint = document.getElementById('selectHint');
    const count = document.getElementById('selCount');
    if (!sel) return;
    function update(){
      const n = Array.from(sel.selectedOptions).length;
      if (n > 0) {
        hint.classList.remove('hidden');
        count.textContent = n;
      } else {
        hint.classList.add('hidden');
        count.textContent = 0;
      }
    }
    sel.addEventListener('change', update);
    update();
  })();
</script>
