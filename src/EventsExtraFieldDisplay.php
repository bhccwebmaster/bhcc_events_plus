<?php

namespace Drupal\bhcc_events_plus;

use Drupal\Component\Utility\Html;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\localgov_directories\Constants as Directory;
use Drupal\localgov_directories\DirectoryExtraFieldDisplay;
use Drupal\localgov_directories\Entity\LocalgovDirectoriesFacets;
use Drupal\localgov_directories\Entity\LocalgovDirectoriesFacetsType;
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
   * Current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

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
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   Current route match.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository, EntityFieldManagerInterface $entity_field_manager, BlockManagerInterface $plugin_manager_block, FormBuilderInterface $form_builder, RouteMatchInterface $route_match) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
    $this->entityFieldManager = $entity_field_manager;
    $this->pluginBlockManager = $plugin_manager_block;
    $this->formBuilder = $form_builder;
    $this->routeMatch = $route_match;
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
      $container->get('form_builder'),
      $container->get('current_route_match')
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

    if ($display->getComponent('bhcc_event_geo_address')) {
      if ($node->hasField('localgov_event_location') && $localgov_location = $node->localgov_event_location->getValue()) {
        $geo_ids = array_column($localgov_location, 'target_id');
        foreach ($geo_ids as $geo_id) {
          $geo = $this->entityTypeManager->getStorage('geo_entity')->load($geo_id);
          $view = $this->entityTypeManager->getViewBuilder('geo_entity')->view($geo, 'display_address');
          $build['bhcc_event_geo_address'][$geo_id] = $view;
        }
      }
    }
  }

  /**
   * Retrieves view, and sets render array.
   */
  protected function getViewEmbed(NodeInterface $node, $search_filter = FALSE) {
    $view = Views::getView('reccuring_events_search');
    if (!$view || !$view->access('embed_1')) {
      return;
    }
    $render = [
      '#type' => 'view',
      '#name' => 'reccuring_events_search',
      '#display_id' => 'embed_1',
      '#arguments' => [$node->id()],
    ];
    if (!$search_filter) {
      $render['#post_render'] = [
        [DirectoryExtraFieldDisplay::class, 'removeExposedFilter'],
      ];
    }

    return $render;
  }

  /**
   * Prepares variables for our bundle grouped facets item list template.
   *
   * Facet bundles are sorted based on their weight.
   *
   * @see templates/facets-item-list--links--localgov-directories-facets.tpl.php
   * @see localgov_directories_preprocess_facets_item_list()
   */
  public function preprocessFacetList(array &$variables) {
    $reset_all = NULL;
    $show_reset_link = [];

    // Check if the show reset link has been enabled.
    foreach ($variables['items'] as $key => $item) {
      if ($variables["items"][$key]["value"]["#attributes"]["data-drupal-facet-item-value"] == 'reset_all') {
        $variables["attributes"]["class"][] = "facet-show-reset";
        $show_reset_link = current($variables["items"]);
      }
    }

    $facet_storage = $this->entityTypeManager
      ->getStorage(Directory::FACET_CONFIG_ENTITY_ID);
    $group_items = [];
    foreach ($variables['items'] as $key => $item) {
      if ($entity = $facet_storage->load($item['value']['#attributes']['data-drupal-facet-item-value'])) {
        assert($entity instanceof LocalgovDirectoriesFacets);
        $group_items[$entity->bundle()]['items'][$key] = $item;
      }
    }

    // This is usually on a channel node. If so remove facets not active on
    // channel.
    $active_facets = NULL;
    if (($channel = $this->routeMatch->getParameter('node'))
      && $channel instanceof NodeInterface
      && $channel->bundle() == 'event_channel'
    ) {
      $active_facets = array_column($channel->localgov_directory_facets_enable->getValue(), 'target_id');
    }
    if (!is_null($active_facets)) {
      $group_items = array_intersect_key($group_items, array_flip($active_facets));
    }

    $type_storage = $this->entityTypeManager
      ->getStorage(Directory::FACET_TYPE_CONFIG_ENTITY_ID);
    foreach ($group_items as $bundle => $items) {
      $entity = $type_storage->load($bundle);
      assert($entity instanceof LocalgovDirectoriesFacetsType);
      $group_items[$bundle]['title'] = Html::escape($this->entityRepository->getTranslationFromContext($entity)->label());
      $group_items[$bundle]['weight'] = $entity->get('weight');
    }
    uasort($group_items, static::compareFacetBundlesByWeight(...));
    $variables['items'] = $group_items;

    if (!empty($show_reset_link)) {
      // Add the reset link.
      $variables['items']['show_reset_all']['items'][] = $show_reset_link;
      $reset_all = $variables['items']['show_reset_all'];
      // Place the reset link at the top of the facet filters.
      array_unshift($variables['items'], $reset_all);
      array_pop($variables['items']);
    }
  }

  /**
   * Facet bundle comparison callback for sorting.
   *
   * Bundles are compared by their weights.  When weights are equal, labels take
   * over.
   *
   * @param array $bundle1
   *   Necessary keys: weight, title.
   * @param array $bundle2
   *   Same as $bundle1.
   */
  public static function compareFacetBundlesByWeight(array $bundle1, array $bundle2): int {

    if ($bundle1['weight'] === $bundle2['weight']) {
      return strnatcasecmp($bundle1['title'], $bundle2['title']);
    }

    return $bundle1['weight'] < $bundle2['weight'] ? -1 : 1;
  }

}
