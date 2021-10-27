<?php

namespace Drupal\bhcc_events_plus\Plugin\views\filter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\localgov_directories\Entity\LocalgovDirectoriesFacets;
use Drupal\localgov_directories\Entity\LocalgovDirectoriesFacetsType;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
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
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $identifier = $options['expose']['identifier'];

    $exposed_input = $view->getExposedInput();

    $facet_ids = [];
    $types = $this->getFacetTypes();
    foreach($types as $id => $type) {
      if (!empty($exposed_input[$id])) {
        $facet_ids = $facet_ids + $exposed_input[$id];
      }
    }
    $exposed_input[$identifier] = $facet_ids;
    $view->setExposedInput($exposed_input);
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (!isset($this->valueOptions)) {
      $facet_types = $this->getFacetTypes();

      $options = [];
      foreach ($facet_types as $id => $label) {
        $facets = $this->getFacetsForFacetType($id);
        $options[$label] = $facets;
      }

      $this->valueOptions = $options;
    }

    return $this->valueOptions;
  }

  protected function getFacetTypes() {
    $facet_types = $this->entityTypeManager
      ->getStorage('localgov_directories_facets_type')
      ->getQuery('AND')
      ->execute();

    $types = [];
    foreach (LocalgovDirectoriesFacetsType::loadMultiple($facet_types) as $facet_type) {
      $types[$facet_type->id()] = $facet_type->label();
    }

    return $types;
  }

  protected function getFacetsForFacetType($facet_type) {
    $facets = $this->entityTypeManager
      ->getStorage('localgov_directories_facets')
      ->getQuery('AND')
      ->condition('bundle', $facet_type )
      ->execute();

    $options = [];
    foreach (LocalgovDirectoriesFacets::loadMultiple($facets) as $facet) {
      $options[$facet->id()] = $facet->label();
    }
    return $options;
  }

  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);

    $form['bhcc_facets'] = [
      '#type' => 'container',
    ];
    $types = $this->getFacetTypes();
    foreach($types as $id => $type) {
      if (!empty($form['value']['#options'][$type])) {
        $form['bhcc_facets'][$id] = [
          '#type' => 'checkboxes',
          '#title' => $type,
          '#options' => $form['value']['#options'][$type],
        ];
      }
    }

    $form['value']['#type'] = 'value';
    unset($form['value']['#options']);
  }

  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    parent::buildExposedForm($form, $form_state);
  }

  public function validate() {
    dpm('validate');
  }

  /**
   * {@inheritdoc}
   */
  protected function valueSubmit($form, FormStateInterface $form_state) {
    dpm('custom value submit');
    // $form['value']['#type'] = 'select';
    dpm(array_keys($form));
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
