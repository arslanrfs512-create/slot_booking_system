<?php
// public/calendar.php
require_once __DIR__.'/../config.php';
$products = $pdo->query("SELECT id,name FROM products")->fetchAll();
$settings = $pdo->prepare("SELECT v FROM settings WHERE k = ?");
$settings->execute(['slot_refresh_interval_seconds']);
$refreshInterval = $settings->fetchColumn() ?: 15;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Booking Calendar</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <!-- FullCalendar -->
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>

  <style>
    #calendar { max-width: 1100px; margin: 20px auto; }
    .slot-pill { display:inline-block; padding:6px 10px; border-radius:6px; margin:4px; color:#fff; cursor:pointer; }
    .s-available { background:#28a745; }
    .s-reserved  { background:#ffc107; color:#000; }
    .s-booked    { background:#6c757d; }
    .s-blocked   { background:#343a40; }
    .s-cancelled { background:#dc3545; }
  </style>
</head>
<body class="bg-light">
<div class="container py-3">
  <h3>Booking Calendar</h3>

  <div class="mb-3">
    <select id="productSelect" class="form-select w-auto d-inline-block">
      <?php foreach($products as $p): ?>
        <option value="<?=$p['id']?>"><?=htmlspecialchars($p['name'])?></option>
      <?php endforeach; ?>
    </select>
    <label class="ms-3">Auto refresh (sec):</label>
    <input id="refreshSec" type="number" min="5" value="<?=htmlspecialchars($refreshInterval)?>" class="form-control d-inline-block" style="width:90px">
  </div>

  <!-- Calendar -->
  <div id="calendar"></div>

  <!-- Slots list -->
  <div id="slotsContainer" class="mt-4"></div>
</div>

<!-- Booking modal -->
<div class="modal" tabindex="-1" id="bookModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="bookingForm">
        <div class="modal-header">
          <h5 class="modal-title">Book Slot</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div id="slotInfo" class="mb-2"></div>
          <div class="mb-2">
            <label>Customer Name</label>
            <input name="customer_name" class="form-control" required>
          </div>
          <div class="mb-2">
            <label>Email</label>
            <input name="customer_email" type="email" class="form-control" required>
          </div>
          <div class="mb-2">
            <label>Players</label>
            <select name="players_count" id="players_count" class="form-select"></select>
          </div>
          <div id="paymentPlaceholder" class="mb-2 text-muted">Payment placeholder (to be integrated later)</div>
          <input type="hidden" name="time_slot_id" id="time_slot_id">
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Confirm Booking</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
let calendar;
let refreshTimer;
let playersMap = {};

document.addEventListener('DOMContentLoaded', function() {
  var calendarEl = document.getElementById('calendar');

  // Initialize FullCalendar
  calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    // Highlight days with slots
    events: function(fetchInfo, successCallback, failureCallback) {
      const product = $('#productSelect').val();
      $.getJSON('calendar_ajax.php', {
        action:'day_summary',
        product_id: product,
        start_date: fetchInfo.startStr,
        end_date: fetchInfo.endStr
      }, function(resp){
        if(!resp.success){ failureCallback(); return; }
        const events = [];
        resp.data.forEach(day=>{
          if(day.count > 0){
            events.push({
              title: day.count+' slots',
              start: day.date,
              allDay: true,
              display: 'background',
              backgroundColor: '#28a74555'
            });
          }
        });
        successCallback(events);
      });
    },
    dateClick: function(info) {
      loadDaySlots(info.dateStr);
    }
  });
  calendar.render();

  // Auto-refresh handling
  function startAutoRefresh(){
    clearInterval(refreshTimer);
    const sec = parseInt($('#refreshSec').val()) || 15;
    refreshTimer = setInterval(()=> {
      calendar.refetchEvents(); // refresh highlights
      const lastDate = $('#slotsContainer').data('date');
      if(lastDate){ loadDaySlots(lastDate); }
    }, sec*1000);
  }
  $('#refreshSec').on('change', startAutoRefresh);
  startAutoRefresh();

  $('#productSelect').on('change', function(){
    calendar.refetchEvents();
    $('#slotsContainer').html('');
  });

  // Booking submission
  $('#bookingForm').on('submit', function(e){
    e.preventDefault();
    $.post('book.php', $(this).serialize(), function(resp){
      if(resp.success){
        alert('Booking successful!');
        const lastDate = $('#slotsContainer').data('date');
        if(lastDate){ loadDaySlots(lastDate); }
        bootstrap.Modal.getInstance(document.getElementById('bookModal')).hide();
      } else {
        alert('Booking failed: ' + resp.message);
      }
    }, 'json').fail(function(xhr){ alert('Server error'); console.error(xhr.responseText); });
  });

  // Slot click
  $(document).on('click', '.slot-pill', function(){
    const status = $(this).data('status');
    if(status !== 'available'){ alert('Slot not available'); return; }

    const slotId = $(this).data('id');
    const templateId = $(this).data('template-id');
    const date = $(this).data('date');
    const start = $(this).data('start');
    const end = $(this).data('end');

    $('#slotInfo').text(date + ' ' + start + ' - ' + end);
    $('#time_slot_id').val(slotId);

    $('#players_count').empty();
    (playersMap[templateId] || []).forEach(p=>{
      $('#players_count').append(`<option value="${p.players_count}" data-price="${p.price}">${p.players_count} — ${p.price}</option>`);
    });

    new bootstrap.Modal(document.getElementById('bookModal')).show();
  });
});

// Load slots for a specific date
function loadDaySlots(dateStr){
  const product = $('#productSelect').val();
  $('#slotsContainer').html('<div class="text-muted">Loading slots...</div>').data('date', dateStr);
  $.getJSON('calendar_ajax.php', {
    action:'fetch_slots',
    product_id: product,
    start_date: dateStr,
    end_date: dateStr
  }, function(resp){
    if(!resp.success){ $('#slotsContainer').html('Error loading slots'); return; }
    playersMap = resp.data.players_map || {};
    let html = `<h5>Slots for ${dateStr}</h5><div class="d-flex flex-wrap">`;
    resp.data.days.forEach(day=>{
      day.slots.forEach(s=>{
        html += `<div class="slot-pill s-${s.status}" 
                    data-id="${s.id}" 
                    data-template-id="${s.template_id}" 
                    data-date="${day.date}" 
                    data-start="${s.start_time}" 
                    data-end="${s.end_time}" 
                    data-status="${s.status}">
          ${s.start_time} - ${s.end_time} (${s.status})
        </div>`;
      });
    });
    html += '</div>';
    $('#slotsContainer').html(html);
  });
}
</script>
</body>
</html>
