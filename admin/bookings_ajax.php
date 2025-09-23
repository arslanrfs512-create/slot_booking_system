<?php
// admin/bookings_ajax.php
// require_once __DIR__ . '/../config.php';
// header('Content-Type: application/json; charset=utf-8');

// $action = $_REQUEST['action'] ?? '';

// function json_out($data){ echo json_encode($data); exit; }

// if($action === 'list'){
//     // filters
//     $product_id = isset($_GET['product_id']) && $_GET['product_id'] !== '' ? (int)$_GET['product_id'] : null;
//     $from = $_GET['from'] ?? null;
//     $to = $_GET['to'] ?? null;
//     $payment_status = $_GET['payment_status'] ?? null;

//     // build where
//     $w = [];
//     $params = [];

//     if($product_id){
//         $w[] = 'ts.product_id = ?';
//         $params[] = $product_id;
//     }
//     if($from){
//         $w[] = 'ts.slot_date >= ?';
//         $params[] = $from;
//     }
//     if($to){
//         $w[] = 'ts.slot_date <= ?';
//         $params[] = $to;
//     }
//     if($payment_status){
//         $w[] = 'b.payment_status = ?';
//         $params[] = $payment_status;
//     }

//     $where = $w ? ('WHERE ' . implode(' AND ', $w)) : '';

//     // Main query: list bookings with joined time_slot/product
//     $sql = "
//       SELECT b.id, b.time_slot_id, b.customer_name, b.customer_email, b.players_count, b.total_price, b.payment_status,
//              ts.slot_date, ts.start_time, ts.end_time, ts.status AS slot_status, p.name AS product_name
//       FROM bookings b
//       JOIN time_slots ts ON ts.id = b.time_slot_id
//       JOIN products p ON p.id = ts.product_id
//       $where
//       ORDER BY ts.slot_date DESC, ts.start_time ASC, b.id DESC
//       LIMIT 1000
//     ";
//     $stmt = $pdo->prepare($sql);
//     $stmt->execute($params);
//     $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

//     // totals / aggregates
//     $totals = [
//       'total' => 0,
//       'paid' => 0,
//       'pending' => 0,
//       'failed' => 0,
//       'slot_counts' => []
//     ];

//     foreach($bookings as $row){
//       $totals['total']++;
//       $ps = $row['payment_status'] ?? 'pending';
//       if(isset($totals[$ps])) $totals[$ps]++;

//       $ss = $row['slot_status'] ?? 'unknown';
//       if(!isset($totals['slot_counts'][$ss])) $totals['slot_counts'][$ss] = 0;
//       $totals['slot_counts'][$ss]++;
//     }

//     json_out(['success'=>true,'data'=>['bookings'=>$bookings,'totals'=>$totals]]);
// }

// // get single booking
// if($action === 'get'){
//     $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
//     if(!$id) json_out(['success'=>false,'message'=>'Invalid id']);

//     $stmt = $pdo->prepare("
//       SELECT b.*, ts.slot_date, ts.start_time, ts.end_time, ts.status AS slot_status, p.name AS product_name
//       FROM bookings b
//       JOIN time_slots ts ON ts.id = b.time_slot_id
//       JOIN products p ON p.id = ts.product_id
//       WHERE b.id = ? LIMIT 1
//     ");
//     $stmt->execute([$id]);
//     $b = $stmt->fetch(PDO::FETCH_ASSOC);
//     if(!$b) json_out(['success'=>false,'message'=>'Not found']);
//     json_out(['success'=>true,'data'=>$b]);
// }

// // change payment status
// if($action === 'change_payment'){
//     if($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['success'=>false,'message'=>'Invalid method']);
//     $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
//     $payment_status = $_POST['payment_status'] ?? '';
//     if(!$id || !in_array($payment_status, ['pending','paid','failed'])) json_out(['success'=>false,'message'=>'Invalid input']);

//     $stmt = $pdo->prepare("UPDATE bookings SET payment_status = ? WHERE id = ?");
//     $ok = $stmt->execute([$payment_status, $id]);
//     json_out(['success' => (bool)$ok]);
// }

