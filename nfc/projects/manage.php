<?php
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM projects WHERE id = ?")->execute([$id]);
    header("Location: manage.php?deleted=1");
    exit;
}

// Fetch projects
$projects = $pdo->query("SELECT * FROM projects ORDER BY id DESC")->fetchAll();

// Fetch assigned students
$ps = $pdo->query("
    SELECT ps.project_id, ps.student_id, s.roll_no, s.first_name, s.last_name
    FROM project_students ps
    JOIN students s ON s.id = ps.student_id
")->fetchAll();

$map = [];
foreach ($ps as $r) {
    $map[$r['project_id']][] = $r;
}
?>

<main class="flex-1 p-6">
  <div class="max-w-6xl mx-auto">

    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-semibold">Projects</h2>
        <p class="text-sm text-gray-500">Manage all projects, tech stack and assigned students.</p>
      </div>
      <a href="add.php" class="px-4 py-2 bg-violet-600 text-white rounded-lg shadow hover:bg-violet-700">Add Project</a>
    </div>

    <!-- Alerts -->
    <?php if (isset($_GET['created'])): ?>
      <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg">Project created successfully.</div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
      <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg">Project deleted.</div>
    <?php endif; ?>

    <?php if (isset($_GET['updated'])): ?>
      <div class="mb-4 p-3 bg-blue-50 border border-blue-200 text-blue-700 rounded-lg">Project updated.</div>
    <?php endif; ?>

    <!-- Table -->
    <div class="bg-white rounded-2xl shadow overflow-hidden">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
          <tr>
            <th class="px-4 py-3 text-left">ID</th>
            <th class="px-4 py-3 text-left">Name</th>
            <th class="px-4 py-3 text-left">Tech Stack</th>
            <th class="px-4 py-3 text-left">Progress</th>
            <th class="px-4 py-3 text-left">Assigned Students</th>
            <th class="px-4 py-3 text-left">Actions</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-gray-200">
          <?php if (empty($projects)): ?>
            <tr>
              <td colspan="6" class="px-4 py-4 text-center text-gray-500">No projects found.</td>
            </tr>
          <?php endif; ?>

          <?php foreach ($projects as $p): ?>
            <tr class="hover:bg-gray-50">
              <td class="px-4 py-3"><?= $p['id'] ?></td>
              <td class="px-4 py-3 font-medium"><?= htmlspecialchars($p['name']) ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($p['tech_stack']) ?></td>
              <td class="px-4 py-3"><?= (int)$p['progress_percent'] ?>%</td>

              <td class="px-4 py-3">
                <?php if (!empty($map[$p['id']])): ?>
                  <?php foreach ($map[$p['id']] as $s): ?>
                    <div><?= htmlspecialchars($s['roll_no'].' - '.$s['first_name'].' '.$s['last_name']) ?></div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <span class="text-gray-500 text-sm">No students assigned</span>
                <?php endif; ?>
              </td>

              <td class="px-4 py-3 space-x-2">
                <!-- Edit Button -->
                <a href="edit.php?id=<?= $p['id'] ?>"
                   class="inline-block px-3 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-xs">
                  Edit
                </a>

                <!-- Assign Button -->
                <a href="assign.php?project=<?= $p['id'] ?>"
                   class="inline-block px-3 py-1 bg-violet-600 text-white rounded-md hover:bg-violet-700 text-xs">
                  Assign
                </a>

                <!-- Delete Button -->
                <a href="?delete=<?= $p['id'] ?>"
                   class="inline-block px-3 py-1 bg-red-600 text-white rounded-md hover:bg-red-700 text-xs"
                   onclick="return confirm('Delete this project?')">
                  Delete
                </a>
              </td>
            </tr>

          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
