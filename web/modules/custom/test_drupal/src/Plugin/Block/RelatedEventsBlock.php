<?php

namespace Drupal\test_drupal\Plugin\Block;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\test_drupal\Service\EventService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a 'Related Events' block.
 *
 * @Block(
 *   id = "related_events_block",
 *   admin_label = @Translation("Related Events Block"),
 *   category = @Translation("Custom"),
 * )
 */
class RelatedEventsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The event service.
   *
   * @var \Drupal\test_drupal\Service\EventService
   */
  protected $eventService;

  /**
   * The \Drupal\Core\Logger\LoggerChannelChannelFactoryInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * RelatedEventsBlock constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\test_drupal\Service\EventService $event_service
   *   The event service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   * The logger factory.
   *
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EventService $event_service,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->eventService = $event_service;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('test_drupal.event_service'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $related_events = [];

    // Get the current event entity.
    $event = \Drupal::routeMatch()->getParameter('node');
    if ($event instanceof EntityInterface && $event->bundle() === 'event') {
      // Get related events.
      try {
        $related_events = $this->eventService->getRelatedEvents($event);
      }
      catch (InvalidPluginDefinitionException|PluginNotFoundException $e) {
        $this->loggerFactory->get('test_drupal')->error($e->getMessage());
      }

      // Prepare the render array for related events.
      $build = [
        '#theme' => 'related_events_block',
        '#event' => $event,
        '#related_events' => $related_events,
      ];
    }

    return $build;
  }

}