// // cancel booking (free slot, log)
// if($action === 'cancel'){
//     if($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['success'=>false,'message'=>'Invalid method']);
//     $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
//     if(!$id) json_out(['success'=>false,'message'=>'Invalid id']);

//     try {
//         $pdo->beginTransaction();

//         // get booking & time_slot
//         $stmt = $pdo->prepare("SELECT time_slot_id, customer_name FROM bookings WHERE id = ? FOR UPDATE");
//         $stmt->execute([$id]);
//         $b = $stmt->fetch(PDO::FETCH_ASSOC);
//         if(!$b){
//             $pdo->rollBack();
//             json_out(['success'=>false,'message'=>'Booking not found']);
//         }
//         $slot_id = (int)$b['time_slot_id'];

//         // set slot to 'available' (only if it was booked)
//         $ust = $pdo->prepare("UPDATE time_slots SET status = 'available' WHERE id = ? AND status = 'booked'");
//         $ust->execute([$slot_id]);

//         // insert status log if changed
//         $log = $pdo->prepare("INSERT INTO slot_status_log (time_slot_id, old_status, new_status, changed_by) VALUES (?, ?, ?, ?)");
//         $log->execute([$slot_id, 'booked', 'available', 'admin_cancel']);

//         // optionally mark booking as cancelled (we will delete or set payment_status to failed)
//         $bstmt = $pdo->prepare("UPDATE bookings SET payment_status = 'failed' WHERE id = ?");
//         $bstmt->execute([$id]);

//         $pdo->commit();
//         json_out(['success'=>true]);
//     } catch (Exception $e){
//         $pdo->rollBack();
//         json_out(['success'=>false,'message'=>$e->getMessage()]);
//     }
// }

// // export CSV
// if($action === 'export'){
//     // same filters as list
//     $product_id = isset($_GET['product_id']) && $_GET['product_id'] !== '' ? (int)$_GET['product_id'] : null;
//     $from = $_GET['from'] ?? null;
//     $to = $_GET['to'] ?? null;
//     $payment_status = $_GET['payment_status'] ?? null;

//     $w = []; $params = [];
//     if($product_id){ $w[] = 'ts.product_id = ?'; $params[] = $product_id; }
//     if($from){ $w[] = 'ts.slot_date >= ?'; $params[] = $from; }
//     if($to){ $w[] = 'ts.slot_date <= ?'; $params[] = $to; }
//     if($payment_status){ $w[] = 'b.payment_status = ?'; $params[] = $payment_status; }
//     $where = $w ? ('WHERE '.implode(' AND ', $w)) : '';

//     $sql = "
//       SELECT b.id, p.name AS product_name, ts.slot_date, ts.start_time, ts.end_time, b.customer_name, b.customer_email, b.players_count, b.total_price, b.payment_status, ts.status AS slot_status
//       FROM bookings b
//       JOIN time_slots ts ON ts.id = b.time_slot_id
//       JOIN products p ON p.id = ts.product_id
//       $where
//       ORDER BY ts.slot_date DESC, ts.start_time ASC
//     ";
//     $stmt = $pdo->prepare($sql);
//     $stmt->execute($params);
//     $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

//     // output CSV headers
//     header('Content-Type: text/csv; charset=utf-8');
//     header('Content-Disposition: attachment; filename=bookings_export_'.date('Ymd_His').'.csv');

//     $out = fopen('php://output', 'w');
//     fputcsv($out, ['booking_id','product_name','date','start_time','end_time','customer_name','customer_email','players_count','total_price','payment_status','slot_status']);
//     foreach($rows as $r){
//         fputcsv($out, [
//           $r['id'],
//           $r['product_name'],
//           $r['slot_date'],
//           $r['start_time'],
//           $r['end_time'],
//           $r['customer_name'],
//           $r['customer_email'],
//           $r['players_count'],
//           $r['total_price'],
//           $r['payment_status'],
//           $r['slot_status']
//         ]);
//     }
//     exit;
// }

// json_out(['success'=>false,'message'=>'Unknown action']);

require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';

function json_out($data){ echo json_encode($data); exit; }

