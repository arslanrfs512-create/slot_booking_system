<?php
// admin/slots_list.php
require_once __DIR__.'/../config.php';

// fetch products for filter
$products = $pdo->query("SELECT id,name FROM products")->fetchAll();

// fetch staff list
$staff = $pdo->query("SELECT id,name FROM staff")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin — Slots</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Manage Time Slots</h3>
    <a class="btn btn-primary" href="index.php">Create Template</a>
  </div>

  <div class="card p-3 mb-3">
    <div class="row g-2 align-items-center">
      <div class="col-auto"><label>Product</label></div>
      <div class="col-auto">
        <select id="filterProduct" class="form-select">
          <option value="">All Products</option>
          <?php foreach($products as $p): ?>
            <option value="<?=$p['id']?>"><?=htmlspecialchars($p['name'])?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <input type="date" id="filterDate" class="form-control">
      </div>
      <div class="col-auto">
        <button id="searchBtn" class="btn btn-secondary">Search</button>
      </div>
    </div>
  </div>

  <div id="slotsTable" class="card p-3">
    <!-- <div id="loader">Loading...</div> -->
    <div id="tableWrap"></div>
  </div>
</div>

<script>
const staffList = <?=json_encode($staff)?>;

function renderStaffSelect(slotId, currentStaffId){
  let html = `<select class="form-select form-select-sm change-staff" data-id="${slotId}">`;
  html += `<option value="">-- None --</option>`;
  staffList.forEach(s=>{
    html += `<option value="${s.id}" ${s.id==currentStaffId?'selected':''}>${s.name}</option>`;
  });
  html += `</select>`;
  return html;
}

function loadSlots(){
  const product = $('#filterProduct').val();
  const date = $('#filterDate').val();
  $('#tableWrap').html('Loading...');
  $.getJSON('slots_ajax.php', {action:'list', product_id:product, date:date}, function(res){
    if(!res.success){ $('#tableWrap').html('<div class="text-danger">Error</div>'); return; }
    const rows = res.data;
    let html = `<table class="table table-sm"><thead><tr>
      <th>ID</th><th>Product</th><th>Date</th><th>Time</th><th>Staff</th><th>Status</th><th>Actions</th>
    </tr></thead><tbody>`;
    rows.forEach(r=>{
      html += `<tr>
        <td>${r.id}</td>
        <td>${r.product_name}</td>
        <td>${r.slot_date}</td>
        <td>${r.start_time} - ${r.end_time}</td>
        <td>${renderStaffSelect(r.id, r.staff_id)}</td>
        <td>
          <select class="form-select form-select-sm change-status" data-id="${r.id}">
            <option ${r.status=='available'?'selected':''} value="available">Available</option>
            <option ${r.status=='reserved'?'selected':''} value="reserved">Reserved</option>
            <option ${r.status=='booked'?'selected':''} value="booked">Booked</option>
            <option ${r.status=='blocked'?'selected':''} value="blocked">Blocked</option>
            <option ${r.status=='cancelled'?'selected':''} value="cancelled">Cancelled</option>
          </select>
        </td>
        <td>
          <button class="btn btn-sm btn-danger delete-slot" data-id="${r.id}">Delete</button>
        </td>
      </tr>`;
    });
    html += `</tbody></table>`;
    $('#tableWrap').html(html);
  });
}

$(function(){
  loadSlots();
  $('#searchBtn').on('click', loadSlots);

  // Change status
  $(document).on('change', '.change-status', function(){
    const id = $(this).data('id'), status = $(this).val();
    $.post('slots_ajax.php', {action:'change_status', id:id, status:status}, function(r){
      if(!r.success) alert('Failed: '+r.message);
    }, 'json');
  });

  // Change staff
  $(document).on('change', '.change-staff', function(){
    const id = $(this).data('id'), staffId = $(this).val();
    $.post('slots_ajax.php', {action:'change_staff', id:id, staff_id:staffId}, function(r){
      if(!r.success) alert('Failed: '+r.message);
    }, 'json');
  });

  // Delete slot
  $(document).on('click', '.delete-slot', function(){
    if(!confirm('Delete this slot?')) return;
    const id = $(this).data('id');
    $.post('slots_ajax.php', {action:'delete', id:id}, function(r){
      if(r.success) loadSlots(); else alert('Failed');
    }, 'json');
  });
});
</script>
</body>
</html>
