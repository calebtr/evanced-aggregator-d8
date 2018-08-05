<?php
/**
 * @file
 * Contains \Drupal\node\Routing\RouteSubscriber.
 */

namespace Drupal\evanced_aggregator\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class EvancedRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // As nodes are the primary type of content, the node listing should be
    // easily available. In order to do that, override admin/content to show
    // a node listing instead of the path's child links.
    $route = $collection->get('aggregator.admin_overview');
    if ($route) {
      $route->setDefaults(array(
        '_title' => 'Evanced feed aggregator settings',
        '_controller' => '\Drupal\evanced_aggregator\Controller\EvancedAggregatorController::adminOverview',
      ));
    }
  }

}
