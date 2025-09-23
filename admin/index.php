<?php
// admin/index.php
require_once __DIR__.'/../config.php';

// load products and staff
$products = $pdo->query("SELECT id,name FROM products ORDER BY id")->fetchAll();
$staff = $pdo->query("SELECT id,name FROM staff ORDER BY id")->fetchAll();
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Admin - Slot Templates</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/custom.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Admin — Create Slot Template</h2>
            <a class="btn btn-outline-secondary" href="slots_list.php">Manage Slots / Templates</a>
        </div>

        <form id="templateForm" class="card p-3">
            <div class="mb-3 row">
                <label class="col-sm-2 col-form-label">Product</label>
                <div class="col-sm-4">
                    <select name="product_id" id="product_id" class="form-select" required>
                        <option value="">Select product</option>
                        <?php foreach($products as $p): ?>
                        <option value="<?=htmlspecialchars($p['id'])?>"><?=htmlspecialchars($p['name'])?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <label class="col-sm-2 col-form-label">Date range</label>
                <div class="col-sm-4 d-flex gap-2">
                    <input type="date" class="form-control" name="start_date" id="start_date" required>
                    <input type="date" class="form-control" name="end_date" id="end_date" required>
                </div>
            </div>

            <div class="mb-3 row">
                <label class="col-sm-2 col-form-label">Min players</label>
                <div class="col-sm-2"><input type="number" min="1" value="1" name="min_players" id="min_players"
                        class="form-control" required></div>
                <label class="col-sm-2 col-form-label">Max players</label>
                <div class="col-sm-2"><input type="number" min="1" value="4" name="max_players" id="max_players"
                        class="form-control" required></div>
                <div class="col-sm-4">
                    <button type="button" id="generatePlayers" class="btn btn-info">Generate Players Pricing</button>
                </div>
            </div>

            <div id="playersPricingWrap" class="mb-3"></div>

            <hr>
            <div class="mb-3">
                <h5>Dates & Time Slots</h5>
                <div class="mb-2 text-muted">After selecting a date range click "Generate Dates" then add time slots per
                    date.</div>
                <div class="d-flex gap-2 mb-2">
                    <button type="button" id="generateDates" class="btn btn-primary">Generate Dates</button>
                    <button type="button" id="clearDates" class="btn btn-outline-secondary">Clear Dates</button>
                </div>
                <div id="datesContainer"></div>
            </div>

            <!-- Hidden until slots are generated -->
            <div class="d-flex justify-content-end" id="saveBtnWrap" style="display:none;">
                <button type="submit" class="btn btn-success">Save Template & Slots</button>
            </div>
        </form>
    </div>

    <script>
    $(function() {


        function formatDate(d) {
            return d.toISOString().slice(0, 10);
        }
        let slotIndex = 0;

        function toggleSaveButton() {
            if ($('.slot-row').length > 0) {
                $('#saveBtnWrap').show();
            } else {
                $('#saveBtnWrap').hide();
            }
        }

        // Generate date blocks
        $('#generateDates').on('click', function() {
            $('#datesContainer').empty();
            slotIndex = 0;
            let s = $('#start_date').val(),
                e = $('#end_date').val();
            if (!s || !e) {
                alert('Select start and end dates');
                return;
            }
            let start = new Date(s),
                end = new Date(e);
            if (start > end) {
                alert('Start must be <= end');
                return;
            }

            for (let dt = new Date(start); dt <= end; dt.setDate(dt.getDate() + 1)) {
                const dateStr = formatDate(new Date(dt));
                const block = $(`
          <div class="card mb-2 date-block" data-date="${dateStr}">
            <div class="card-body">
              <div class="row mb-2 align-items-center">
                <div class="col-sm-3"><strong>${dateStr}</strong></div>
                <label class="col-sm-3 col-form-label text-end">Number of Staff</label>
                <div class="col-sm-2">
                  <input type="number" name="dates[${slotIndex}][number_of_staff]" 
                        class="form-control form-control-sm" 
                        placeholder="e.g. 4" value="4" min="1" required>
                </div>
                <div class="col-sm-4 text-end">
                  <button type="button" class="btn btn-sm btn-outline-primary addSlotBtn">
                    Add Time Slot
                  </button>
                </div>
              </div>
              <div class="time-slots-list"></div>
            </div>
          </div>
        `);

                // Auto-generate 4 slots (09:00 - 13:00)
                const times = [
                    ["09:00", "10:00"],
                    ["10:00", "11:00"],
                    ["11:00", "12:00"],
                    ["12:00", "13:00"]
                ];
                times.forEach(t => {
                    const row = $(`
              <div class="row align-items-center mb-2 slot-row">
                <input type="hidden" name="dates[${slotIndex}][date]" value="${dateStr}">
                <div class="col-auto"><input type="time" name="dates[${slotIndex}][start_time]" value="${t[0]}" class="form-control form-control-sm" required></div>
                <div class="col-auto"><input type="time" name="dates[${slotIndex}][end_time]" value="${t[1]}" class="form-control form-control-sm" required></div>
                <div class="col-auto">
                  <select name="dates[${slotIndex}][status]" class="form-select form-select-sm" required>
                    <option value="available">Available</option>
                    <option value="reserved">Reserved</option>
                    <option value="booked">Booked</option>
                    <option value="blocked">Blocked</option>
                    <option value="cancelled">Cancelled</option>
                  </select>
                </div>
                <div class="col-auto">
                  <button type="button" class="btn btn-sm btn-danger remove-slot">×</button>
                </div>
              </div>
            `);
                    block.find('.time-slots-list').append(row);
                    slotIndex++;
                });

                $('#datesContainer').append(block);
            }

            toggleSaveButton();
        });


        $('#clearDates').on('click', function() {
            $('#datesContainer').empty();
            slotIndex = 0;
            toggleSaveButton();
        });

        // Add slot row
        $(document).on('click', '.addSlotBtn', function() {
            const parent = $(this).closest('.card');
            const date = parent.data('date');

            const row = $(`
      <div class="row align-items-center mb-2 slot-row">
        <input type="hidden" name="dates[${slotIndex}][date]" value="${date}">
        <div class="col-auto"><input type="time" name="dates[${slotIndex}][start_time]" class="form-control form-control-sm" required></div>
        <div class="col-auto"><input type="time" name="dates[${slotIndex}][end_time]" class="form-control form-control-sm" required></div>
        <div class="col-auto">
          <select name="dates[${slotIndex}][status]" class="form-select form-select-sm" required>
            <option value="available">Available</option>
            <option value="reserved">Reserved</option>
            <option value="booked">Booked</option>
            <option value="blocked">Blocked</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
        <div class="col-auto"><button type="button" class="btn btn-sm btn-danger remove-slot">×</button></div>
      </div>
    `);
            parent.find('.time-slots-list').append(row);
            slotIndex++;
            toggleSaveButton();
        });

        $(document).on('click', '.remove-slot', function() {
            $(this).closest('.slot-row').remove();
            toggleSaveButton();
        });

        // Generate players pricing
        $('#generatePlayers').on('click', function() {
            $('#playersPricingWrap').empty();
            let min = parseInt($('#min_players').val()),
                max = parseInt($('#max_players').val());
            if (isNaN(min) || isNaN(max) || min <= 0 || max < min) {
                alert('Invalid min/max');
                return;
            }
            const container = $(
                '<div class="card p-3 mb-3"><h6>Players Pricing</h6><div id="pricingRows"></div></div>'
            );
            for (let i = min; i <= max; i++) {
                const r = $(`
        <div class="row mb-2 align-items-center">
          <div class="col-3"><label>${i} Players</label></div>
          <div class="col-3">
            <input type="number" step="0.01" min="0" class="form-control" name="prices[${i}]" placeholder="e.g. 53.00" required>
          </div>
        </div>
      `);
                container.find('#pricingRows').append(r);
            }
            $('#playersPricingWrap').append(container);
        });

        // Submit template + pricing + times
        $('#templateForm').on('submit', function(e) {
            e.preventDefault();
            if ($('.slot-row').length === 0) {
                alert('Add at least one slot before saving.');
                return;
            }
            const formData = $(this).serialize();
            $.ajax({
                url: 'templates_save.php',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function(resp) {
                    if (resp.success) {
                        alert('Template and slots saved successfully!');
                        window.location.href = "/admin/slots_list.php"
                    } else {
                        alert('Error: ' + (resp.message || 'Unknown'));
                    }
                },
                error: function(xhr) {
                    alert('Server error. See console.');
                    console.error(xhr.responseText);
                }
            });
        });

    });
    </script>
</body>

</html>