<?php

namespace Drupal\bhcc_events_plus;

use Drupal\date_recur\DateRange;
use Drupal\date_recur\Plugin\Field\FieldType\DateRecurFieldItemList;

/**
 * Reccuring date field values helper.
 *
 * Extract values from multifield recurring date fields.
 */
class ReccuringDateValuesHelper {

  /**
   * Date Recur field.
   *
   * @var Drupal\date_recur\Plugin\Field\FieldType\DateRecurFieldItemList
   */
  protected $field;

  /**
   * Class contructor.
   *
   * @param \Drupal\date_recur\Plugin\Field\FieldType\DateRecurFieldItemList $field
   *   Reccuring date field.
   */
  public function __construct(DateRecurFieldItemList $field) {
    $this->field = $field;
  }

  /**
   * Has future occurances.
   *
   * @return bool
   *   TRUE if there are future occurances from Now.
   */
  public function hasFutureOccurances() : bool {
    return !empty($this->getOccurancesFromField('now', NULL, 1)) ? TRUE : FALSE;
  }

  /**
   * Get next occurance.
   *
   * @return \Drupal\date_recur\DateRange|null
   *   The date range set of the next occurance, or NULL if none.
   */
  public function getNextOccurance() : ?DateRange {
    $next_occurance_set = $this->getOccurancesFromField('now', NULL, 1);
    $next = reset($next_occurance_set);
    return !empty($next) ? $next : NULL;
  }

  /**
   * Get first occurance.
   *
   * Use the occurances helper to find the event start date from field deltas.
   *
   * @return \Drupal\date_recur\DateRange|null
   *   The date range set of the first occurance, or NULL if none.
   */
  public function getFirstOccurance() : ?DateRange {
    $first_occurance_set = $this->getOccurancesFromField('0001-01-01', NULL, 1);
    $first = reset($first_occurance_set);
    return !empty($first) ? $first : NULL;
  }

  /**
   * Get occurnaces between a date range.
   *
   * @param string $start_date
   *   Start date as supported by \DateTime.
   * @param string $end_date
   *   End date as supported by \DateTime.
   *
   * @return array
   *   An array of Drupal\date_recur\DateRange sets, or empty if none found.
   */
  public function getOccurancesBetweenRange(string $start_date, string $end_date) : array {
    return $this->getOccurancesFromField($start_date, $end_date);
  }

  /**
   * Get a limted set of occurances.
   *
   * @param string $start_date
   *   Start date as supported by \DateTime.
   * @param int $limit
   *   Limit of how many occurances to get.
   *
   * @return array|null
   *   An array of Drupal\date_recur\DateRange sets, or empty if none found.
   */
  public function getOccurancesSet(string $start_date, int $limit) : ?array {
    return $this->getOccurancesFromField($start_date, NULL, $limit);
  }

  /**
   * Get repeat rules accross all delters.
   *
   * @return array
   *   An array containing Drupal\date_recur\Rl\RlDateRecurRule objects.
   */
  public function getAllRepeatRules() : array {
    $rules = [];
    foreach ($this->field as $item) {
      $date_recur_helper = $item->getHelper();
      $rules = array_merge($rules, $date_recur_helper->getRules());
    }
    return $rules;
  }

  /**
   * Get occurances from the set field.
   *
   * @param string|null $start_date
   *   Start date as supported by \DateTime.
   * @param string|null $end_date
   *   End date as supported by \DateTime.
   * @param int|null $limit
   *   Limit number of results.
   *
   * @return array
   *   An array of Drupal\date_recur\DateRange sets, or empty if none found.
   */
  protected function getOccurancesFromField(string $start_date, ?string $end_date = NULL, ?int $limit = NULL) : array {

    // If there is no field set, then return empty array.
    if (!$this->field) {
      return [];
    }

    // Initialise the dates array and find all occurances accross all deltas.
    $dates = [];
    foreach ($this->field as $item) {
      $date_recur_helper = $item->getHelper();
      $dates = array_merge($dates, $date_recur_helper->getOccurrences(new \DateTime($start_date), $end_date ? new \DateTime($end_date) : NULL, $limit));
    }

    // Sort the occurances by timestamp, as they may be out of order when set
    // by different deltas.
    uasort($dates, function ($a, $b) {
      $a_time = $a?->getStart()->getTimestamp();
      $b_time = $b?->getStart()->getTimestamp();
      return $a_time <= $b_time ? -1 : 1;
    });

    // Strip back to limit if requested, as the limit sent to getOccurrences
    // will be per delta.
    if ($limit) {
      $dates = array_slice($dates, 0, $limit);
    }

    return $dates;
  }

}
