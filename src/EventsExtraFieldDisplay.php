<?php

namespace Drupal\bhcc_events_plus;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds views display for the directory channel.
 */
class EventsExtraFieldDisplay implements ContainerInjectionInterface, TrustedCallbackInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The block plugin manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $pluginBlockManager;

  /**
   * Form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * DirectoryExtraFieldDisplay constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   Entity repository.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Entity Field Manager.
   * @param \Drupal\Core\Block\BlockManagerInterface $plugin_manager_block
   *   Plugin Block Manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   Form Builder.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository, EntityFieldManagerInterface $entity_field_manager, BlockManagerInterface $plugin_manager_block, FormBuilderInterface $form_builder) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
    $this->entityFieldManager = $entity_field_manager;
    $this->pluginBlockManager = $plugin_manager_block;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity.repository'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.block'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return [
      'removeExposedFilter',
    ];
  }

  /**
   * Gets the "extra fields" for a bundle.
   *
   * @see hook_entity_extra_field_info()
   */
  public function entityExtraFieldInfo() {
    $fields = [];

    // Event channel view display.
    $fields['node']['event_channel']['display']['bhcc_events_view'] = [
      'label' => $this->t('Events listing'),
      'description' => $this->t("Output from the embedded view for this channel."),
      'weight' => -20,
      'visible' => TRUE,
    ];

    // Add banner for event in the past.
    $fields['node']['localgov_event']['display']['bhcc_event_past_banner'] = [
      'label' => $this->t('Event past banner'),
      'description' => $this->t('Banner to display if the event is in the past.'),
      'weight' => 100,
      'visible' => TRUE,
    ];

    // Add banner for event in the past.
    $fields['node']['localgov_event']['display']['bhcc_event_geo_address'] = [
      'label' => $this->t('Event geo address'),
      'description' => $this->t('The address from a geo entity.'),
      'weight' => 0,
      'visible' => TRUE,
    ];

    // Add end event checkbox on event edit form.
    $fields['node']['localgov_event']['form']['end_venue_checkbox'] = [
      'label' => $this->t('Add end venue checkbox'),
      'description' => $this->t('Add a checkbox to add an end event.'),
      'weight' => 100,
      'visible' => TRUE,
    ];

    return $fields;
  }

  /**
   * Adds view with arguments to view render array if required.
   *
   * @see localgov_directories_node_view()
   */
  public function nodeView(array &$build, NodeInterface $node, EntityViewDisplayInterface $display, $view_mode) {

    // Display the events channel view.
    if ($display->getComponent('bhcc_events_view')) {
      $build['bhcc_events_view'] = $this->getViewEmbed($node);
    }

    // Display the empty banner if event is in the past.
    if ($display->getComponent('bhcc_event_past_banner')) {

      // Get the event field.
      if ($node->getType() == 'localgov_event' && $node->hasField('localgov_event_date')) {
        if ($date_field = $node->get('localgov_event_date')->first()) {
          $date_bhcc_helper = $date_field->getHelper();

          // If the last occurance of the event is in the past,
          // then show the banner.
          if (empty($date_bhcc_helper->getOccurrences(new \DateTime(), NULL, 5))) {
            $markup = '<p class="bhcc-event-alert status-message status-message--info"';
            $markup .= $this->t('This event occurred in the past.');
            $markup .= '</p>';
            $build['bhcc_event_past_banner'] = [
              '#type' => 'markup',
              '#markup' => $markup,
            ];
          }
        }
      }
    }

    if ($display->getComponent('bhcc_event_geo_address')) {
      if ($node->hasField('localgov_event_location') && $geo_id = $node->localgov_event_location->target_id) {
        $geo = $this->entityTypeManager->getStorage('localgov_geo')->load($geo_id);
        $view = $this->entityTypeManager->getViewBuilder('localgov_geo')->view($geo, 'display_address');
        $build['bhcc_event_geo_address'] = $view;
      }
    }
  }

  /**
   * Retrieves view, and sets render array.
   */
  protected function getViewEmbed(NodeInterface $node, $search_filter = FALSE) {
    $view = Views::getView('localgov_events_listing');
    if (!$view || !$view->access('embed_1')) {
      return;
    }
    $render = [
      '#type' => 'view',
      '#name' => 'localgov_events_listing',
      '#display_id' => 'embed_1',
      '#arguments' => [$node->id()],
    ];

    return $render;
  }

}
