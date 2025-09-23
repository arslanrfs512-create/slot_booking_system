<?php

require_once __DIR__.'/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_res(['success'=>false,'message'=>'Invalid method']);
}

$min_players  = filter_input(INPUT_POST,'min_players', FILTER_VALIDATE_INT);
$max_players  = filter_input(INPUT_POST,'max_players', FILTER_VALIDATE_INT);
$start_date   = sanitize_date($_POST['start_date'] ?? null);
$end_date     = sanitize_date($_POST['end_date'] ?? null);

if(!$min_players || !$max_players || $min_players > $max_players) {
    json_res(['success'=>false,'message'=>'Invalid inputs']);
}

// collect pricing
$prices = $_POST['prices'] ?? []; // array keyed by players_count => price
// collect dates/time slots
$dates  = $_POST['dates'] ?? []; // array of arrays: date, start_time, end_time, status

try {
    $pdo->beginTransaction();

    // ✅ Get all products
    $products = $pdo->query("SELECT id FROM products")->fetchAll(PDO::FETCH_COLUMN);

    if(!$products) {
        throw new Exception("No products found");
    }

    // Prepare insert statements
    $templateStmt = $pdo->prepare("
        INSERT INTO slot_templates (product_id, min_players, max_players, start_date, end_date) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $pricingStmt = $pdo->prepare("
        INSERT INTO player_pricings (template_id, players_count, price) 
        VALUES (?, ?, ?)
    ");
    $slotStmt = $pdo->prepare("
        INSERT INTO time_slots (template_id, product_id, slot_date, start_time, end_time, number_of_staff, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $checkStmt = $pdo->prepare("
        SELECT id FROM time_slots 
        WHERE product_id = ? AND slot_date = ? AND start_time = ? AND end_time = ?
    ");

    $template_ids = [];

    foreach($products as $product_id) {
        // Insert template
        $templateStmt->execute([$product_id, $min_players, $max_players, $start_date, $end_date]);
        $template_id = (int)$pdo->lastInsertId();
        $template_ids[] = $template_id;

        // Insert player pricings
        for($i=$min_players;$i<=$max_players;$i++){
            $price = isset($prices[$i]) ? (float)$prices[$i] : 0.00;
            $pricingStmt->execute([$template_id, $i, $price]);
        }

        // Insert time slots
        foreach($dates as $d){
            $slot_date = sanitize_date($d['date'] ?? null);
            $start     = sanitize_time($d['start_time'] ?? null);
            $end       = sanitize_time($d['end_time'] ?? null);
            $number_of_staff = $d['number_of_staff'] ?? null;
            $status    = in_array($d['status'] ?? 'available', ['available','unavailable','booked']) 
                            ? $d['status'] : 'available';

            if(!$slot_date || !$start || !$end) continue;

            // Check duplicate slot conflict
            $checkStmt->execute([$product_id, $slot_date, $start, $end]);
            if($checkStmt->fetch()) {
                throw new Exception("Duplicate slot conflict for product {$product_id}: {$slot_date} {$start}-{$end}");
            }

            // Insert slot
            $slotStmt->execute([$template_id, $product_id, $slot_date, $start, $end, $number_of_staff, $status]);
        }
    }

    $pdo->commit();
    json_res(['success'=>true, 'template_ids'=>$template_ids]);

} catch (Exception $e) {
    $pdo->rollBack();
    json_res(['success'=>false,'message'=>'Save failed: '.$e->getMessage()]);
}