/**
 * Enforce per-day staff capacity.
 */
function enforceDayCapacity(PDO $pdo, string $slot_date){
    // fetch staff limit for the date
    $stmt = $pdo->prepare("SELECT number_of_staff FROM staff_capacity WHERE slot_date = ?");
    $stmt->execute([$slot_date]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $limit = $row ? (int)$row['number_of_staff'] : 0;

    if($limit <= 0) return;

    // count paid bookings for that date
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT ts.id) AS cnt
        FROM bookings b
        JOIN time_slots ts ON ts.id = b.time_slot_id
        WHERE ts.slot_date = ? AND b.payment_status='paid'
    ");
    $stmt->execute([$slot_date]);
    $cnt = (int)$stmt->fetchColumn();

    if($cnt >= $limit){
        // mark all remaining slots as booked
        $up = $pdo->prepare("UPDATE time_slots SET status='booked' WHERE slot_date=? AND status='available'");
        $up->execute([$slot_date]);
    } else {
        // free up extra slots if below capacity
        $up = $pdo->prepare("
            UPDATE time_slots 
            SET status='available'
            WHERE slot_date=? AND status='booked'
              AND id NOT IN (
                  SELECT time_slot_id FROM bookings WHERE payment_status='paid'
              )
        ");
        $up->execute([$slot_date]);
    }
}

if($action === 'list'){
    // filters
    $product_id = isset($_GET['product_id']) && $_GET['product_id'] !== '' ? (int)$_GET['product_id'] : null;
    $from = $_GET['from'] ?? null;
    $to = $_GET['to'] ?? null;
    $payment_status = $_GET['payment_status'] ?? null;

    $w = []; $params = [];
    if($product_id){ $w[] = 'ts.product_id = ?'; $params[] = $product_id; }
    if($from){ $w[] = 'ts.slot_date >= ?'; $params[] = $from; }
    if($to){ $w[] = 'ts.slot_date <= ?'; $params[] = $to; }
    if($payment_status){ $w[] = 'b.payment_status = ?'; $params[] = $payment_status; }
    $where = $w ? ('WHERE ' . implode(' AND ', $w)) : '';

    $sql = "
      SELECT b.id, b.time_slot_id, b.customer_name, b.customer_email, b.players_count, b.total_price, b.payment_status,
             ts.slot_date, ts.start_time, ts.end_time, ts.status AS slot_status, p.name AS product_name
      FROM bookings b
      JOIN time_slots ts ON ts.id = b.time_slot_id
      JOIN products p ON p.id = ts.product_id
      $where
      ORDER BY ts.slot_date DESC, ts.start_time ASC, b.id DESC
      LIMIT 1000
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // totals
    $totals = ['total'=>0,'paid'=>0,'pending'=>0,'failed'=>0,'slot_counts'=>[]];
    foreach($bookings as $row){
        $totals['total']++;
        $ps = $row['payment_status'] ?? 'pending';
        if(isset($totals[$ps])) $totals[$ps]++;
        $ss = $row['slot_status'] ?? 'unknown';
        if(!isset($totals['slot_counts'][$ss])) $totals['slot_counts'][$ss]=0;
        $totals['slot_counts'][$ss]++;
    }

    json_out(['success'=>true,'data'=>['bookings'=>$bookings,'totals'=>$totals]]);
}

if($action === 'get'){
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if(!$id) json_out(['success'=>false,'message'=>'Invalid id']);

    $stmt = $pdo->prepare("
      SELECT b.*, ts.slot_date, ts.start_time, ts.end_time, ts.status AS slot_status, p.name AS product_name
      FROM bookings b
      JOIN time_slots ts ON ts.id = b.time_slot_id
      JOIN products p ON p.id = ts.product_id
      WHERE b.id = ? LIMIT 1
    ");
    $stmt->execute([$id]);
    $b = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$b) json_out(['success'=>false,'message'=>'Not found']);
    json_out(['success'=>true,'data'=>$b]);
}

if($action === 'change_payment'){
    if($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['success'=>false,'message'=>'Invalid method']);
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $payment_status = $_POST['payment_status'] ?? '';
    if(!$id || !in_array($payment_status, ['pending','paid','failed'])) json_out(['success'=>false,'message'=>'Invalid input']);

    $stmt = $pdo->prepare("UPDATE bookings SET payment_status = ? WHERE id = ?");
    $ok = $stmt->execute([$payment_status, $id]);

    // enforce capacity if needed
    if($ok){
        $stmt = $pdo->prepare("SELECT ts.slot_date FROM bookings b JOIN time_slots ts ON ts.id=b.time_slot_id WHERE b.id=?");
        $stmt->execute([$id]);
        $slot_date = $stmt->fetchColumn();
        if($slot_date) enforceDayCapacity($pdo, $slot_date);
    }

    json_out(['success' => (bool)$ok]);
}

if($action === 'cancel'){
    if($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['success'=>false,'message'=>'Invalid method']);
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if(!$id) json_out(['success'=>false,'message'=>'Invalid id']);

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT time_slot_id FROM bookings WHERE id=? FOR UPDATE");
        $stmt->execute([$id]);
        $b = $stmt->fetch(PDO::FETCH_ASSOC);
        if(!$b){ $pdo->rollBack(); json_out(['success'=>false,'message'=>'Booking not found']); }
        $slot_id = (int)$b['time_slot_id'];

        $ust = $pdo->prepare("UPDATE time_slots SET status='available' WHERE id=? AND status='booked'");
        $ust->execute([$slot_id]);

        $log = $pdo->prepare("INSERT INTO slot_status_log (time_slot_id, old_status, new_status, changed_by) VALUES (?, ?, ?, ?)");
        $log->execute([$slot_id, 'booked', 'available', 'admin_cancel']);

        $bstmt = $pdo->prepare("UPDATE bookings SET payment_status='failed' WHERE id=?");
        $bstmt->execute([$id]);

        // get date for enforcement
        $stmt = $pdo->prepare("SELECT slot_date FROM time_slots WHERE id=?");
        $stmt->execute([$slot_id]);
        $slot_date = $stmt->fetchColumn();

        $pdo->commit();

        if($slot_date) enforceDayCapacity($pdo, $slot_date);

        json_out(['success'=>true]);
    } catch (Exception $e){
        $pdo->rollBack();
        json_out(['success'=>false,'message'=>$e->getMessage()]);
    }
}

