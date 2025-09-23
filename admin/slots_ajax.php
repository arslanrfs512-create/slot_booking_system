<?php
// admin/slots_ajax.php
require_once __DIR__.'/../config.php';

$action = $_REQUEST['action'] ?? '';

if($action=='change_staff'){
    $id = $_POST['id'] ?? 0;
    $staffId = $_POST['staff_id'] ?: null;
    $stmt = $pdo->prepare("UPDATE time_slots SET staff_id=? WHERE id=?");
    $ok = $stmt->execute([$staffId,$id]);
    echo json_encode(['success'=>$ok]);
    exit;
}


if($action === 'list') {
    $product_id = isset($_GET['product_id']) && $_GET['product_id'] !== '' ? (int)$_GET['product_id'] : null;
    $date = isset($_GET['date']) && $_GET['date'] !== '' ? sanitize_date($_GET['date']) : null;

    $sql = "SELECT ts.*, p.name AS product_name, s.name AS staff_name
            FROM time_slots ts
            LEFT JOIN products p ON p.id=ts.product_id
            LEFT JOIN staff s ON s.id=ts.staff_id
            WHERE 1";
    $params = [];
    if($product_id){ $sql .= " AND ts.product_id = ?"; $params[] = $product_id; }
    if($date){ $sql .= " AND ts.slot_date = ?"; $params[] = $date; }
    $sql .= " ORDER BY ts.slot_date, ts.start_time";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    json_res(['success'=>true,'data'=>$rows]);
}

if($action === 'change_status' && $_SERVER['REQUEST_METHOD']==='POST') {
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if(!$id || !in_array($status, ['available','unavailable','booked'])) json_res(['success'=>false,'message'=>'Invalid']);
    $old = $pdo->prepare("SELECT status FROM time_slots WHERE id=?");
    $old->execute([$id]); $oldRow = $old->fetch();
    $stmt = $pdo->prepare("UPDATE time_slots SET status=? WHERE id=?");
    $stmt->execute([$status,$id]);
    // insert status log
    $log = $pdo->prepare("INSERT INTO slot_status_log (time_slot_id, old_status, new_status, changed_by) VALUES (?, ?, ?, ?)");
    $changed_by = $_SESSION['admin_name'] ?? 'admin';
    $log->execute([$id, $oldRow['status'] ?? null, $status, $changed_by]);
    json_res(['success'=>true]);
}

if($action === 'delete' && $_SERVER['REQUEST_METHOD']==='POST') {
    $id = (int)($_POST['id'] ?? 0);
    if(!$id) json_res(['success'=>false,'message'=>'Invalid id']);
    $stmt = $pdo->prepare("DELETE FROM time_slots WHERE id=?");
    $stmt->execute([$id]);
    json_res(['success'=>true]);
}

json_res(['success'=>false,'message'=>'Unknown action']);
