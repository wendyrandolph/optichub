<?php
require_once base_path('app/helpers/CSRF.php');
require_once __DIR__ . '/../layouts/main.php';

$bookedSlots = [];
foreach ($booked as $appt) {
  $key = $appt['day_of_week'] . '|' . substr($appt['time_slot'], 0, 5); // e.g. 'Monday|09:00'
  $bookedSlots[$key] = true;
}
?>
<div class="page-container">
  <div class="content-wrap">
    <?php if (isset($_GET['booked']) && $_GET['booked'] == '1'): ?>
      <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
        ✅ Appointment booked successfully!
      </div>
    <?php endif; ?>
    <h2>Set Date-Specific Availability</h2>
    <form method="post" action="/scheduler/add-date-availability" style="margin-bottom: 2rem;">
      <label>Date:
        <input type="date" name="available_date" required>
      </label>
      <label>Start Time:
        <input type="time" name="start_time" required>
      </label>
      <label>End Time:
        <input type="time" name="end_time" required>
      </label>
      <button type="submit">Add Availability</button>
    </form>

    <script>
      const dateAvailability = <?= json_encode($availability) ?>;
      const bookedSlots = <?= json_encode($booked) ?>;
    </script>


    <h2>Select a Date</h2>
    <input type="date" id="date-picker" min="<?= date('Y-m-d') ?>">
    <div id="time-slots" class="week-scheduler" style="margin-top: 2rem;"></div>


    <form id="booking-form" method="post" action="/scheduler/book" style="margin-top:2rem; display:none;">
      <h3>Confirm Your Booking</h3>
      <input type="hidden" name="day_of_week" id="form-day">
      <input type="hidden" name="time_slot" id="form-time">
      <input type="hidden" name="date" id="form-date">

      <label>Name:
        <input type="text" name="client_name" required>
      </label>
      <label>Email:
        <input type="email" name="client_email" required>
      </label>
      <button type="submit">Book Appointment</button>
    </form>
  </div>
</div>
<div id="scheduler-data"
  data-availability='<?= json_encode($dateAvailability) ?>'
  data-booked-slots='<?= json_encode($bookedSlots) ?>'
  style="display: none;">
</div>