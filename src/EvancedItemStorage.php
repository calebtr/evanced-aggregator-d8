<?php

/**
 * @file
 * Contains \Drupal\aggregator\ItemStorage.
 */

namespace Drupal\evanced_aggregator;

use Drupal\aggregator\FeedInterface;
use Drupal\aggregator\ItemStorageInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Controller class for evanced aggregators items.
 *
 * This extends the Drupal\Core\Entity\Sql\SqlContentEntityStorage class, adding
 * required special handling for feed item entities.
 */
class EvancedItemStorage extends SqlContentEntityStorage implements ItemStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function getItemCount(FeedInterface $feed) {
    $query = \Drupal::entityQuery('evanced_item')
      ->condition('fid', $feed->id())
      ->count();
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function loadAll($limit = NULL) {
    $query = \Drupal::entityQuery('evanced_item');
    return $this->executeFeedItemQuery($query, $limit);
  }

  /**
   * {@inheritdoc}
   */
  public function loadByFeed($fid, $limit = NULL) {
    $query = \Drupal::entityQuery('evanced_item')
      ->condition('fid', $fid);
    return $this->executeFeedItemQuery($query, $limit);
  }

  /**
   * Helper method to execute an item query.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The query to execute.
   * @param int $limit
   *   (optional) The number of items to return.
   *
   * @return \Drupal\aggregator\ItemInterface[]
   *   An array of the feed items.
   */
  protected function executeFeedItemQuery(QueryInterface $query, $limit) {
    $query->sort('timestamp', 'DESC')
      ->sort('iid', 'DESC');
    if (!empty($limit)) {
      $query->pager($limit);
    }

    return $this->loadMultiple($query->execute());
  }

}
