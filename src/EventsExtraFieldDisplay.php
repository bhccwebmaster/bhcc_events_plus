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
use Drupal\Core\Form\FormState;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
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
    $fields['node']['event_channel']['display']['bhcc_events_view'] = [
      'label' => $this->t('Events listing'),
      'description' => $this->t("Output from the embedded view for this channel."),
      'weight' => -20,
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
    if ($display->getComponent('bhcc_events_view')) {
      $build['bhcc_events_view'] = $this->getViewEmbed($node);
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
