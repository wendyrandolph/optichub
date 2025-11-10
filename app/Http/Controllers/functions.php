<?php
// app/controllers/functions.php
require_once base_path('app/helpers/ApiKeyService.php');

// Dashboard Query Functions

function getActiveProjectsCount($conn)
{
    $stmt = $conn->query("SELECT COUNT(*) FROM projects WHERE status = 'open'");
    return $stmt->fetchColumn();
}

function getNewLeadsCount($conn)
{
    $stmt = $conn->query("SELECT COUNT(*) FROM leads WHERE status = 'new'");
    return $stmt->fetchColumn();
}

function getClientsCount($conn)
{
    $stmt = $conn->query("SELECT COUNT(*) FROM clients");
    return $stmt->fetchColumn();
}

function getPendingTasksCount($conn)
{
    $stmt = $conn->query("SELECT COUNT(*) FROM tasks WHERE status = 'pending'");
    return $stmt->fetchColumn();
}

function project_to_template_payload(array $project)
{
  // Extract stages/tasks and convert absolute dates to offsets (days from project start)
  $start = new DateTime($project['start_date']);
  foreach ($project['stages'] as &$stage) {
    $due = new DateTime($stage['due_date']);
    $stage['due_offset_days'] = (int)$start->diff($due)->format('%a');
    unset($stage['due_date']);
  }
  unset($stage);

  return [
    'name'   => $project['name'],
    'stages' => $project['stages'],
    'owners' => $project['owners'] ?? []
  ];
}

function instantiate_project_from_template(array $tpl, string $startDate)
{
  $start = new DateTime($startDate);
  foreach ($tpl['stages'] as &$stage) {
    $due = (clone $start)->modify('+' . ((int)$stage['due_offset_days']) . ' days');
    $stage['due_date'] = $due->format('Y-m-d');
  }
  unset($stage);

  // Return a standard project payload ready for the existing "create project" logic
  return [
    'name'       => $tpl['name'],
    'start_date' => $start->format('Y-m-d'),
    'stages'     => $tpl['stages'],
    'owners'     => $tpl['owners']
  ];
}
