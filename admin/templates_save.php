<?php
// admin/templates_save.php
require_once __DIR__.'/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_res(['success'=>false,'message'=>'Invalid method']);
}

$product_id = filter_input(INPUT_POST,'product_id', FILTER_VALIDATE_INT);
$min_players = filter_input(INPUT_POST,'min_players', FILTER_VALIDATE_INT);
$max_players = filter_input(INPUT_POST,'max_players', FILTER_VALIDATE_INT);
$start_date = sanitize_date($_POST['start_date'] ?? null);
$end_date = sanitize_date($_POST['end_date'] ?? null);

if(!$product_id || !$min_players || !$max_players || $min_players > $max_players) {
    json_res(['success'=>false,'message'=>'Invalid inputs']);
}

// collect pricing
$prices = $_POST['prices'] ?? []; // array keyed by players_count => price
// collect dates/time slots
$dates = $_POST['dates'] ?? []; // array of arrays: date, start_time, end_time, status

try {
    $pdo->beginTransaction();

    // create template
    $stmt = $pdo->prepare("INSERT INTO slot_templates (product_id, min_players, max_players, start_date, end_date) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$product_id, $min_players, $max_players, $start_date, $end_date]);
    $template_id = (int)$pdo->lastInsertId();

    // insert player pricings
    $pstmt = $pdo->prepare("INSERT INTO player_pricings (template_id, players_count, price) VALUES (?, ?, ?)");
    for($i=$min_players;$i<=$max_players;$i++){
        $price = isset($prices[$i]) ? (float)$prices[$i] : 0.00;
        $pstmt->execute([$template_id, $i, $price]);
    }

    // prepare time slot insert
    $tstmt = $pdo->prepare("INSERT INTO time_slots (template_id, product_id, slot_date, start_time, end_time, number_of_staff, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach($dates as $d){
        // Expect safe fields; sanitize
        $slot_date = sanitize_date($d['date'] ?? null);
        $start = sanitize_time($d['start_time'] ?? null);
        $end = sanitize_time($d['end_time'] ?? null);
        $number_of_staff = $d['number_of_staff'] ?? null;
        $status = in_array($d['status'] ?? 'available', ['available','reserved','booked','blocked','cancelled']) ? $d['status'] : 'available';
        if(!$slot_date || !$start || !$end) continue;
        $tstmt->execute([$template_id, $product_id, $slot_date, $start, $end, $number_of_staff, $status]);
    }

    $pdo->commit();
    json_res(['success'=>true, 'template_id'=>$template_id]);
} catch (Exception $e) {
    $pdo->rollBack();
    json_res(['success'=>false,'message'=>'Save failed: '.$e->getMessage()]);
}