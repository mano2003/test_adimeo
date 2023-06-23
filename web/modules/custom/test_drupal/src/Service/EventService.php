<?php

namespace Drupal\test_drupal\Service;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Event Service.
 */
class EventService {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * EventService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    DateFormatterInterface $date_formatter
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * Format a date using the date formatter service.
   *
   * @param int $timestamp
   *   The timestamp to format.
   *
   * @return string
   *   The formatted date.
   */
  public function formatDate($timestamp): string {
    return $this->dateFormatter->format($timestamp, 'custom', 'Y-m-d');
  }

  /**
   * Gets the related events.
   *
   * @param \Drupal\Core\Entity\EntityInterface $current_event
   *   The current event entity.
   *
   * @return array An array of related event entities.
   *   An array of related event entities.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getRelatedEvents(EntityInterface $current_event): array {
    $limit = 3;

    $related_events = [];
    $rows = [];

    // Get the event type (taxonomy term).
    $event_type = $current_event->field_event_type->entity;

    // Get the current date.
    $datetime = new DrupalDateTime();

    if (!$event_type) {
      return $rows;
    }

    // Query for related events.
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'event')
      ->condition('nid', $current_event->id(), '<>')
      ->condition('field_event_type.target_id', $event_type->id())
      ->condition('field_date_range.value', $datetime, '>=')
      ->sort('field_date_range.value', 'DESC')
      ->range(0, $limit)
      ->accessCheck(TRUE);

    // Get the related events.
    $related_event_ids = $query->execute();

    // Load the related event entities.
    if (!empty($related_event_ids)) {
      $related_events = $this->entityTypeManager
        ->getStorage('node')
        ->loadMultiple($related_event_ids);
    }

    // If there are fewer than the required number of related events,
    // query events of other types.
    $related_events_count = count($related_events);
    if ($related_events_count < $limit) {
      $remaining_limit = $limit - $related_events_count;
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'event')
        ->condition('nid', $current_event->id(), '<>')
        ->condition('field_event_type.target_id', $event_type->id())
        ->condition('field_date_range.value', $datetime, '>=')
        ->sort('field_date_range.value', 'DESC')
        ->range(0, $remaining_limit)
        ->accessCheck(TRUE);

      $remaining_event_ids = $query->execute();

      // Load the remaining related event entities.
      if (!empty($remaining_event_ids)) {
        $remaining_related_events = $this->entityTypeManager
          ->getStorage('node')
          ->loadMultiple($remaining_event_ids);

        $related_events = array_merge($related_events, $remaining_related_events);
      }

      // Query for additional events of other types.
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'event')
        ->condition('field_event_type.target_id', $event_type->id(), '<>')
        ->condition('field_date_range.value', $datetime, '>=')
        ->sort('field_date_range.value', 'DESC')
        ->range(0, $limit - $related_events_count)
        ->accessCheck(TRUE);

      // Get additional events of other types.
      $additional_event_ids = $query->execute();

      if (!empty($additional_event_ids)) {
        $additional_events = $this->entityTypeManager
          ->getStorage('node')
          ->loadMultiple($additional_event_ids);
        $related_events = array_merge($related_events, $additional_events);
      }

      // Filter out events with a date range end in the past.
      foreach ($related_events as $key => $event) {
        $start_date = $event->get('field_date_range')->value;
        $end_date = $event->get('field_date_range')->end_value;
        $event_type = $event->field_event_type->entity->get('name')->value;
        if (!empty($end_date)) {
          $end_date_datetime = new DrupalDateTime($end_date);
          if ($end_date_datetime->format('U') < $datetime->format('U')) {
            unset($related_events[$key]);
          }
          $rows[] = [
            'title' => $event->get('title')->value,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'event_type' => $event_type,
          ];
        }
      }
    }

    return $rows;
  }

}
