<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Drupal\evanced_aggregator;

use DOMDocument;
use DOMXPath;
use Zend\Cache\Storage\StorageInterface as CacheStorage;
use Zend\Http as ZendHttp;
use Zend\Feed\Reader\Exception;
use Zend\Feed\Reader\Reader;
use Zend\Feed\Reader\Rss;
use Zend\Stdlib\ErrorHandler;

/**
*/
class EvancedReader extends Reader {

    /**
     * Trick Zend reader into thinking we are providing an is an RSS feed.
     */
    public static function detectType($feed, $specOnly = false) {
        return self::TYPE_RSS_ANY;
    }

}
