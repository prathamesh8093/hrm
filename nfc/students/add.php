<?php
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$errors = [];
// default old values for redisplay
$old = [
    'roll_no'    => '',
    'first_name' => '',
    'last_name'  => '',
    'email'      => '',
    'phone'      => '',
    'department' => '',
    'year'       => '' // will hold 'FY','SY','TY' or 'LY'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // populate old values trimmed
    foreach ($old as $k => $_v) {
        $old[$k] = isset($_POST[$k]) ? trim($_POST[$k]) : '';
    }

    // 1) roll_no must be integer (digits only)
    if ($old['roll_no'] === '') {
        $errors[] = "Roll number is required.";
    } elseif (!ctype_digit($old['roll_no'])) {
        $errors[] = "Roll number must contain digits only.";
    }

    // 2) first_name must be alphabetic (allow spaces, hyphen, apostrophe)
    if ($old['first_name'] === '') {
        $errors[] = "First name is required.";
    } elseif (!preg_match("/^[A-Za-z\s'\-]+$/u", $old['first_name'])) {
        $errors[] = "First name may contain only letters, spaces, hyphens and apostrophes.";
    }

    // 3) last_name (optional) - if provided validate same as first name
    if ($old['last_name'] !== '' && !preg_match("/^[A-Za-z\s'\-]+$/u", $old['last_name'])) {
        $errors[] = "Last name may contain only letters, spaces, hyphens and apostrophes.";
    }

    // 4) email (optional) - if provided must be valid
    if ($old['email'] !== '' && !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    // 5) phone (optional) - if provided must be exactly 10 digits
    if ($old['phone'] !== '') {
        $digitsOnly = preg_replace('/\D+/', '', $old['phone']); // strip non-digits
        if (!preg_match('/^\d{10}$/', $digitsOnly)) {
            $errors[] = "Phone number must contain exactly 10 digits.";
        } else {
            $old['phone'] = $digitsOnly; // normalize phone to digits only
        }
    }

    // 6) year must be one of FY, SY, TY, LY
    if ($old['year'] === '') {
        $errors[] = "Please select the year (FY / SY / TY / LY).";
    } else {
        $yearUpper = strtoupper($old['year']);
        $allowed = ['FY', 'SY', 'TY', 'LY'];
        if (!in_array($yearUpper, $allowed, true)) {
            $errors[] = "Year must be one of: FY, SY, TY, LY.";
        } else {
            $old['year'] = $yearUpper;
        }
    }

    // 7) department - optional but limit length
    if ($old['department'] !== '' && mb_strlen($old['department']) > 100) {
        $errors[] = "Department is too long.";
    }

    // If no validation errors, insert into DB
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO students (roll_no, first_name, last_name, email, phone, department, year)
                VALUES (:roll_no, :first_name, :last_name, :email, :phone, :department, :year)
            ");

            $stmt->execute([
                ':roll_no'    => $old['roll_no'],
                ':first_name' => $old['first_name'],
                ':last_name'  => $old['last_name'] ?: null,
                ':email'      => $old['email'] ?: null,
                ':phone'      => $old['phone'] ?: null,
                ':department' => $old['department'] ?: null,
                ':year'       => $old['year'] ?: null
            ]);

            // success: redirect to manage page
            header("Location: manage.php?added=1");
            exit;

        } catch (PDOException $e) {
            // handle unique constraint (roll_no or email) or other DB errors
            if ($e->getCode() == 23000) {
                // try to detect duplicate field from message
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
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<!-- Page content -->
<div class="flex-1 p-6"> <!-- push content to the right of sidebar on large screens -->
  <div class="max-w-3xl mx-auto">

    <div class="bg-white rounded-2xl shadow p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-semibold">Add Student</h2>
        <a href="/hrm-system/students/manage.php" class="inline-flex items-center gap-2 bg-gray-100 px-3 py-1 rounded-md text-sm">Manage Students</a>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="mb-4">
          <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
            <strong class="block font-medium">Please fix the following:</strong>
            <ul class="mt-2 list-disc ml-5 text-sm">
              <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      <?php endif; ?>

      <form method="post" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Roll No*</label>
            <input name="roll_no" type="text" required
                   value="<?= htmlspecialchars($old['roll_no']) ?>"
                   class="mt-1 block w-full rounded-md border-gray-200 shadow-sm focus:border-violet-400 focus:ring focus:ring-violet-200 focus:ring-opacity-50 p-2" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Phone</label>
            <input name="phone" type="text" maxlength="15" placeholder="e.g. 9876543210"
                   value="<?= htmlspecialchars($old['phone']) ?>"
                   class="mt-1 block w-full rounded-md border-gray-200 shadow-sm focus:border-violet-400 focus:ring focus:ring-violet-200 focus:ring-opacity-50 p-2" />
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">First Name*</label>
            <input name="first_name" type="text" required
                   value="<?= htmlspecialchars($old['first_name']) ?>"
                   class="mt-1 block w-full rounded-md border-gray-200 shadow-sm focus:border-violet-400 focus:ring focus:ring-violet-200 focus:ring-opacity-50 p-2" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Last Name</label>
            <input name="last_name" type="text"
                   value="<?= htmlspecialchars($old['last_name']) ?>"
                   class="mt-1 block w-full rounded-md border-gray-200 shadow-sm focus:border-violet-400 focus:ring focus:ring-violet-200 focus:ring-opacity-50 p-2" />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Email</label>
          <input name="email" type="email"
                 value="<?= htmlspecialchars($old['email']) ?>"
                 class="mt-1 block w-full rounded-md border-gray-200 shadow-sm focus:border-violet-400 focus:ring focus:ring-violet-200 focus:ring-opacity-50 p-2" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Department</label>
          <input name="department" type="text"
                 value="<?= htmlspecialchars($old['department']) ?>"
                 class="mt-1 block w-full rounded-md border-gray-200 shadow-sm focus:border-violet-400 focus:ring focus:ring-violet-200 focus:ring-opacity-50 p-2" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Year*</label>
          <select name="year" required
                  class="mt-1 block w-48 rounded-md border-gray-200 shadow-sm focus:border-violet-400 focus:ring focus:ring-violet-200 focus:ring-opacity-50 p-2">
            <option value="">-- Select year --</option>
            <option value="FY" <?= $old['year'] === 'FY' ? 'selected' : '' ?>>FY</option>
            <option value="SY" <?= $old['year'] === 'SY' ? 'selected' : '' ?>>SY</option>
            <option value="TY" <?= $old['year'] === 'TY' ? 'selected' : '' ?>>TY</option>
            <option value="LY" <?= $old['year'] === 'LY' ? 'selected' : '' ?>>LY (4th / Final year)</option>
          </select>
        </div>

        <div class="pt-2">
          <button type="submit" class="inline-flex items-center gap-2 bg-violet-600 text-white px-4 py-2 rounded-lg shadow hover:bg-violet-700">
            Add Student
          </button>
          <a href="/hrm-system/students/manage.php" class="ml-3 inline-flex items-center px-4 py-2 border rounded-lg text-sm">Cancel</a>
        </div>
      </form>
    </div>

  </div>
              </main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
