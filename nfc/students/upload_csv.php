<?php
// students/upload_csv.php
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Please select a valid CSV file to upload.";
    } else {
        $file = $_FILES['csv_file'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            $errors[] = "Only CSV files are allowed.";
        } else {
            // ensure uploads folder exists
            $uploadDir = __DIR__ . '/../uploads';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $targetPath = $uploadDir . '/' . time() . '_' . preg_replace('/[^a-z0-9\._-]/i','_', $file['name']);
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                $errors[] = "Failed to move uploaded file.";
            } else {
                // Process CSV
                $handle = fopen($targetPath, 'r');
                if ($handle === false) {
                    $errors[] = "Cannot open uploaded file.";
                } else {
                    $rowCount = 0;
                    $inserted = 0;
                    try {
                        $pdo->beginTransaction();
                        // Prepare insert statement once
                        $stmt = $pdo->prepare("INSERT INTO students (roll_no, first_name, last_name, email, phone, department, year) VALUES (?,?,?,?,?,?,?)");

                        // Read first line to detect header or data
                        $firstLine = fgetcsv($handle);
                        if ($firstLine === false) {
                            throw new Exception("CSV is empty.");
                        }

                        // Decide if first line is header: look for strings like 'roll', 'first', 'email'
                        $firstLineJoined = strtolower(implode(',', $firstLine));
                        $hasHeader = (stripos($firstLineJoined, 'roll') !== false || stripos($firstLineJoined, 'first') !== false || stripos($firstLineJoined, 'email') !== false);

                        // If first line is data (not header), process it
                        if (!$hasHeader) {
                            $rowCount++;
                            $data = $firstLine;
                            // safe access up to 7 columns
                            $stmt->execute([
                                $data[0] ?? null,
                                $data[1] ?? null,
                                $data[2] ?? null,
                                $data[3] ?? null,
                                $data[4] ?? null,
                                $data[5] ?? null,
                                $data[6] ?? null
                            ]);
                            $inserted++;
                        }

                        // Process remaining lines
                        while (($data = fgetcsv($handle)) !== false) {
                            // skip empty rows
                            if (count($data) === 1 && trim($data[0]) === '') continue;
                            $rowCount++;
                            // basic guard: at least roll_no and first_name expected
                            if (empty($data[0]) && empty($data[1])) continue;

                            $stmt->execute([
                                $data[0] ?? null,
                                $data[1] ?? null,
                                $data[2] ?? null,
                                $data[3] ?? null,
                                $data[4] ?? null,
                                $data[5] ?? null,
                                $data[6] ?? null
                            ]);
                            $inserted++;
                        }

                        $pdo->commit();
                        $message = "Upload complete. Processed $rowCount rows, inserted $inserted students.";
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $errors[] = "Import failed: " . $e->getMessage();
                    } finally {
                        fclose($handle);
                    }
                }
            }
        }
    }
}

// small sample CSV for download (data URI)
$sampleCsv = "roll_no,first_name,last_name,email,phone,department,year\n101,Prathmesh,Shinkar,prathmesh@example.com,9876543210,BCA,TY\n102,Amit,Patil,amitp@example.com,9898989898,BCA,TY\n";
$sampleUri = 'data:text/csv;charset=utf-8,' . rawurlencode($sampleCsv);
?>

<!-- Page Content (offset for sidebar) -->
<div class="lg:ml-72 px-4 py-6">
  <div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-semibold">Bulk Upload Students (CSV)</h2>
        <p class="text-sm text-gray-500">Upload a CSV with student records. Header row is optional.</p>
      </div>
      <div>
        <a href="manage.php" class="inline-flex items-center gap-2 border px-3 py-2 rounded-md hover:bg-gray-50">Back to Manage</a>
      </div>
    </div>

    <?php if ($message): ?>
      <div class="mb-4 p-3 rounded-md bg-green-50 border border-green-200 text-green-800">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
      <div class="mb-4 p-3 rounded-md bg-red-50 border border-red-200 text-red-800">
        <strong class="block font-medium">Errors</strong>
        <ul class="mt-2 list-disc ml-5 text-sm">
          <?php foreach ($errors as $err): ?>
            <li><?= htmlspecialchars($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow p-6">
      <form method="post" enctype="multipart/form-data" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">CSV File</label>
          <div class="mt-2 flex items-center gap-3">
            <label class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 bg-violet-600 text-white rounded-md shadow hover:bg-violet-700">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              <span id="fileLabel">Choose CSV</span>
              <input id="csvFile" name="csv_file" type="file" accept=".csv" class="sr-only" required>
            </label>

            <span id="fileName" class="text-sm text-gray-500">No file chosen</span>
          </div>
          <p class="text-xs text-gray-400 mt-1">Recommended columns: <strong>roll_no, first_name, last_name, email, phone, department, year</strong></p>
        </div>

        <div class="flex items-center gap-3">
          <button type="submit" class="inline-flex items-center gap-2 bg-violet-600 text-white px-4 py-2 rounded-lg shadow hover:bg-violet-700">
            Upload CSV
          </button>

          <a href="<?= $sampleUri ?>" download="students_sample.csv" class="inline-flex items-center gap-2 border px-4 py-2 rounded-lg text-sm hover:bg-gray-50">
            Download sample CSV
          </a>

          <a href="manage.php" class="inline-flex items-center gap-2 ml-auto border px-4 py-2 rounded-lg text-sm hover:bg-gray-50">Cancel</a>
        </div>
      </form>
    </div>

    <div class="mt-6 text-sm text-gray-600">
      <div class="mb-2 font-medium">Quick preview (first two rows):</div>
      <pre class="bg-gray-50 rounded p-3 text-xs overflow-auto"><?= htmlspecialchars($sampleCsv) ?></pre>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
  // show chosen filename
  (function(){
    const fileInput = document.getElementById('csvFile');
    const fileName = document.getElementById('fileName');
    const fileLabel = document.getElementById('fileLabel');
    if (!fileInput) return;
    fileInput.addEventListener('change', function(){
      if (this.files && this.files.length) {
        fileName.textContent = this.files[0].name;
        fileLabel.textContent = 'Change';
      } else {
        fileName.textContent = 'No file chosen';
        fileLabel.textContent = 'Choose CSV';
      }
    });
  })();
</script>
