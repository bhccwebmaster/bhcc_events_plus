<?php

declare(strict_types=1);

namespace Drupal\bhcc_events_plus\EventSubscriber;

use Drupal\search_api\Event\ProcessingResultsEvent;
use Drupal\search_api\Event\SearchApiEvents;
use Drupal\search_api\Utility\Utility;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Localgov events search api results event subscriber.
 */
final class ResultsSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      SearchApiEvents::PROCESSING_RESULTS => ['processResults'],
    ];
  }

  /**
   * Reacts to the process results event.
   *
   * @param Drupal\search_api\Event\ProcessingResultsEvent $event
   *   The processing results event.
   */
  public function processResults(ProcessingResultsEvent $event) {

    // Get results.
    $results = $event->getResults();

    // Only apply to the localgov_events search index.
    $query = $results->getQuery();
    $index = $query->getIndex();
    $index_id = $index->id();
    if ($index_id != 'recurring_events') {
      return;
    }

    // Get result items.
    $result_items = $results->getResultItems();

    // Filter results so only unique events.
    // @todo replace this with group query.
    $nids = [];
    $filtered_results = [];
    foreach ($result_items as $result_item) {

      // Use the item id and split the property path to get the NID.
      // This should be faster than loading up each result, though might not
      // be always accurate as it's down to how search items are assigned id
      // during indexing.
      // If it's required to get the original node id, this can be loaded with.
      // @code $nid = $result_item->getOriginalObject()->getEntity()->id();
      [, $source_id] = Utility::splitCombinedId($result_item->getId());
      [$nid] = Utility::splitPropertyPath($source_id, FALSE);
      if (!in_array($nid, $nids)) {
        $nids[] = $nid;
        $filtered_results[] = $result_item;
      }
    }

    // Set results count to the number of filtered results.
    $results->setResultCount(count($filtered_results));

    // Slice the results to the original offset and limit.
    $offset = $query->getOriginalQuery()->getOption('offset');
    $limit = $query->getOriginalQuery()->getOption('limit');
    if (is_int($offset) && is_int($limit)) {
      $filtered_results = array_slice($filtered_results, $offset, $limit);
    }

    // Write back results with the filtered version.
    $results->setResultItems($filtered_results);
  }

}
