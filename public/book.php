<?php
require_once __DIR__.'/../config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__.'/../vendor/autoload.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_res(['success'=>false,'message'=>'Invalid method']);
}

$time_slot_id   = isset($_POST['time_slot_id']) ? (int)$_POST['time_slot_id'] : 0;
$customer_name  = trim($_POST['customer_name'] ?? '');
$customer_email = trim($_POST['customer_email'] ?? '');
$players_count  = isset($_POST['players_count']) ? (int)$_POST['players_count'] : 1;

if(!$time_slot_id || !$customer_name || !$customer_email){
    json_res(['success'=>false,'message'=>'Missing booking info']);
}

try {
    $pdo->beginTransaction();

    // lock slot
    $stmt = $pdo->prepare("
        SELECT ts.*, p.name as product_name 
        FROM time_slots ts 
        JOIN products p ON p.id=ts.product_id 
        WHERE ts.id = ? FOR UPDATE
    ");
    $stmt->execute([$time_slot_id]);
    $slot = $stmt->fetch();

    if(!$slot){
        $pdo->rollBack();
        json_res(['success'=>false,'message'=>'Slot not found']);
    }
    if($slot['status'] !== 'available'){
        $pdo->rollBack();
        json_res(['success'=>false,'message'=>'Slot no longer available']);
    }

    $slot_date  = $slot['slot_date'];
    $start_time = $slot['start_time'];
    $end_time   = $slot['end_time'];

    // ---- CHECK IF ANY OVERLAP ALREADY BOOKED ----
    $overlapStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM time_slots 
        WHERE slot_date = ?
          AND status = 'booked'
          AND start_time < ?
          AND end_time   > ?
    ");
    $overlapStmt->execute([$slot_date, $end_time, $start_time]);
    $overlapCount = (int)$overlapStmt->fetchColumn();

    if($overlapCount > 0){
        $pdo->rollBack();
        json_res(['success'=>false,'message'=>'Another booking already exists in this time window']);
    }

    // pricing lookup
    $template_id = $slot['template_id'];
    $price = 0.00;
    if($template_id){
        $pstmt = $pdo->prepare("
            SELECT price 
            FROM player_pricings 
            WHERE template_id = ? AND players_count = ? 
            LIMIT 1
        ");
        $pstmt->execute([$template_id, $players_count]);
        $row = $pstmt->fetch();
        $price = $row ? (float)$row['price'] : 0.00;
    }

    // booking insert
    $bstmt = $pdo->prepare("
        INSERT INTO bookings 
        (time_slot_id, customer_name, customer_email, players_count, total_price, payment_status) 
        VALUES (?, ?, ?, ?, ?, 'pending')
    ");
    $bstmt->execute([$time_slot_id, $customer_name, $customer_email, $players_count, $price]);
    $booking_id = (int)$pdo->lastInsertId();

    // ---- MARK ALL OVERLAPPING SLOTS BOOKED ----
    $upd = $pdo->prepare("
        UPDATE time_slots
        SET status = 'booked'
        WHERE slot_date = ?
          AND status = 'available'
          AND start_time < ?
          AND end_time   > ?
    ");
    $upd->execute([$slot_date, $end_time, $start_time]);

    // log changes
    $logStmt = $pdo->prepare("
        INSERT INTO slot_status_log (time_slot_id, old_status, new_status, changed_by)
        SELECT id, 'available', 'booked', ?
        FROM time_slots
        WHERE slot_date = ?
          AND start_time < ?
          AND end_time   > ?
          AND status = 'booked'
    ");
    $logStmt->execute([$customer_name, $slot_date, $end_time, $start_time]);

    $pdo->commit();

    // --- EMAIL PART ---
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = "sandbox.smtp.mailtrap.io";
        $mail->SMTPAuth   = true;
        $mail->Username   = "aa0fe1b2382f74"; 
        $mail->Password   = "7e98dd763501e9";
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom("from@example.com", "Booking System");
        $mail->addAddress($customer_email, $customer_name);  // customer
        $mail->addAddress("admin@example.com", "Admin");     // admin

        $mail->isHTML(true);
        $mail->Subject = "Booking Confirmation #$booking_id";

        $mail->Body = "
            <h3>Booking Confirmed</h3>
            <p><strong>Booking ID:</strong> $booking_id</p>
            <p><strong>Customer:</strong> {$customer_name} ({$customer_email})</p>
            <p><strong>Product:</strong> {$slot['product_name']}</p>
            <p><strong>Date:</strong> {$slot['slot_date']}</p>
            <p><strong>Time:</strong> {$slot['start_time']} - {$slot['end_time']}</p>
            <p><strong>Players:</strong> {$players_count}</p>
            <p><strong>Total Price:</strong> {$price}</p>
        ";

        $mail->send();
    } catch (Exception $e) {
        error_log("Email could not be sent. Error: {$mail->ErrorInfo}");
    }

    json_res(['success'=>true,'booking_id'=>$booking_id]);

} catch (Exception $e) {
    $pdo->rollBack();
    json_res(['success'=>false,'message'=>'Booking failed: '.$e->getMessage()]);
}
