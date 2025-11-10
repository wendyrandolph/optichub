<?php

namespace App\Services;

use DateTime;
use DateTimeImmutable;

/**
 * Handles conversion logic between absolute project dates and template offset days.
 */
class ProjectTemplateService
{
  /**
   * Converts a project with absolute due dates into a template with relative date offsets.
   */
  public function createPayloadFromProject(array $project): array
  {
    // Use PHP's DateTime/DateTimeImmutable as you did, but remove the pass-by-reference.
    $start = new DateTimeImmutable($project['start_date']);
    $stages = $project['stages'];

    foreach ($stages as $key => $stage) {
      if (empty($stage['due_date'])) continue;

      $due = new DateTimeImmutable($stage['due_date']);

      // Calculate difference in days
      $stages[$key]['due_offset_days'] = (int)$start->diff($due)->format('%a');

      // Unset absolute date for the template
      unset($stages[$key]['due_date']);
    }

    return [
      'name'   => $project['name'],
      'stages' => $stages,
      'owners' => $project['owners'] ?? []
    ];
  }

  /**
   * Instantiates a new project payload from a template by converting offsets to absolute dates.
   */
  public function instantiateProject(array $template, string $startDate): array
  {
    $start = new DateTimeImmutable($startDate);
    $stages = $template['stages'];

    foreach ($stages as $key => $stage) {
      // Modify the start date by the offset days
      $due = $start->modify('+' . ((int)$stage['due_offset_days']) . ' days');

      // Set the absolute due date
      $stages[$key]['due_date'] = $due->format('Y-m-d');
    }

    return [
      'name'       => $template['name'],
      'start_date' => $start->format('Y-m-d'),
      'stages'     => $stages,
      'owners'     => $template['owners']
    ];
  }
}
