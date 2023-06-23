<?php

namespace Drupal\test_drupal\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for Test Drupal routes.
 */
class TestDrupalController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

}
