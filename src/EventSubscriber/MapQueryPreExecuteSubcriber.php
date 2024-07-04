<?php

namespace Drupal\bhcc_events_plus\EventSubscriber;

use Drupal\facets\FacetManager\DefaultFacetManager;
use Drupal\search_api\Event\QueryPreExecuteEvent;
use Drupal\search_api\Event\SearchApiEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Pass facets through to map like directories.
 * 
 * @see \Drupal\localgov_directories_location\EventSubscriber\SearchApiSubscriber.
 */
class MapQueryPreExecuteSubcriber implements EventSubscriberInterface {

  /**
   * Facet manager.
   *
   * @var \Drupal\facets\FacetManager\DefaultFacetManager
   */
  protected $facetManager;

  /**
   * MapQueryPreExecuteSubcriber constructor.
   *
   * @param \Drupal\facets\FacetManager\DefaultFacetManager $facet_manager
   *   The facet manager.
   */
  public function __construct(DefaultFacetManager $facet_manager) {
    $this->facetManager = $facet_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      SearchApiEvents::QUERY_PRE_EXECUTE => 'queryPreExecute',
    ];
  }

  /**
   * Reacts to the query pre-execute event.
   *
   * @param \Drupal\search_api\Event\QueryPreExecuteEvent $event
   *   The query pre-execute event.
   */
  public function queryPreExecute(QueryPreExecuteEvent $event) {
    $query = $event->getQuery();
    // While we're just dealing with one view display by id this check isn't
    // needed, but left here as harmless and generalizing the match is possibly
    // desirable in the future.
    if ($query->getIndex()->getServerInstance()->supportsFeature('search_api_facets')) {
      $search_id = $query->getSearchId();
      // This is the map to work with the list.
      if ($search_id == 'views_attachment:reccuring_events_search__attachment_1') {
        // Add the active filters from the search api view display for the list.
        $this->facetManager->alterQuery($query, 'search_api:views_page__reccuring_events_search__page_1');
      }
    }
  }

}
