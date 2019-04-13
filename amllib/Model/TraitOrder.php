<?php

namespace AppMasters\AmLLib\Model;

use Illuminate\Database\Eloquent\Model;

/**
 *
 * protected static $orderGroupField = 'event_id';
 * protected static $keepEntryOrder = true;
 *
 * Trait TraitOrder
 * @package AppMasters\AmLLib\Model
 */
trait TraitOrder
{

    private static $enableReOrderCollection = true;

    /**
     * Start trait
     */
    public static function bootTraitOrder()
    {
        // Check if it's disabled (when testing cruds)
        if (config("disable_trait_order")==true)
            return;

        // Listen to "deleted" event to reorder collection
        static::deleted(function ($obj) {
            // Ordering now? Just quit
            if (!self::$enableReOrderCollection)
                return;

            static::reOrderCollection($obj);
        });
        static::created(function ($obj) {
            // Ordering now? Just quit
            if (!self::$enableReOrderCollection)
                return;

            if (isset(static::$keepEntryOrder) && static::$keepEntryOrder == true) {
                // Validated on EventsManager
                self::reOrderCollectionKeepingEntryOrder($obj);
                return;
            } else {
                // Must validate on Mais Vale
                static::reOrderCollection($obj);
            }
        });
        static::updated(function (Model $obj) {
            // Not changed order.. not a problem here, just quit
            $changed = $obj->isDirty() ? $obj->getDirty() : false;
            if (!isset($changed['order']))
                return;

            // Ordering now? Just quit
            if (!self::$enableReOrderCollection)
                return;

            if (isset(static::$keepEntryOrder) && static::$keepEntryOrder == true) {
                // Validated on EventsManager
                self::reOrderCollectionKeepingEntryOrder($obj);
                return;
            } else {
                // Must validate on Mais Vale
                static::reOrderCollection($obj);
            }
        });
    }

    /**
     * Will reorder collection, maintaining the entry order value
     * @param $entry
     */
    public static function reOrderCollectionKeepingEntryOrder(Model $entry)
    {
        $class = __CLASS__;

        // 1 - Order lower entries
        $where = array();
        if (isset($class::$orderGroupField))
            array_push($where, [$class::$orderGroupField, '=', $entry[$class::$orderGroupField]]);
        array_push($where, ['order', '<=', $entry['order']]);
        if (isset($entry['id']))
            array_push($where, ['id', '<>', $entry['id']]);
        $records = static::where($where)->get()->sortBy('order');

        // reorder collection, from 1
        TraitOrder::reOrderCollectionFrom($records, 1);

        // 2 - Order higher entries
        $where = array();
        if (isset($class::$orderGroupField))
            array_push($where, [$class::$orderGroupField, '=', $entry[$class::$orderGroupField]]);
        array_push($where, ['order', '>=', $entry['order']]);
        if (isset($entry['id']))
            array_push($where, ['id', '<>', $entry['id']]);
        $records = static::where($where)->get()->sortBy('order');

        // reorder collection, from entry order position
        TraitOrder::reOrderCollectionFrom($records, $entry['order'] + 1);
    }

    /**
     * Internal method to change the record order attribute
     * @param $collection
     * @param $order
     * @param Model|null $entry
     */
    public static function reOrderCollectionFrom($collection, $order, Model &$entry = null)
    {
        foreach ($collection as $record) {
            if ($record->order !== $order) {
                $record->order = $order;
                $record->save();
            }
            if ($entry != null && $record['id'] == $entry['id'])
                $entry->order = $record['order'];
            $order++;
        }
    }

    /**
     * Rules to be used on /reoder/ endpoint
     * @return array
     */
    public function orderRules()
    {
        return [
            'entities' => 'required|array',
        ];
    }

    /**
     * Change "order" value between to instances
     *
     * Change instance "order" field value with $entry order value
     * @param $entry
     */
    function reOrder($entry)
    {
        self::$enableReOrderCollection = false;
        $oldOrder = $this->order;

        $this->order = (int)$entry->order;
        $entry->order = $oldOrder;

        $entry->save();
        $this->save();
        self::$enableReOrderCollection = true;
    }

    /**
     * Reorder all records on collection
     * - The deleted record will not be retrieved on select
     * @param $entry
     */
    static function reOrderCollection(&$entry)
    {
        $class = __CLASS__;
        if (isset($class::$orderGroupField)) {
            $groupFieldValue = $entry[$class::$orderGroupField];
            $records = static::whereRaw($class::$orderGroupField . '="' . $groupFieldValue . '"')->get()->sortBy('order');
        } else {
            $records = static::all()->sortBy('order');
        }

        $order = 1;
        foreach ($records as $record) {
            if ($record->order !== $order) {
                $record->order = $order;
                $record->save();
            }
            if ($record['id'] == $entry['id'])
                $entry->order = $record['order'];
            $order++;
        }
    }

}