<?php

declare(strict_types=1);

namespace Drupal\bhcc_events_plus\EventSubscriber;

use Drupal\search_api\Event\QueryPreExecuteEvent;
use Drupal\search_api\Event\SearchApiEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Localgov events search api query pre execute event subcriber.
 */
final class QueryPreExecuteSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      SearchApiEvents::QUERY_PRE_EXECUTE => ['preExecuteQuery'],
    ];
  }

  /**
   * Reacts to the pre execute query event.
   *
   * @param Drupal\search_api\Event\QueryPreExecuteEvent $event
   *   The pre execute query event.
   */
  public function preExecuteQuery(QueryPreExecuteEvent $event) {

    // Only apply to the reccuring_events search index.
    $query = $event->getQuery();
    $index = $query->getIndex();
    $index_id = $index->id();
    if ($index_id != 'recurring_events') {
      return;
    }

    // Modify conditions.
    $condition_group = $query->getConditionGroup();
    $conditions = $condition_group->getConditions();
    $found_start_date_condition = FALSE;
    foreach ($conditions as $condition) {
      $sub_conditions = $condition->getConditions();
      foreach ($sub_conditions as $sub_condition) {
        $field = $sub_condition->getField();

        // Check if a start date is present.
        if ($field == 'localgov_event_date_occurrence') {
          $found_start_date_condition = TRUE;
        }

        // Increase the end date value to end of day.
        // @See https://brighton-hove-digital.atlassian.net/browse/WEB-282
        if ($field == 'end_value') {
          $end_value = $sub_condition->getValue();
          $end_value += 86400;
          $sub_condition->setValue($end_value);
        }
      }
    }

    // If no start date, set it to today so default is todays events.
    if (!$found_start_date_condition) {
      $condition_group->addCondition('localgov_event_date_occurrence', strtotime('midnight'), '>=');
    }

    // Remove offset and limit in the query to get all results.
    // This is then filtered in the results subscriber.
    // @todo replace this with 'grouped' query.
    $query->setOption('offset', NULL);
    $query->setOption('limit', NULL);
  }

}
