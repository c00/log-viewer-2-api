<?php

namespace c00;

class Helper
{
    public static function removeEmptyValues($array)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = self::removeEmptyValues($array[$key]);
            }

            if (empty($array[$key])) {
                unset($array[$key]);
            }
        }

        return $array;
    }


}