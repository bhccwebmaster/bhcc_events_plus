<?php

namespace Drupal\bhcc_events_plus;

use Drupal\date_recur\Plugin\Field\FieldType\DateRecurFieldItemList;

/**
 * Find date instances.
 *
 * Wrapper around ReccuringDateValuesHelper class as a service.
 */
class FieldDateInstances {

  /**
   * Get occuraances from a field.
   *
   * @param \Drupal\date_recur\Plugin\Field\FieldType\DateRecurFieldItemList $field
   *   A date recur field.
   * @param string $start_date
   *   Start date as supported by \DateTime.
   * @param string $end_date
   *   End date as supported by \DateTime.
   * @param bool $group
   *   Flag to group occurances by year-month then day.
   *
   * @return array
   *   Array containing an array of [
   *     'start_datetime' => \DateTime,
   *     'end_datetime' => \DateTime,
   *   ].
   *   If group flag is selected, then the array will be grouped by [
   *   YEAR-MONTH => [
   *      DAY => []
   *   ].
   */
  public function getOccurancesFromField(DateRecurFieldItemList $field, string $start_date, string $end_date, bool $group = TRUE): array {

    $reccur_helper = new ReccuringDateValuesHelper($field);
    $instance_dates = $reccur_helper->getOccurancesBetweenRange($start_date, $end_date);
    $instances = [];
    foreach ($instance_dates as $instance_date) {
      $start = $instance_date->getStart();
      $end = $instance_date->getEnd();
      $instance = [
        'start_datetime' => $start,
        'end_datetime' => $end,
      ];

      $month = $start->format('Y-m');
      $day = $start->format('d');

      if ($group) {
        $instances[$month][$day][] = $instance;
      }
      else {
        $instances[] = $instance;
      }
    }

    return $instances;
  }

  /**
   * Get first occurance.
   *
   * @param \Drupal\date_recur\Plugin\Field\FieldType\DateRecurFieldItemList $field
   *   A date recur field items list.
   *
   * @return array
   *   Array containing [
   *     'start_datetime' => \DateTime,
   *     'end_datetime' => \DateTime,
   *   ].
   *   Or an empty array if no value.
   */
  public function getFirstOccurance(DateRecurFieldItemList $field): array {
    $reccur_helper = new ReccuringDateValuesHelper($field);
    $first_occurance = $reccur_helper->getFirstOccurance();
    $first_date = [];
    if ($first_occurance) {
      $start = $first_occurance->getStart();
      $end = $first_occurance->getEnd();
      $first_date = [
        'start_datetime' => $start,
        'end_datetime' => $end,
      ];
    }
    return $first_date;
  }

  /**
   * Get next occurance.
   *
   * @param \Drupal\date_recur\Plugin\Field\FieldType\DateRecurFieldItemList $field
   *   A date recur field items list.
   * @param string $start_date
   *   Start date as supported by \DateTime.
   *   deafult is 'now'.
   *
   * @return array
   *   Array containing [
   *     'start_datetime' => \DateTime,
   *     'end_datetime' => \DateTime,
   *   ].
   *   Or an empty array if no next occurance.
   */
  public function getNextOccurance(DateRecurFieldItemList $field, string $start_date = 'now'): array {
    $reccur_helper = new ReccuringDateValuesHelper($field);
    $next_occurance = $reccur_helper->getNextOccurance($start_date);
    $next_date = [];
    if ($next_occurance) {
      $start = $next_occurance->getStart();
      $end = $next_occurance->getEnd();
      $next_date = [
        'start_datetime' => $start,
        'end_datetime' => $end,
      ];
    }
    return $next_date;
  }

  /**
   * Has future occurance.
   *
   * @param \Drupal\date_recur\Plugin\Field\FieldType\DateRecurFieldItemList $field
   *   A date recur field items list.
   *
   * @return bool
   *   TRUE if has future occurance.
   */
  public function hasFutureOccurances(DateRecurFieldItemList $field): bool {
    $reccur_helper = new ReccuringDateValuesHelper($field);
    return $reccur_helper->hasFutureOccurances();
  }

}
