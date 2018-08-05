<?php

/**
 * @file
 * Contains \Drupal\aggregator\Plugin\aggregator\parser\DefaultParser.
 */

namespace Drupal\evanced_aggregator\Plugin\aggregator\parser;

use Drupal\aggregator\Plugin\ParserInterface;
use Drupal\aggregator\FeedInterface;
use Drupal\aggregator\Plugin\aggregator\parser\DefaultParser;
use Drupal\evanced_aggregator\EvancedReader;
use Zend\Feed\Reader\Exception;
use Zend\Feed\Reader\Exception\ExceptionInterface;


/**
 * Defines the Evanced aggregator parser implementation.
 *
 * Parses RSS, Atom and RDF feeds.
 *
 * @AggregatorParser(
 *   id = "evanced_aggregator",
 *   title = @Translation("Evanced parser"),
 *   description = @Translation("Evanced parser for RSS, Atom and RDF feeds.")
 * )
 */
class EvancedParser extends DefaultParser implements ParserInterface {

  /**
   * {@inheritdoc}
   */
  public function parse(FeedInterface $feed) {

    // Set our bridge extension manager to Zend Feed.
    EvancedReader::setExtensionManager(\Drupal::service('feed.bridge.reader'));
    try {
      $channel = EvancedReader::importString($feed->source_string);
    }
    catch (Zend\Feed\Reader\Exception\ExceptionInterface $e) {
      watchdog_exception('aggregator', $e);
      drupal_set_message(t('The feed from %site seems to be broken because of error "%error".', array('%site' => $feed->label(), '%error' => $e->getMessage())), 'error');

      return FALSE;
    }

    $feed->setWebsiteUrl($channel->getLink());
    $feed->setDescription($channel->getDescription());
    if ($image = $channel->getImage()) {
      $feed->setImage($image['uri']);
    }
    // Initialize items array.
    $feed->items = array();
    foreach ($channel as $item) {

      // Reset the parsed item.
      $parsed_item = array();

      // load the xml into a DomDocument object so we can parse it
      $dom = new \DomDocument();
      $result = $dom->loadXML($item->saveXML());

      // skip items if they aren't valid xml
      if (!$result) {
        continue;
      }


      // merge together all the values from the category fields
      $categories = array_filter(array_unique(array_merge(
          explode(',', $dom->getElementsByTagName('prieventtype')->item(0)->nodeValue),
          explode(',', $dom->getElementsByTagName('eventtypes')->item(0)->nodeValue),
          explode(',', $dom->getElementsByTagName('eventtype1')->item(0)->nodeValue),
          explode(',', $dom->getElementsByTagName('eventtype2')->item(0)->nodeValue),
          explode(',', $dom->getElementsByTagName('eventtype3')->item(0)->nodeValue),
          explode(',', $dom->getElementsByTagName('agegroups')->item(0)->nodeValue),
          explode(',', $dom->getElementsByTagName('agegroup1')->item(0)->nodeValue),
          explode(',', $dom->getElementsByTagName('agegroup2')->item(0)->nodeValue),
          explode(',', $dom->getElementsByTagName('agegroup3')->item(0)->nodeValue)
      )), function($v) { return trim($v); });


      // skip this item if one of the categories is "Staff development/meeting"
      if (in_array('Staff development/meeting', $categories)) {
        continue;
      }
      $parsed_item['eventtype'] = implode(' ', $categories);

      // Move the values to an array as expected by processors.
      $parsed_item['title'] = $dom->getElementsByTagName('title')->item(0)->nodeValue;
      $parsed_item['guid'] = $dom->getElementsByTagName('link')->item(0)->nodeValue;
      $parsed_item['link'] = $dom->getElementsByTagName('link')->item(0)->nodeValue;

      // Get the date and time
      $date = $dom->getElementsByTagName('date')->item(0)->nodeValue;
      $startTime = $dom->getElementsByTagName('time')->item(0)->nodeValue;
      $endTime = $dom->getElementsByTagName('endtime')->item(0)->nodeValue;

      // process the date & overwrite timestamp
      $whenStamp = strtotime($date . ' ' . $startTime);
      $parsed_item['timestamp'] = $whenStamp;

      // strip leading spaces and 0, and trailing AM or PM from start time and end time
      $startTime = trim(ltrim(substr($startTime, 0, 5), 0));
      $endTime = trim(ltrim(substr($endTime, 0, 5), 0));

      // $when properties
      $when = new \stdClass();
      $when->dayofweek = substr(date('l', $whenStamp), 0, 3);
      $when->month = date('M', $whenStamp);
      $when->day = date('j', $whenStamp);
      $when->time = $startTime . ' - ' . $endTime;

      $evancedItem = array(
        '#theme' => 'evanced_item',
        '#when' => $when,
        '#title' => $parsed_item['title'],
        '#link' => $parsed_item['link'],
      );

      // set the description to a render array and render it
      $parsed_item['description'] = \Drupal::service('renderer')->renderRoot($evancedItem);
      $parsed_item['author'] = '';
      if ($author = $item->getAuthor()) {
        $parsed_item['author'] = $author['name'];
      }
      // Store on $feed object. This is where processors will look for parsed items.
      $feed->items[] = $parsed_item;
    }

    return TRUE;
  }

}
