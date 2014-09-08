<?php

/*
 * This file is part of the AntiMattr MongoDB Migrations Library, a library by Matthew Fitzgerald.
 *
 * (c) 2014 Matthew Fitzgerald
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AntiMattr\MongoDB\Migrations\Collection;

use Doctrine\MongoDB\Collection;
use Exception;

/**
 * @author Matthew Fitzgerald <matthewfitz@gmail.com>
 */
class Statistics
{
    const COUNT = "count";
    const SIZE = "size";
    const AVG_OBJ_SIZE = "avgObjSize";
    const STORAGE_SIZE = "storageSize";
    const NUM_EXTENTS = "numExtents";
    const NINDEXES = "nindexes";
    const LAST_EXTENT_SIZE = "lastExtentSize";
    const PADDING_FACTOR = "paddingFactor";
    const TOTAL_INDEX_SIZE = "totalIndexSize";

    static public $metrics = array(
        self::COUNT,
        self::SIZE,
        self::AVG_OBJ_SIZE,
        self::STORAGE_SIZE,
        self::NUM_EXTENTS,
        self::NINDEXES,
        self::LAST_EXTENT_SIZE,
        self::PADDING_FACTOR,
        self::TOTAL_INDEX_SIZE
    );

    /**
     * @var Doctrine\MongoDB\Collection
     */
    private $collection;

    /**
     * @var array
     */
    private $before = array();

    /**
     * @var array
     */
    private $after = array();

    /**
     * @param Doctrine\MongoDB\Collection
     */
    public function setCollection(Collection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * @return Doctrine\MongoDB\Collection
     */
    public function getCollection()
    {
        return $this->collection;
    }

    public function updateBefore()
    {
        $data = $this->getCollectionStats();
        foreach ($data as $key => $value) {
            if (in_array($key, static::$metrics)) {
                $this->before[$key] = $value;
            }
        }
    }

    /**
     * @return array
     */
    public function getBefore()
    {
        return $this->before;
    }

    public function updateAfter()
    {
        $data = $this->getCollectionStats();
        foreach ($data as $key => $value) {
            if (in_array($key, static::$metrics)) {
                $this->after[$key] = $value;
            }
        }
    }

    /**
     * @return array
     */
    public function getAfter()
    {
        return $this->after;
    }

    /**
     * @return array
     *
     * @throws \Exception
     */
    protected function getCollectionStats()
    {
        $database = $this->collection->getDatabase();
        $name = $this->collection->getName();

        try {
            if (!$data = $database->command(array('collStats' => $name))) {
                $message = sprintf(
                    "Statistics not found for collection %s",
                    $name
                );
                throw new Exception($message);
            }
            if (isset($data['errmsg'])) {
                throw new Exception($data['errmsg']);
            }

            return $data;
        } catch (Exception $e) {
            throw $e;
        }

        return array();
    }
}
