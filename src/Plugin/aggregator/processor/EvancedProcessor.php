<?php

/**
 * @file
 * Contains \Drupal\evanced_aggregator\Plugin\aggregator\processor\EvancedProcessor.
 */

namespace Drupal\evanced_aggregator\Plugin\aggregator\processor;

use Drupal\aggregator\Plugin\ProcessorInterface;
use Drupal\aggregator\Plugin\aggregator\processor\DefaultProcessor;
use Drupal\aggregator\FeedInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\ConfigFormBaseTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a default processor implementation.
 *
 * Creates lightweight records from feed items.
 *
 * @AggregatorProcessor(
 *   id = "evanced_processor",
 *   title = @Translation("Evanced processor"),
 *   description = @Translation("Creates Evanced records from feed items.")
 * )
 */
class EvancedProcessor extends DefaultProcessor implements ProcessorInterface, ContainerFactoryPluginInterface {
  use ConfigFormBaseTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('entity.manager')->getStorage('evanced_item'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process(FeedInterface $feed) {
    if (!is_array($feed->items)) {
      return;
    }
    foreach ($feed->items as $item) {
      // @todo: The default entity view builder always returns an empty
      //   array, which is ignored in aggregator_save_item() currently. Should
      //   probably be fixed.
      if (empty($item['title'])) {
        continue;
      }

      // Save this item. Try to avoid duplicate entries as much as possible. If
      // we find a duplicate entry, we resolve it and pass along its ID is such
      // that we can update it if needed.
      if (!empty($item['guid'])) {
        $values = array('fid' => $feed->id(), 'guid' => $item['guid']);
      }
      elseif ($item['link'] && $item['link'] != $feed->link && $item['link'] != $feed->url) {
        $values = array('fid' => $feed->id(), 'link' => $item['link']);
      }
      else {
        $values = array('fid' => $feed->id(), 'title' => $item['title']);
      }

      // Try to load an existing entry.
      if ($entry = entity_load_multiple_by_properties('evanced_item', $values)) {
        $entry = reset($entry);
      }
      else {
        $entry = entity_create('evanced_item', array('langcode' => $feed->language()->getId()));
      }
      if ($item['timestamp']) {
        $entry->setPostedTime($item['timestamp']);
      }

      // Make sure the item title and author fit in the 255 varchar column.
      $entry->setTitle(Unicode::truncate($item['title'], 255, TRUE, TRUE));
      $entry->setAuthor(Unicode::truncate($item['author'], 255, TRUE, TRUE));

      $entry->setFeedId($feed->id());
      $entry->setLink($item['link']);
      $entry->setGuid($item['guid']);
      $entry->setEventType($item['eventtype']);

      $description = '';
      if (!empty($item['description'])) {
        $description = $item['description'];
      }
      $entry->setDescription($description);

      $entry->save();
    }
  }

}
