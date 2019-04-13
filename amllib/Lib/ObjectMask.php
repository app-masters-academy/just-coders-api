<?php

namespace AppMasters\AmLLib\Lib;

class ObjectMask
{
    static function applyMask($array, $showMask = null, $hideMask = null)
    {
        die("HE");
        $newArray = [];

        $showAll = isset($showMask['*']);

        if (isset($showMask))
            $showMask = self::combine($showMask);

        if (isset($hideMask)) {
            // var_dump($hideMask);
            $hideMask = self::combine($hideMask);
            // var_dump($hideMask);
            // die();
        }

        // A record, is assoc > 'field' => 'value
        if (self::isAssoc($array)) {
            // We are handling one record here
            $record = $array;

            $newRecord = $record;

            // Extract array attributes
            $arrayAttributes = array_keys($record);
            foreach ($arrayAttributes as $attribute) {

                //
                // var_dump(isset($hideMask));
                // var_dump($hideMask[$attribute]);
                // echo "<hr>";

                if (isset($showMask)) {
                    if (isset($showMask[$attribute])) {
                        if (is_array($record[$attribute])) {
                            $newRecord[$attribute] = self::applyMask($record[$attribute], $showMask[$attribute]);
                            $showMask[$attribute] = $attribute;
                        }
                    } else if (!$showAll) {
                        unset($newRecord[$attribute]);
                    }
                } else if (isset($hideMask) && isset($hideMask[$attribute])) {
                    // var_dump("HEE");
                    if (is_array($record[$attribute])) {
                        // var_dump("ARRAY ".$attribute);
                        $newRecord[$attribute] = self::applyMask($record[$attribute], null, $hideMask[$attribute]);
                        $hideMask[$attribute] = $attribute;
                    } else {
                        // var_dump("UNSET ".$attribute);
                        unset($newRecord[$attribute]);
                    }
                }
            }
            $newArray = $newRecord;
        } else {
            foreach ($array as $k => $record) {
                $array[$k] = self::applyMask($record, $showMask, $hideMask);
            }
            $newArray = $array;
        }

        return $newArray;
    }


    // Move to LIB
    static function combine($array)
    {
        $nArray = [];
        foreach ($array as $k => $value) {
            if (!is_array($value))
                $nArray[$value] = $value;
            else
                $nArray[$k] = self::combine($value);
        }

        return $nArray;
    }

    // Move to LIB
    static function isAssoc(array $arr)
    {
        if (array() === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}