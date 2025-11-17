<?php
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// handle delete (very simple)
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM projects WHERE id = ?")->execute([$id]);
    header("Location: manage.php?deleted=1");
    exit;
}

// fetch projects
$projects = $pdo->query("SELECT * FROM projects ORDER BY id DESC")->fetchAll();

// fetch assigned students map
$ps = $pdo->query("SELECT ps.project_id, ps.student_id, s.roll_no, s.first_name, s.last_name
                   FROM project_students ps
                   JOIN students s ON s.id = ps.student_id")->fetchAll();

$map = [];
foreach ($ps as $r) {
    $map[$r['project_id']][] = $r;
}
?>

<!-- Page Content (offset for sidebar) -->
<div class="lg:ml-72 px-4 py-6">
  <div class="max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-semibold">Projects</h2>
        <p class="text-sm text-gray-500">Create projects, assign students and track progress.</p>
      </div>
      <div class="flex items-center gap-3">
        <a href="add.php" class="inline-flex items-center gap-2 bg-violet-600 text-white px-3 py-2 rounded-md shadow hover:bg-violet-700">
          Add Project
        </a>
      </div>
    </div>

    <!-- Messages -->
    <?php if (isset($_GET['created'])): ?>
      <div class="mb-4 p-3 rounded-md bg-green-50 border border-green-200 text-green-800">
        Project created.
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
      <div class="mb-4 p-3 rounded-md bg-green-50 border border-green-200 text-green-800">
        Project deleted.
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['updated'])): ?>
      <div class="mb-4 p-3 rounded-md bg-green-50 border border-green-200 text-green-800">
        Project updated.
      </div>
    <?php endif; ?>

    <!-- Search & stats row -->
    <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div class="flex items-center gap-2">
        <input id="searchBox" type="search" placeholder="Search by name, tech stack or student..." class="w-72 p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-violet-200" />
        <button onclick="resetSearch()" class="px-3 py-2 border rounded-md text-sm hover:bg-gray-50">Reset</button>
      </div>
      <div class="text-sm text-gray-500">Total projects: <span class="font-medium"><?= count($projects) ?></span></div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto bg-white rounded-lg shadow">
      <table id="projectsTable" class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tech Stack</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Progress</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Assigned Students</th>
            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-100">
          <?php if (empty($projects)): ?>
            <tr>
              <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">No projects found.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($projects as $p): ?>
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700"><?= (int)$p['id'] ?></td>

                <td class="px-4 py-3">
                  <div class="font-medium text-gray-800"><?= htmlspecialchars($p['name']) ?></div>
                  <?php if (!empty($p['description'])): ?>
                    <div class="text-xs text-gray-500 truncate mt-1" style="max-width:420px"><?= htmlspecialchars($p['description']) ?></div>
                  <?php endif; ?>
                </td>

                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars($p['tech_stack'] ?: '—') ?></td>

                <td class="px-4 py-3 whitespace-nowrap text-sm">
                  <div class="flex items-center gap-3">
                    <div class="text-sm font-medium"><?= (int)$p['progress_percent'] ?>%</div>
                    <div class="w-32 bg-gray-100 rounded-full h-2.5 overflow-hidden">
                      <div class="h-2.5 rounded-full bg-violet-600" style="width: <?= (int)$p['progress_percent'] ?>%"></div>
                    </div>
                  </div>
                </td>

                <td class="px-4 py-3 text-sm">
                  <?php
                  if (!empty($map[$p['id']])) {
                      foreach ($map[$p['id']] as $s) {
                          echo '<div class="text-sm text-gray-700">'.htmlspecialchars($s['roll_no'].' - '.$s['first_name'].' '.$s['last_name']).'</div>';
                      }
                  } else {
                      echo '<div class="text-sm text-gray-400">No students assigned</div>';
                  }
                  ?>
                </td>

                <td class="px-4 py-3 whitespace-nowrap text-sm text-center">
                  <div class="inline-flex gap-2">
                    <a href="edit.php?id=<?= (int)$p['id'] ?>" class="px-3 py-1 rounded-md text-sm border hover:bg-gray-50">Edit</a>
                    <a href="assign.php?project=<?= (int)$p['id'] ?>" class="px-3 py-1 rounded-md text-sm border hover:bg-gray-50">Assign</a>
                    <a href="?delete=<?= (int)$p['id'] ?>"
                       onclick="return confirm('Delete project?')"
                       class="px-3 py-1 rounded-md text-sm bg-red-50 text-red-700 border border-red-100 hover:bg-red-100">
                       Delete
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination / footer -->
    <div class="mt-4 flex items-center justify-between text-sm text-gray-600">
      <div>Showing <?= count($projects) ?> projects</div>
      <div><!-- place pagination here if needed --></div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<!-- Small client-side search for convenience -->
<script>
  (function(){
    const searchBox = document.getElementById('searchBox');
    const rows = Array.from(document.querySelectorAll('#projectsTable tbody tr')).filter(r => r.querySelectorAll('td').length);
    if (!searchBox) return;

    searchBox.addEventListener('input', function(){
      const q = this.value.trim().toLowerCase();
      rows.forEach(row => {
        // search in the row text (name, tech stack, students)
        const txt = row.innerText.toLowerCase();
        row.style.display = q === '' || txt.indexOf(q) !== -1 ? '' : 'none';
      });
    });

    window.resetSearch = function(){
      searchBox.value = '';
      searchBox.dispatchEvent(new Event('input'));
    };
  })();
</script>
