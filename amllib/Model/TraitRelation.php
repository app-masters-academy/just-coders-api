<?php

namespace AppMasters\AmLLib\Model;

trait TraitRelation
{
    static $handleRelations = [];
    static $relationAttributes = [];

    public static function removeRelationAttributes($attributes)
    {
        $class = __CLASS__;

        if (!isset($class::$handleRelationPost))
            return $attributes;

        // Store relations here

        self::$handleRelations[$class] = $class::$handleRelationPost;
        self::$relationAttributes[$class] = $attributes;

        // Remove relation attributes to allow save model
        $attributesWithoutRelations = $attributes;
        foreach (static::$handleRelationPost as $relation) {
            unset($attributesWithoutRelations[$relation]);
        }
        return $attributesWithoutRelations;
    }

    /**
     * Save relations to database
     * @todo DOCREVIEW
     */
    public function attachRelations()
    {
        $class = __CLASS__;

        if (!isset($class::$handleRelationPost))
            return;

        $attributes = self::$relationAttributes[$class];

        // Move to CERTIFY
        // Attach ids from original $attributes
        foreach (self::$handleRelations[$class] as $relation) {
            if (isset($attributes[$relation])) {
                $ids = $attributes[$relation];
                if ($ids !== null) {
                    $ids = self::parseIds($ids);
                    foreach ($ids as $id) {
                        $this->$relation()->attach($id);
                    }
                }
            }
        }
    }

    /**
     * @todo DOCREVIEW
     * @param $record
     * @return mixed
     */
    public function removeHiddenRelationAttributes($record)
    {
        // Must hidden relations fields?
        if (isset($this->hiddenRelation) && count($this->hiddenRelation) > 0) {
            foreach ($this->hiddenRelation as $relation => $hiddenFields) {
                if (isset($record[$relation]) && is_array($record[$relation])) {

                    if (!$this->isAssoc($record[$relation])) {
                        foreach ($record[$relation] as $key => $relationRecord) {
                            foreach ($hiddenFields as $hidden)
                                unset($relationRecord[$hidden]);
                            $record[$relation][$key] = $relationRecord;
                        }
                    } else {
                        foreach ($hiddenFields as $hidden) {
                            if (array_key_exists($hidden,$record[$relation]))
                                unset($record[$relation][$hidden]);
                        }
                    }
                }
            }
        }

        return $record;
    }


    /**
     * @todo DOCREVIEW
     * @param $record
     * @return mixed
     */
    public function visibleRelationAttributes($record)
    {
        if (isset($this->visibleRelation) && count($this->visibleRelation) > 0) {
            foreach ($this->visibleRelation as $relation => $visibleFields) {
                if (isset($record[$relation])) {
                    if (!$this->isAssoc($record[$relation])) {
                        foreach ($record[$relation] as $key => $relationRecord) {
                            $newRecord = [];
                            foreach ($visibleFields as $visible) {
                                if (isset($relationRecord[$visible]))
                                    $newRecord[$visible] = $relationRecord[$visible];
                            }
                            $record[$relation][$key] = $newRecord;
                        }
                    } else {
                        $newRecord = [];
                        foreach ($visibleFields as $visible) {
                            if (isset($record[$relation][$visible]))
                                $newRecord[$visible] = $record[$relation][$visible];
                        }
                        $record[$relation] = $newRecord;
                    }
                }
            }
        }
        return $record;
    }


    /**
     * @param array $arr
     * @return bool
     */
    private function isAssoc(array $arr)
    {
        if (array() === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

}