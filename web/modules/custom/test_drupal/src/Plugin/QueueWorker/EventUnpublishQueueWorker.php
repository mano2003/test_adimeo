<?php

  namespace Drupal\test_drupal\Plugin\QueueWorker;

  use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
  use Drupal\Core\Queue\QueueWorkerBase;
  use Drupal\Core\Entity\EntityTypeManagerInterface;
  use Drupal\node\Entity\Node;
  use Symfony\Component\DependencyInjection\ContainerInterface;

  /**
   * A queue worker for unpublishing past events.
   *
   * @QueueWorker(
   *   id = "event_unpublish_queue",
   *   title = @Translation("Event Unpublish Queue"),
   *   cron = {"time" = 43200} // Runs every day at 12 AM (12 * 60 * 60 seconds)
   * )
   */
  class EventUnpublishQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

    /**
     * The entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * Constructs a new EventUnpublishQueueWorker instance.
     *
     * @param array $configuration
     *   A configuration array containing information about the plugin instance.
     * @param string $plugin_id
     *   The plugin_id for the plugin instance.
     * @param mixed $plugin_definition
     *   The plugin implementation definition.
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
     *   The entity type manager.
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
    public function processItem($data) {
      $nid = $data['nid'];
      $node = Node::load($nid);
      if ($node instanceof Node && $node->getType() === 'event') {
        $end_date = new \DateTime($node->field_date_range->end_value);
        $current_date = new \DateTime();
        if ($end_date <= $current_date) {
          // Unpublish the node.
          $node->set('status', 0)->save();
        }
      }
    }

  }
