<?php
// admin/bookings.php
require_once __DIR__ . '/../config.php';

// We expect $pdo (PDO) from config.php

// Get products for filter dropdown
$products = $pdo->query("SELECT id, name FROM products ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// booking statuses we show for filtering
$payment_statuses = ['pending','paid','failed'];
$slot_statuses = ['available','unavailable','booked'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin - Bookings</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
  <style>
    .stat-box { padding:12px; border-radius:8px; background:#fff; box-shadow:0 1px 3px rgba(0,0,0,.05); }
    .small-muted { font-size:0.85rem; color:#6c757d; }
    .cursor-pointer{ cursor:pointer; }
  </style>
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Bookings</h3>
    <a class="btn btn-outline-secondary" href="index.php">Back to Templates</a>
  </div>

  <!-- Filters -->
  <div class="card mb-3 p-3">
    <div class="row g-2 align-items-center">
      <div class="col-md-3">
        <label class="form-label small-muted">Product</label>
        <select id="filterProduct" class="form-select">
          <option value="">All products</option>
          <?php foreach($products as $p): ?>
            <option value="<?=htmlspecialchars($p['id'])?>"><?=htmlspecialchars($p['name'])?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label small-muted">From date</label>
        <input type="date" id="filterFrom" class="form-control">
      </div>

      <div class="col-md-2">
        <label class="form-label small-muted">To date</label>
        <input type="date" id="filterTo" class="form-control">
      </div>

      <div class="col-md-2">
        <label class="form-label small-muted">Payment status</label>
        <select id="filterPayment" class="form-select">
          <option value="">All</option>
          <option value="pending">pending</option>
          <option value="paid">paid</option>
          <option value="failed">failed</option>
        </select>
      </div>

      <div class="col-md-1">
        <label class="form-label small-muted">Show</label>
        <button id="btnSearch" class="btn btn-primary w-100">Search</button>
      </div>

      <div class="col-md-2 text-end">
        <label class="form-label small-muted d-block">&nbsp;</label>
        <div class="btn-group">
          <button id="btnExport" class="btn btn-outline-secondary">Export CSV</button>
          <button id="btnRefresh" class="btn btn-outline-secondary">Refresh</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Stats -->
  <div class="row g-3 mb-3" id="statsRow">
    <div class="col-md-3">
      <div class="stat-box">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="h5" id="stat_total">0</div>
            <div class="small-muted">Total bookings</div>
          </div>
          <div class="text-end">
            <div class="small-muted">Payment</div>
            <div id="stat_paid" class="text-success">0 paid</div>
            <div id="stat_pending" class="text-warning">0 pending</div>
            <div id="stat_failed" class="text-danger">0 failed</div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-9">
      <div class="stat-box">
        <div class="d-flex gap-3 flex-wrap" id="slotStatusCounts">
          <!-- JS will fill per-slot-status counts as badges -->
        </div>
      </div>
    </div>
  </div>

  <!-- Table -->
  <div class="card">
    <div class="card-body p-2">
      <div id="bookingsTableWrap" class="table-responsive">
        <table class="table table-sm table-striped mb-0">
          <thead>
            <tr>
              <th>#</th>
              <th>Booking ID</th>
              <th>Product</th>
              <th>Date</th>
              <th>Time</th>
              <th>Customer</th>
              <th>Players</th>
              <th>Total</th>
              <th>Payment Status</th>
              <th>Slot Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="bookingsTbody">
            <tr><td colspan="11" class="text-center small-muted">Loading...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Booking details -->
<div class="modal" tabindex="-1" id="bookingModal">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Booking Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body" id="bookingDetails">Loading...</div>
      <div class="modal-footer">
        <button type="button" id="modalClose" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
/*
  admin/bookings.php JS
  - loads booking list via AJAX
  - shows aggregated stats
  - allows changing payment_status and cancel booking
*/

function humanDate(d){
  return d ? d : '';
}

function loadBookings(){
  const data = {
    action: 'list',
    product_id: $('#filterProduct').val(),
    from: $('#filterFrom').val(),
    to: $('#filterTo').val(),
    payment_status: $('#filterPayment').val()
  };
  $('#bookingsTbody').html('<tr><td colspan="11" class="text-center small-muted">Loading...</td></tr>');
  $.getJSON('bookings_ajax.php', data, function(resp){
    if(!resp || !resp.success){
      $('#bookingsTbody').html('<tr><td colspan="11" class="text-danger">Error loading bookings</td></tr>');
      return;
    }

    const rows = resp.data.bookings;
    const totals = resp.data.totals; // { total, paid, pending, failed, slot_counts: { booked:.. } }

    // Stats
    $('#stat_total').text(totals.total || 0);
    $('#stat_paid').text((totals.paid||0) + ' paid');
    $('#stat_pending').text((totals.pending||0) + ' pending');
    $('#stat_failed').text((totals.failed||0) + ' failed');

    // slot status badges
    const slotStatusEl = $('#slotStatusCounts').empty();
    const slot_counts = totals.slot_counts || {};
    for(const s of ['available','unavailable','booked']){
      const cnt = slot_counts[s] || 0;
      const badge = $(`<div class="badge bg-secondary text-white me-2">${s}: ${cnt}</div>`);
      slotStatusEl.append(badge);
    }

    if(rows.length === 0){
      $('#bookingsTbody').html('<tr><td colspan="11" class="text-center small-muted">No bookings found</td></tr>');
      return;
    }

    let html = '';
    rows.forEach((r, idx) => {
      html += `<tr>
        <td>${idx+1}</td>
        <td>${r.id}</td>
        <td>${escapeHtml(r.product_name)}</td>
        <td>${r.slot_date}</td>
        <td>${r.start_time} - ${r.end_time}</td>
        <td>${escapeHtml(r.customer_name)}<br><small>${escapeHtml(r.customer_email)}</small></td>
        <td>${r.players_count}</td>
        <td>${r.total_price}</td>
        <td>
          <select class="form-select form-select-sm change-payment" data-id="${r.id}">
            <option value="pending" ${r.payment_status==='pending'?'selected':''}>pending</option>
            <option value="paid" ${r.payment_status==='paid'?'selected':''}>paid</option>
            <option value="failed" ${r.payment_status==='failed'?'selected':''}>failed</option>
          </select>
        </td>
        <td>${r.slot_status}</td>
        <td>
          <button class="btn btn-sm btn-info view-booking" data-id="${r.id}">View</button>
          <button class="btn btn-sm btn-danger cancel-booking ms-1" data-id="${r.id}">Cancel</button>
        </td>
      </tr>`;
    });
    $('#bookingsTbody').html(html);
  }).fail(function(xhr){
    $('#bookingsTbody').html('<tr><td colspan="11" class="text-danger">Server error</td></tr>');
    console.error(xhr.responseText);
  });
}

$(function(){
  loadBookings();

  $('#btnSearch').on('click', loadBookings);
  $('#btnRefresh').on('click', function(){ $('#filterProduct').val(''); $('#filterFrom').val(''); $('#filterTo').val(''); $('#filterPayment').val(''); loadBookings(); });

  // Export
  $('#btnExport').on('click', function(){
    const params = $.param({
      action: 'export',
      product_id: $('#filterProduct').val(),
      from: $('#filterFrom').val(),
      to: $('#filterTo').val(),
      payment_status: $('#filterPayment').val()
    });
    window.location = 'bookings_ajax.php?' + params;
  });

  // change payment status
  $(document).on('change', '.change-payment', function(){
    const id = $(this).data('id');
    const status = $(this).val();
    $.post('bookings_ajax.php', { action:'change_payment', id: id, payment_status: status }, function(resp){
      if(!resp.success) { alert('Failed: ' + resp.message); }
      loadBookings();
    }, 'json').fail(function(){ alert('Server error'); });
  });

  // cancel booking
  $(document).on('click', '.cancel-booking', function(){
    if(!confirm('Cancel this booking? This will free the slot.')) return;
    const id = $(this).data('id');
    $.post('bookings_ajax.php', { action:'cancel', id: id }, function(resp){
      if(resp.success){
        alert('Booking cancelled');
        loadBookings();
      } else {
        alert('Failed: ' + resp.message);
      }
    }, 'json').fail(function(){ alert('Server error'); });
  });

  // view details
  $(document).on('click', '.view-booking', function(){
    const id = $(this).data('id');
    $('#bookingDetails').html('Loading...');
    $.getJSON('bookings_ajax.php', { action:'get', id: id }, function(resp){
      if(!resp.success){ $('#bookingDetails').html('<div class="text-danger">Cannot load details</div>'); return; }
      const b = resp.data;
      let html = `<table class="table table-sm"><tbody>
        <tr><th>Booking ID</th><td>${b.id}</td></tr>
        <tr><th>Product</th><td>${escapeHtml(b.product_name)}</td></tr>
        <tr><th>Date</th><td>${b.slot_date}</td></tr>
        <tr><th>Time</th><td>${b.start_time} - ${b.end_time}</td></tr>
        <tr><th>Customer</th><td>${escapeHtml(b.customer_name)} &lt;${escapeHtml(b.customer_email)}&gt;</td></tr>
        <tr><th>Players</th><td>${b.players_count}</td></tr>
        <tr><th>Total</th><td>${b.total_price}</td></tr>
        <tr><th>Payment Status</th><td>${b.payment_status}</td></tr>
        <tr><th>Slot Status</th><td>${b.slot_status}</td></tr>
      </tbody></table>`;
      $('#bookingDetails').html(html);
      var modal = new bootstrap.Modal(document.getElementById('bookingModal'));
      modal.show();
    }).fail(function(){ $('#bookingDetails').html('<div class="text-danger">Server error</div>'); });
  });

});

// small helpers
function escapeHtml(str){
  if(!str) return '';
  return String(str).replace(/[&<>"']/g, function(m){ return { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m]; });
}
</script>
</body>
</html>
