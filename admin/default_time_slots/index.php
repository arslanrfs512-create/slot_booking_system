<?php
// index.php (Frontend + Backend in one file with Bootstrap UI + Import Option)
declare(strict_types=1);
session_start();

// ---------- CONFIG -------------
define('DB_HOST','127.0.0.1');
define('DB_NAME','slot_booking');
define('DB_USER','root');
define('DB_PASS',''); // update if needed

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo "DB connection error: " . htmlspecialchars($e->getMessage());
    exit;
}

// ---------- HELPERS -------------
function json_res($data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}
function sanitize_time($t) {
    if ($t === null || $t === '') return null;
    $p = date_create_from_format('H:i', $t) ?: date_create_from_format('H:i:s', $t);
    return $p ? $p->format('H:i:s') : null;
}

// ---------- API ACTIONS ----------
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    if ($action === 'list') {
        $stmt = $pdo->query("SELECT id, start_time, end_time FROM default_time_slots ORDER BY start_time ASC");
        json_res(['success' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($action === 'add') {
        $start_time = sanitize_time($_POST['start_time'] ?? null);
        $end_time   = sanitize_time($_POST['end_time'] ?? null);
        if (!$start_time || !$end_time) json_res(['success' => false, 'error' => 'Invalid time.']);
        if ($start_time >= $end_time) json_res(['success' => false, 'error' => 'Start time must be less than end time.']);

        // Conflict check
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM default_time_slots 
                            WHERE (start_time < ? AND end_time > ?)");
        $stmt->execute([$end_time, $start_time]);
        if ($stmt->fetchColumn() > 0) {
            json_res(['success' => false, 'error' => 'Slot conflict with existing slot(s).']);
        }

        $stmt = $pdo->prepare("INSERT INTO default_time_slots (start_time, end_time) VALUES (?, ?)");
        $stmt->execute([$start_time, $end_time]);
        json_res(['success' => true, 'message' => 'Slot added.', 'id' => $pdo->lastInsertId()]);
    }

    if ($action === 'edit') {
        $id         = $_POST['id'] ?? null;
        $start_time = sanitize_time($_POST['start_time'] ?? null);
        $end_time   = sanitize_time($_POST['end_time'] ?? null);
        if (!$id || !$start_time || !$end_time) json_res(['success' => false, 'error' => 'Invalid input.']);
        if ($start_time >= $end_time) json_res(['success' => false, 'error' => 'Start time must be less than end time.']);

        // Conflict check (exclude current ID)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM default_time_slots 
                            WHERE id != ? AND (start_time < ? AND end_time > ?)");
        $stmt->execute([$id, $end_time, $start_time]);
        if ($stmt->fetchColumn() > 0) {
            json_res(['success' => false, 'error' => 'Slot conflict with existing slot(s).']);
        }

        $stmt = $pdo->prepare("UPDATE default_time_slots SET start_time=?, end_time=? WHERE id=?");
        $stmt->execute([$start_time, $end_time, $id]);
        json_res(['success' => true, 'message' => 'Slot updated.']);
    }

    
    if ($action === 'delete') {
        $id = $_POST['id'] ?? null;
        if (!$id) json_res(['success' => false, 'error' => 'Invalid ID']);
        $stmt = $pdo->prepare("DELETE FROM default_time_slots WHERE id=?");
        $stmt->execute([$id]);
        json_res(['success' => true, 'message' => 'Slot deleted.']);
    }

    if ($action === 'import') {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            json_res(['success' => false, 'error' => 'File upload failed']);
        }

        $file = $_FILES['file']['tmp_name'];
        $handle = fopen($file, 'r');
        if (!$handle) json_res(['success' => false, 'error' => 'Unable to read file']);

        $count = 0;
        $skipped = 0;
        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            if (count($row) < 2) { 
                $skipped++; 
                continue; 
            }

            $start_time = sanitize_time(trim($row[0]));
            $end_time   = sanitize_time(trim($row[1]));

            if (!$start_time || !$end_time || $start_time >= $end_time) {
                $skipped++;
                continue;
            }

            // Conflict check (same as add)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM default_time_slots 
                                WHERE (start_time < ? AND end_time > ?)");
            $stmt->execute([$end_time, $start_time]);

            if ($stmt->fetchColumn() > 0) {
                // Conflict -> skip this row
                $skipped++;
                continue;
            }

            // Insert safe slot
            $stmt = $pdo->prepare("INSERT INTO default_time_slots (start_time, end_time) VALUES (?, ?)");
            $stmt->execute([$start_time, $end_time]);
            $count++;
        }
        fclose($handle);

        json_res(['success' => true, 'message' => "Imported $count slots. Skipped $skipped rows (invalid/conflict)."]);
    }


    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Default Time Slots</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Default Time Slots</h4>
                <!-- Import Button -->
                <form id="importForm" enctype="multipart/form-data" class="d-flex">
                    <input type="file" name="file" id="importFile" accept=".csv"
                        class="form-control form-control-sm me-2" required>
                    <button type="submit" class="btn btn-warning btn-sm">Import CSV</button>
                </form>
            </div>
            <div class="card-body">
                <!-- Form -->
                <form id="slotForm" class="row g-3 mb-4">
                    <input type="hidden" id="slotId">
                    <div class="col-md-4">
                        <label for="start_time" class="form-label">Start Time</label>
                        <input type="time" class="form-control" id="start_time" required>
                    </div>
                    <div class="col-md-4">
                        <label for="end_time" class="form-label">End Time</label>
                        <input type="time" class="form-control" id="end_time" required>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-success w-100">Save</button>
                    </div>
                </form>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-center align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="slotsTable"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
    const form = document.getElementById('slotForm');
    const slotId = document.getElementById('slotId');
    const startTime = document.getElementById('start_time');
    const endTime = document.getElementById('end_time');
    const slotsTable = document.getElementById('slotsTable');
    const importForm = document.getElementById('importForm');
    const importFile = document.getElementById('importFile');

    // Fetch and display slots
    async function loadSlots() {
        const res = await fetch('index.php?action=list');
        const data = await res.json();
        slotsTable.innerHTML = '';
        data.data.forEach(s => {
            slotsTable.innerHTML += `
          <tr>
            <td>${s.id}</td>
            <td>${s.start_time}</td>
            <td>${s.end_time}</td>
            <td>
              <button class="btn btn-sm btn-warning me-1" onclick="editSlot(${s.id}, '${s.start_time}', '${s.end_time}')">Edit</button>
              <button class="btn btn-sm btn-danger" onclick="deleteSlot(${s.id})">Delete</button>
            </td>
          </tr>
        `;
        });
    }

    // Add or update slot
    form.addEventListener('submit', async e => {
        e.preventDefault();
        const formData = new FormData();
        formData.append('start_time', startTime.value);
        formData.append('end_time', endTime.value);

        let url = 'index.php?action=add';
        if (slotId.value) {
            url = 'index.php?action=edit';
            formData.append('id', slotId.value);
        }

        const res = await fetch(url, {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        alert(data.message || data.error);
        form.reset();
        slotId.value = '';
        loadSlots();
    });

    // Edit slot
    function editSlot(id, start, end) {
        slotId.value = id;
        startTime.value = start.slice(0, 5); // hh:mm
        endTime.value = end.slice(0, 5);
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    // Delete slot
    async function deleteSlot(id) {
        if (!confirm("Are you sure?")) return;
        const formData = new FormData();
        formData.append('id', id);
        const res = await fetch('index.php?action=delete', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        alert(data.message || data.error);
        loadSlots();
    }

    // Import slots from CSV
    importForm.addEventListener('submit', async e => {
        e.preventDefault();
        const formData = new FormData(importForm);
        const res = await fetch('index.php?action=import', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        alert(data.message || data.error);
        importForm.reset();
        loadSlots();
    });

    loadSlots();
    </script>
</body>

</html>