<?php
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// DELETE student
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];

    $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: manage.php?deleted=1");
    exit;
}

// Fetch all students
$stmt = $pdo->query("SELECT * FROM students ORDER BY id DESC");
$students = $stmt->fetchAll();
?>

<!-- Page Content (offset for sidebar) -->
<div class="lg:ml-72 px-4 py-6">
  <div class="max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-2xl font-semibold">Manage Students</h2>
      <div class="flex items-center gap-3">
        <a href="add.php" class="inline-flex items-center gap-2 bg-violet-600 text-white px-3 py-2 rounded-md shadow hover:bg-violet-700">
          Add New Student
        </a>
        <a href="upload_csv.php" class="inline-flex items-center gap-2 border px-3 py-2 rounded-md hover:bg-gray-50">
          Bulk Upload CSV
        </a>
      </div>
    </div>

    <!-- Messages -->
    <?php if (isset($_GET['added'])): ?>
      <div class="mb-4 p-3 rounded-md bg-green-50 border border-green-200 text-green-800">
        Student added successfully.
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
      <div class="mb-4 p-3 rounded-md bg-red-50 border border-red-200 text-red-800">
        Student deleted successfully.
      </div>
    <?php endif; ?>

    <!-- Search & Filters -->
    <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div class="flex items-center gap-2">
        <input id="searchBox" type="search" placeholder="Search by roll, name, email..." class="w-72 p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-violet-200" />
        <button onclick="resetSearch()" class="px-3 py-2 border rounded-md text-sm hover:bg-gray-50">Reset</button>
      </div>
      <div class="text-sm text-gray-500">Total: <span class="font-medium"><?= count($students) ?></span></div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto bg-white rounded-lg shadow">
      <table id="studentsTable" class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Roll No</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dept</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Year</th>
            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-100">
          <?php if (empty($students)): ?>
            <tr>
              <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500">No students found.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($students as $s): ?>
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700"><?= (int)$s['id'] ?></td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars($s['roll_no']) ?></td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-800"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars($s['email']) ?: '<span class="text-gray-400">—</span>' ?></td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars($s['department']) ?: '<span class="text-gray-400">—</span>' ?></td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars($s['year']) ?></td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-center">
                  <div class="inline-flex gap-2">
                    <!-- Edit link (optional) -->
                    <a href="edit.php?id=<?= (int)$s['id'] ?>" class="px-3 py-1 rounded-md text-sm border hover:bg-gray-50">Edit</a>

                    <!-- Delete -->
                    <a href="?delete=<?= (int)$s['id'] ?>"
                       onclick="return confirm('Are you sure you want to delete this student?')"
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

    <!-- Pagination placeholder (optional) -->
    <div class="mt-4 flex items-center justify-between text-sm text-gray-600">
      <div>Showing <?= count($students) ?> entries</div>
      <div><!-- pagination can go here --></div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<!-- Small client-side search for convenience (optional, not required) -->
<script>
  (function(){
    const searchBox = document.getElementById('searchBox');
    const rows = Array.from(document.querySelectorAll('#studentsTable tbody tr')).filter(r => r.querySelectorAll('td').length);
    searchBox && searchBox.addEventListener('input', function(e){
      const q = this.value.trim().toLowerCase();
      rows.forEach(row => {
        const txt = row.innerText.toLowerCase();
        row.style.display = q === '' || txt.indexOf(q) !== -1 ? '' : 'none';
      });
    });
    window.resetSearch = function(){
      if (searchBox){ searchBox.value = ''; searchBox.dispatchEvent(new Event('input')); }
    };
  })();
</script>