if($action === 'export'){
    $product_id = isset($_GET['product_id']) && $_GET['product_id'] !== '' ? (int)$_GET['product_id'] : null;
    $from = $_GET['from'] ?? null;
    $to = $_GET['to'] ?? null;
    $payment_status = $_GET['payment_status'] ?? null;

    $w = []; $params = [];
    if($product_id){ $w[] = 'ts.product_id = ?'; $params[] = $product_id; }
    if($from){ $w[] = 'ts.slot_date >= ?'; $params[] = $from; }
    if($to){ $w[] = 'ts.slot_date <= ?'; $params[] = $to; }
    if($payment_status){ $w[] = 'b.payment_status = ?'; $params[] = $payment_status; }
    $where = $w ? ('WHERE '.implode(' AND ', $w)) : '';

    $sql = "
      SELECT b.id, p.name AS product_name, ts.slot_date, ts.start_time, ts.end_time, b.customer_name, b.customer_email, b.players_count, b.total_price, b.payment_status, ts.status AS slot_status
      FROM bookings b
      JOIN time_slots ts ON ts.id = b.time_slot_id
      JOIN products p ON p.id = ts.product_id
      $where
      ORDER BY ts.slot_date DESC, ts.start_time ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=bookings_export_'.date('Ymd_His').'.csv');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['booking_id','product_name','date','start_time','end_time','customer_name','customer_email','players_count','total_price','payment_status','slot_status']);
    foreach($rows as $r){
        fputcsv($out, [$r['id'],$r['product_name'],$r['slot_date'],$r['start_time'],$r['end_time'],$r['customer_name'],$r['customer_email'],$r['players_count'],$r['total_price'],$r['payment_status'],$r['slot_status']]);
    }
    exit;
}

json_out(['success'=>false,'message'=>'Unknown action']);