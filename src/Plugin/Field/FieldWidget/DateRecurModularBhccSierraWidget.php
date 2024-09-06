<?php

declare(strict_types=1);

namespace Drupal\bhcc_events_plus\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\DependencyTrait;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\date_recur_modular\DateRecurModularWidgetFieldsTrait;
use Drupal\date_recur_modular\Plugin\Field\FieldWidget\DateRecurModularSierraWidget;

/**
 * Date recur sierra widget.
 *
 * This is a widget built with Drupal states, AJAX, modals, and interpreters.
 * It is inspired by the 2019 implementation of Google Calendar/Outlook for web
 * repeating-rule modal. It provides a single or multi-day all-day option.
 *
 * @FieldWidget(
 *   id = "date_recur_modular_bhcc_sierra",
 *   label = @Translation("Modular: BHCC Sierra"),
 *   field_types = {
 *     "date_recur"
 *   }
 * )
 */
class DateRecurModularBhccSierraWidget extends DateRecurModularSierraWidget {

  use DateRecurModularWidgetFieldsTrait;
  use DependencyTrait;

  /**
   * Name of a private tempstore collection.
   */
  public const COLLECTION_MODAL_STATE = 'date_recur_modular_sierra_modal_state';

  /**
   * Name of a key in private tempstore collection.
   */
  public const COLLECTION_MODAL_STATE_KEY = 'rrule';

  /**
   * Name of a key in private tempstore collection.
   */
  public const COLLECTION_MODAL_STATE_DTSTART = 'dtstart';

  /**
   * Name of a key in private tempstore collection.
   */
  public const COLLECTION_MODAL_STATE_REFRESH_BUTTON = 'refresh_button_name';

  /**
   * DTSTART format for COLLECTION_MODAL_STATE_DTSTART.
   */
  public const COLLECTION_MODAL_STATE_DTSTART_FORMAT = 'Y-m-d\TH:i:s e';

  /**
   * Stores field and delta.
   */
  public const COLLECTION_MODAL_STATE_PATH = 'field_and_delta';

  /**
   * Stores date format to use for occurrences.
   */
  public const COLLECTION_MODAL_DATE_FORMAT = 'date_format';

  /**
   * Form state key.
   */
  protected const FORM_STATE_RRULE_KEY = 'date_recur_modular_sierra_rrule';

  protected const MODE_ONCE = 'daily';

  protected const MODE_WEEKLY = 'weekly';

  protected const MODE_MONTHLY = 'monthly';

  protected const MODE_YEARLY = 'yearly';

  protected const HTML_TIME_FORMAT = 'H:i:s';

  /**
   * The PrivateTempStore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Provides form building and processing.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Part grid for this list.
   *
   * Temporary storage for massage values.
   *
   * @var \Drupal\date_recur\DateRecurPartGrid
   */
  protected $partGrid;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The date recur interpreter entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $dateRecurInterpreterStorage;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The date format entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $dateFormatStorage;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {

    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Remove the steps attribute from time_start and time_end.
    if (isset($element['time_start']) || $element['time_end']) {
      unset($element['time_start']['#attributes']['step']);
      unset($element['time_end']['#attributes']['step']);

      // If the default_value is either set or empty for both
      // Make them use the new format and remove the seconds from default_value.
      if (isset($element['time_start']['#default_value']) && !empty($element['time_start']['#default_value']) && ($element['time_end']['#default_value']) && !empty($element['time_end']['#default_value'])) {
        $time_start = $element['time_start']['#default_value'];
        $time_end = $element['time_end']['#default_value'];

        $element['time_start']['#default_value'] = substr($time_start, 0, 5);
        $element['time_end']['#default_value'] = substr($time_end, 0, 5);
      }
      else {
        // If the default_value has not been set, set the new frormat.
        // Ensure that the default_values format are set to HH:MM.
        $element['time_start']['#default_value'] = date('H:i');
        $element['time_end']['#default_value'] = date('H:i');
      }
    }

    return $element;
  }

}
