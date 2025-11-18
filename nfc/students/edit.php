<?php
// students/edit.php
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$errors = [];
$success = '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    // invalid id
    echo '<main class="flex-1 p-6"><div class="max-w-3xl mx-auto"><div class="bg-white p-6 rounded-xl shadow text-red-600">Invalid student id.</div></div></main>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// fetch student
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    echo '<main class="flex-1 p-6"><div class="max-w-3xl mx-auto"><div class="bg-white p-6 rounded-xl shadow text-red-600">Student not found.</div></div></main>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// old values for form (start with DB values)
$old = [
    'roll_no'    => $student['roll_no'] ?? '',
    'first_name' => $student['first_name'] ?? '',
    'last_name'  => $student['last_name'] ?? '',
    'email'      => $student['email'] ?? '',
    'phone'      => $student['phone'] ?? '',
    'department' => $student['department'] ?? '',
    'year'       => $student['year'] ?? ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // collect and trim
    foreach ($old as $k => $_v) {
        $old[$k] = trim($_POST[$k] ?? '');
    }

    // validation (same rules as add)
    if ($old['roll_no'] === '' || !ctype_digit($old['roll_no'])) {
        $errors[] = "Roll number must contain digits only.";
    }

    if ($old['first_name'] === '' || !preg_match("/^[A-Za-z\s'\-]+$/u", $old['first_name'])) {
        $errors[] = "First name may contain only letters, spaces, hyphens and apostrophes.";
    }

    if ($old['last_name'] !== '' && !preg_match("/^[A-Za-z\s'\-]+$/u", $old['last_name'])) {
        $errors[] = "Last name may contain only letters, spaces, hyphens and apostrophes.";
    }

    if ($old['email'] !== '' && !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if ($old['phone'] !== '') {
        $digitsOnly = preg_replace('/\D+/', '', $old['phone']);
        if (!preg_match('/^\d{10}$/', $digitsOnly)) {
            $errors[] = "Phone number must contain exactly 10 digits.";
        } else {
            $old['phone'] = $digitsOnly;
        }
    }

    $allowed = ['FY','SY','TY','LY'];
    $yearUpper = strtoupper($old['year']);
    if (!in_array($yearUpper, $allowed, true)) {
        $errors[] = "Year must be one of: FY, SY, TY, LY.";
    } else {
        $old['year'] = $yearUpper;
    }

    if ($old['department'] !== '' && mb_strlen($old['department']) > 100) {
        $errors[] = "Department is too long.";
    }

    if (empty($errors)) {
        try {
            $upd = $pdo->prepare("
                UPDATE students
                SET roll_no = :roll_no,
                    first_name = :first_name,
                    last_name = :last_name,
                    email = :email,
                    phone = :phone,
                    department = :department,
                    year = :year,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $upd->execute([
                ':roll_no'    => $old['roll_no'],
                ':first_name' => $old['first_name'],
                ':last_name'  => $old['last_name'] ?: null,
                ':email'      => $old['email'] ?: null,
                ':phone'      => $old['phone'] ?: null,
                ':department' => $old['department'] ?: null,
                ':year'       => $old['year'],
                ':id'         => $id
            ]);

            // Redirect to manage with update flag
            header("Location: manage.php?updated=1");
            exit;

        } catch (PDOException $e) {
            // Unique constraint handling
            if ($e->getCode() == 23000) {
                $msg = $e->getMessage();
                if (stripos($msg, 'roll_no') !== false) {
                    $errors[] = "A student with that roll number already exists.";
                } elseif (stripos($msg, 'email') !== false) {
                    $errors[] = "A student with that email already exists.";
                } else {
                    $errors[] = "Database constraint error: " . htmlspecialchars($msg);
                }
            } else {
                $errors[] = "Database Error: " . htmlspecialchars($e->getMessage());
            }
        }
    }
}
?>

<!-- MAIN CONTENT -->
<main class="flex-1 p-6">
  <div class="max-w-3xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-semibold">Edit Student</h2>
        <p class="text-sm text-gray-500">Update student details</p>
      </div>
      <div>
        <a href="manage.php" class="px-3 py-2 border rounded-md hover:bg-gray-50">Back to Manage</a>
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
      <form method="post" class="space-y-5">
        <div>
          <label class="block text-sm font-medium text-gray-700">Roll No*</label>
          <input name="roll_no" type="text" required
                 value="<?= htmlspecialchars($old['roll_no']) ?>"
                 class="mt-1 block w-full rounded-md border-gray-200 shadow-sm p-2" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">First Name*</label>
            <input name="first_name" type="text" required
                   value="<?= htmlspecialchars($old['first_name']) ?>"
                   class="mt-1 block w-full rounded-md border-gray-200 shadow-sm p-2" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Last Name</label>
            <input name="last_name" type="text"
                   value="<?= htmlspecialchars($old['last_name']) ?>"
                   class="mt-1 block w-full rounded-md border-gray-200 shadow-sm p-2" />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Email</label>
          <input name="email" type="email"
                 value="<?= htmlspecialchars($old['email']) ?>"
                 class="mt-1 block w-full rounded-md border-gray-200 shadow-sm p-2" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Phone (10 digits)</label>
          <input name="phone" type="text"
                 value="<?= htmlspecialchars($old['phone']) ?>"
                 class="mt-1 block w-full rounded-md border-gray-200 shadow-sm p-2" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Department</label>
          <input name="department" type="text"
                 value="<?= htmlspecialchars($old['department']) ?>"
                 class="mt-1 block w-full rounded-md border-gray-200 shadow-sm p-2" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Year</label>
          <select name="year" required class="mt-1 block w-48 rounded-md border-gray-200 shadow-sm p-2">
            <option value="">-- Select year --</option>
            <option value="FY" <?= $old['year'] === 'FY' ? 'selected' : '' ?>>FY</option>
            <option value="SY" <?= $old['year'] === 'SY' ? 'selected' : '' ?>>SY</option>
            <option value="TY" <?= $old['year'] === 'TY' ? 'selected' : '' ?>>TY</option>
            <option value="LY" <?= $old['year'] === 'LY' ? 'selected' : '' ?>>LY (Final year)</option>
          </select>
        </div>

        <div class="flex items-center gap-3">
          <button type="submit" class="px-4 py-2 bg-violet-600 text-white rounded-lg shadow hover:bg-violet-700">Save Changes</button>
          <a href="manage.php" class="ml-3 inline-flex items-center px-4 py-2 border rounded-lg text-sm">Cancel</a>
        </div>
      </form>
    </div>

  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
