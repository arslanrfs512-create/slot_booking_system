<?php
// public/calendar_ajax.php
require_once __DIR__.'/../config.php';

$action = $_GET['action'] ?? '';

if ($action === 'day_summary') {
    $productId = (int)($_GET['product_id'] ?? 0);
    $start = $_GET['start_date'] ?? '';
    $end   = $_GET['end_date'] ?? '';
    if(!$productId || !$start || !$end){
        json_res(['success'=>false,'message'=>'Invalid input']);
    }

    $stmt = $pdo->prepare("SELECT slot_date, COUNT(*) as cnt 
                           FROM time_slots 
                           WHERE product_id = ? AND slot_date BETWEEN ? AND ?
                           GROUP BY slot_date");
    $stmt->execute([$productId, $start, $end]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach($rows as $r){
        $data[] = ['date'=>$r['slot_date'],'count'=>(int)$r['cnt']];
    }
    json_res(['success'=>true,'data'=>$data]);
}

if ($action === 'fetch_slots') {
    $productId = (int)($_GET['product_id'] ?? 0);
    $start = $_GET['start_date'] ?? '';
    $end   = $_GET['end_date'] ?? '';
    if(!$productId || !$start || !$end){
        json_res(['success'=>false,'message'=>'Invalid input']);
    }

    // Get slots
    $stmt = $pdo->prepare("SELECT * FROM time_slots 
                           WHERE product_id = ? AND slot_date BETWEEN ? AND ?
                           ORDER BY slot_date,start_time");
    $stmt->execute([$productId, $start, $end]);
    $slots = $stmt->fetchAll();

    // Group slots by day
    $days = [];
    foreach($slots as $s){
        $d = $s['slot_date'];
        if(!isset($days[$d])) $days[$d] = ['date'=>$d,'slots'=>[]];
        $days[$d]['slots'][] = $s;
    }

    // Player pricing
    $stmt2 = $pdo->prepare("SELECT pp.template_id, pp.players_count, pp.price
                            FROM player_pricings pp
                            JOIN slot_templates st ON st.id = pp.template_id
                            WHERE st.product_id = ?");
    $stmt2->execute([$productId]);
    $pricings = $stmt2->fetchAll();

    $playersMap = [];
    foreach($pricings as $p){
        $playersMap[$p['template_id']][] = [
            'players_count'=>$p['players_count'],
            'price'=>$p['price']
        ];
    }

    json_res(['success'=>true,'data'=>[
        'days'=>array_values($days),
        'players_map'=>$playersMap
    ]]);
}

json_res(['success'=>false,'message'=>'Unknown action']);
