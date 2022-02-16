<?php

namespace Drupal\bhcc_events_plus\Plugin\Field\FieldFormatter;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\date_recur\Plugin\Field\FieldFormatter\DateRecurBasicFormatter;

/**
 * Plugin implementation of the 'bhcc_date_recur_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "bhcc_date_recur_field_formatter",
 *   label = @Translation("BHCC Date Recuring Field Formatter"),
 *   field_types = {
 *     "date_recur"
 *   }
 * )
 */
class BHCCDateRecurFieldFormatter extends DateRecurBasicFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      // Implement default settings.
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    return [
      // Implement settings form.
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = [];
    // Implement settings summary.
    $summary = parent::settingsSummary();

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    // Whether maximum is per field item or in total.
    $isSharedMaximum = !$this->getSetting('count_per_item');
    // Maximum amount of occurrences to be displayed.
    $occurrenceQuota = (int) $this->getSetting('show_next');

    $elements = [];
    foreach ($items as $delta => $item) {
      // $value = $this->viewItem($item, $occurrenceQuota);
      $value = $this->viewNextDate($item);
      $occurrenceQuota -= ($isSharedMaximum ? count($value['#occurrences']) : 0);
      $elements[$delta] = $value;
      if ($occurrenceQuota <= 0) {
        break;
      }
    }

    return $elements;
  }

  /**
   * View next Date
   * @param  FieldItemInterface $item
   *   Individual field item.
   * @return Array
   *   Build array with next date.
   */
  protected function viewNextDate(FieldItemInterface $item) {

    /** @var \Drupal\Core\Datetime\DrupalDateTime|null $startDate */
    $startDate = $item->start_date;
    /** @var \Drupal\Core\Datetime\DrupalDateTime|null $endDate */
    $endDate = $item->end_date ?? $startDate;

    // Initialise next date to current date.
    $nextStartDate = $startDate;
    $nextEndDate = $endDate;

    // If this is a recurring event, find the next event that is in the future.
    if ($item->isRecurring()) {
      // Parent getOccurrences method will only return future results.
      $occurrence = $this->getOccurrences($item, 1);
      if (is_array($occurrence) && !empty($occurrence)) {
        $occurrence = reset($occurrence);
        $nextStartDate = DrupalDateTime::createFromDateTime($occurrence->getStart());
        $nextEndDate = DrupalDateTime::createFromDateTime($occurrence->getEnd());
      }
    }

    // If initial end date is in the past, use the recurring date.
    if (strtotime((string) $endDate) < time()) {
      $startDate = $nextStartDate;
      $endDate = $nextEndDate;
    }

    // Build field item, use only the single date format.
    $build = [
      '#theme' => 'date_recur_next_date',
    ];
    $build['#date'] = $this->buildDateRangeValue($startDate, $endDate, FALSE);

    return $build;
  }


  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return nl2br(Html::escape($item->value));
  }

}
