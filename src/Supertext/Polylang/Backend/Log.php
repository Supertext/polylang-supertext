<?php

namespace Supertext\Polylang\Backend;

/**
 * The log handler
 * @package Supertext\Polylang\Backend
 * @author Michael Sebel <michael@comotive.ch>
 */
class Log
{
  /**
   * @var string The polylang sueprtext log entry meta name
   */
  const META_LOG = 'polylang-supertext-log';
  /**
   * @var string The polylang sueprtext log entry meta name
   */
  const META_ORDER_ID = 'polylang-supertext-order-id';

  /**
   * @param int $postId post that needs a new entry
   * @param string $message the message to be logged
   * @return bool true, if the log entry was placed
   */
  public function addEntry($postId, $message)
  {
    // Only do something if likely to be valid post id
    if (intval($postId) == 0) {
      return false;
    }

    $entries = $this->getLogEntries($postId);

    // Add the new at the end of the array
    $entries[] = array(
      'message' => $message,
      'datetime' => current_time('timestamp')
    );

    // Save back to metadata
    update_post_meta($postId, self::META_LOG, $entries);
    return true;
  }

  /**
   * Adds an order id to the article (Can be multiple)
   * @param int $postId the post to be assigned to an order
   * @param string|int $orderId order id at supertext
   */
  public function addOrderId($postId, $orderId)
  {
    $orderIds = get_post_meta($postId, self::META_ORDER_ID, true);
    if (!is_array($orderIds)) $orderIds = array();
    $orderIds[] = $orderId;
    update_post_meta($postId, self::META_ORDER_ID, $orderIds);
  }

  /**
   * @param int $postId
   * @return int $orderId
   */
  public function getLastOrderId($postId)
  {
    $orderIdList = get_post_meta($postId, Log::META_ORDER_ID, true);
    $orderId = is_array($orderIdList) ? end($orderIdList) : 0;

    return intval($orderId);
  }

  /**
   * @param int $postId the post whose entries need to be received
   * @return array list of log entries for the post
   */
  public function getLogEntries($postId)
  {
    // Get current log entries and force result to be an array
    $entries = get_post_meta($postId, self::META_LOG, true);
    if (!is_array($entries)) $entries = array();
    return $entries;
  }
}