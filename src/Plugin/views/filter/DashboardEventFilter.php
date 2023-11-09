<?php

namespace Drupal\bhcc_events_plus\Plugin\views\filter;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\Plugin\views\filter\InOperator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Events dashboard views filter plugin.
 *
 * @package Drupal\bhcc_events_plus\Plugin\views\filter
 *
 * @viewsFilter("node_dashboard_event")
 */
class DashboardEventFilter extends InOperator implements ContainerFactoryPluginInterface {

  /**
   * Events dashboard views filter constructor.
   *
   * @param array $configuration
   *   The configuration to use.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManager $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (!isset($this->valueOptions)) {
      $node_storage = $this->entityTypeManager->getStorage('node');
      $nodes = $node_storage
        ->getQuery('AND')
        ->condition('type', 'event_channel')
        ->accessCheck(TRUE)
        ->execute();

      $options = [];
      foreach ($node_storage->loadMultiple($nodes) as $node) {
        $options[$node->id()] = $node->label();
      }

      $this->valueOptions = $options;
    }
    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $this->query->addField('node__bhcc_event_channel', 'bhcc_event_channel_target_id');
    $this->query->addWhere(0, 'node__bhcc_event_channel.bhcc_event_channel_target_id', $this->value, 'IN');
  }

}
