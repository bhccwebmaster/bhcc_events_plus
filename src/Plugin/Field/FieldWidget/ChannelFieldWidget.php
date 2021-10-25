<?php

namespace Drupal\bhcc_events_plus\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\localgov_directories\Plugin\Field\FieldWidget\ChannelFieldWidget as DirectoryChannelFieldWidget;

/**
 * Plugin to display directory channels.
 *
 * @FieldWidget(
 *   id = "bhcc_events_plus_channel_selector",
 *   module = "bhcc_events_plus",
 *   label = @Translation("Event channels"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class ChannelFieldWidget extends DirectoryChannelFieldWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $primary_options = $secondary_options = $this->getOptions($items->getEntity());

    $element['primary']['#description'] = $this->t('The primary event channel this appears in. Path, breadcrumb, will be set for this channel');
    $element['secondary']['#description'] = $this->t('Other event channels this will appear in.');

    foreach ($secondary_options as $key => $value) {
      $element['secondary'][$key]['#states']['invisible'] = [':input[name=bhcc_event_channel\[primary\]]' => ['value' => $key]];
    }

    return $element;
  }


}
