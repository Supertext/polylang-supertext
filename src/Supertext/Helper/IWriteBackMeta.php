<?php

namespace Supertext\Helper;

/**
 * Interface IWriteBackMeta
 * @package Supertext\Helper
 */
interface IWriteBackMeta
{
    /**
     * Gets the order type name.
     * @return string
     */
    public function getOrderType();

    /**
     * Gets the reference hash.
     * @return string
     */
    public function getReferenceHash();

    /**
     * Gets the content meta data.
     * @return array|mixed
     */
    public function getContentMetaData();

    /**
     * returns the text for a successfull log entry.
     * @return string
     */
    public function getSuccessLogEntry();

    /**
     * Checks whether the status is in progress.
     * @return bool
     */
    public function isInProgress();

    /**
     * Marks the write back as complete.
     */
    public function markAsComplete();
}
