<?php
require_once __DIR__ . '/../includes/db.php';

$stmt = $pdo->query("SELECT client_name, appointment_date, time_slot FROM appointments ORDER BY appointment_date, time_slot");
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$events = [];

foreach ($appointments as $appt) {
  $start = date('Y-m-d\TH:i:s', strtotime($appt['appointment_date'] . ' ' . $appt['time_slot']));
  $end = date('Y-m-d\TH:i:s', strtotime($appt['appointment_date'] . ' ' . $appt['time_slot'] . ' +1 hour')); // adjust if you want 30-min slots

  $events[] = [
    'title' => 'Appointment: ' . $appt['client_name'],
    'start' => $start,
    'end' => $end,
    'color' => '#1F3C66',
    'extendedProps' => [
      'type' => 'appointment',
      'client' => $appt['client_name'],
    ]
  ];
}

header('Content-Type: application/json');
echo json_encode($events);
exit;
