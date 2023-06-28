<?php

namespace Drupal\bhcc_events_plus\Plugin\views\filter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\localgov_directories\Entity\LocalgovDirectoriesFacets;
use Drupal\localgov_directories\Entity\LocalgovDirectoriesFacetsType;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Events facet filter.
 *
 * @package Drupal\bhcc_events_plus\views\filter
 *
 * @ViewsFilter("bhcc_localgov_facets")
 */
class FacetFilter extends InOperator {

  /**
   * Entity type manage service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Filter identifier.
   *
   * @var string
   */
  protected $identifier;

  /**
   * FacetFilter constructor.
   *
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param array $plugin_definition
   *   Plugin definition.
   * @param Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity Type Manager service.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
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

    // Modifiy the filter if exposed input.
    if (!empty($options['expose'])) {

      // Fetch the filter identifier and store globally.
      $identifier = $options['expose']['identifier'];
      $this->identifier = $identifier;

      $exposed_input = $view->getExposedInput();

      // Reset exposed input to the main filter.
      // @todo this should really be in massageFormValues()
      $facet_ids = [];
      $types = $this->getFacetTypes();
      foreach ($types as $id => $type) {
        if (!empty($exposed_input['facet_' . $id])) {
          $facet_ids = $facet_ids + $exposed_input['facet_' . $id];
        }
      }
      $exposed_input[$identifier] = $facet_ids;
      $view->setExposedInput($exposed_input);
    }
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

  /**
   * Get facets types that are avalible.
   *
   * Static as need to access it in callback.
   *
   * @return array
   *   Array of facets, in form 'key' => 'label'
   */
  public static function getFacetTypes() {
    $facet_types = \Drupal::entityTypeManager()
      ->getStorage('localgov_directories_facets_type')
      ->getQuery('AND')
      ->accessCheck(TRUE)
      ->execute();

    $types = [];
    foreach (LocalgovDirectoriesFacetsType::loadMultiple($facet_types) as $facet_type) {
      $types[$facet_type->id()] = $facet_type->label();
    }

    return $types;
  }

  /**
   * Get facets for facet type.
   *
   * @param string $facet_type
   *   Facet machine name.
   *
   * @return array
   *   Facet options in format [id => label];
   */
  protected function getFacetsForFacetType($facet_type): array {
    $facets = $this->entityTypeManager
      ->getStorage('localgov_directories_facets')
      ->getQuery('AND')
      ->condition('bundle', $facet_type)
      ->accessCheck(TRUE)
      ->execute();

    $options = [];
    foreach (LocalgovDirectoriesFacets::loadMultiple($facets) as $facet) {
      $options[$facet->id()] = $facet->label();
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);

    $form['bhcc_facets'] = [
      '#weight' => 99,
      '#theme' => 'bhcc_events_facets',
      '#type' => 'container',
      '#attributes' => [
        'class' => ['facet-filter'],
      ],
      // Pass through the idenitifier.
      '#identifier' => $this->identifier,
    ];
    $types = $this->getFacetTypes();
    foreach ($types as $id => $type) {
      if (!empty($form['value']['#options'][$type])) {
        $form['bhcc_facets']['facet_' . $id] = [
          '#type' => 'checkboxes',
          '#title' => $type,
          '#options' => $form['value']['#options'][$type],
        ];
      }
    }

    // Set the element itself to a value element so it does not render.
    $form['value']['#type'] = 'value';
    $form['value']['#value'] = [];
    unset($form['value']['#options']);

    // Add a custom submit handler to set the facet values.
    $form['#submit'][] = [
      'Drupal\bhcc_events_plus\Plugin\views\filter\FacetFilter',
      'facetSubmitHandler',
    ];
  }

  /**
   * Facet Submit Handler.
   *
   * Combine the exposed facet values into a single value array for the filter.
   * (Like massageFormValues).
   *
   * @param array $form
   *   Form array.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public static function facetSubmitHandler(array $form, FormStateInterface $form_state) {
    $types = self::getFacetTypes();
    $options = [];
    foreach ($types as $id => $type) {
      $facet_values = $form_state->getValue('facet_' . $id);
      if (is_array($facet_values)) {
        $options = array_merge($options, $facet_values);
      }
    }

    // Retrive the identifier.
    $identifier = $form['bhcc_facets']['#identifier'] ?? NULL;
    $form_state->setValue($identifier, $options);
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
