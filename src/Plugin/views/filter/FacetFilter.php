<?php

namespace Drupal\bhcc_events_plus\Plugin\views\filter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\localgov_directories\Entity\LocalgovDirectoriesFacets;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Plugin\views\filter\InOperator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FacetFilter
 *
 * @package Drupal\bhcc_events_plus\views\filter
 *
 * @ViewsFilter("bhcc_localgov_facets")
 */
class FacetFilter extends InOperator {

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * FacetFilter constructor.
   *
   * @param array $configuration
   *   Configuration.
   * @param $plugin_id
   *   Plugin ID.
   * @param $plugin_definition
   *   Plugin definition.
   * @param Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity Type Manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
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
      $facets = $this->entityTypeManager
        ->getStorage('localgov_directories_facets')
        ->getQuery('AND')
        ->execute();

      $options = [];
      foreach (LocalgovDirectoriesFacets::loadMultiple($facets) as $facet) {
        $options[$facet->id()] = $facet->label();
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
    $this->query->addField('node__localgov_directory_facets_select', 'localgov_directory_facets_select_target_id');
    $this->query->addWhere(0, 'node__localgov_directory_facets_select.localgov_directory_facets_select_target_id', $this->value, 'IN');
  }
}
