<?php

/* For licensing terms, see /license.txt */

/**
 * This is the array library for Chamilo.

 * Removes duplicate values from a dimensional array.
 *
 * @param array|mixed $array dimensional array
 *
 * @return array an array with unique values
 */
function array_unique_dimensional($array)
{
    if (!is_array($array)) {
        return $array;
    }

    foreach ($array as &$myvalue) {
        $myvalue = serialize($myvalue);
    }

    $array = array_unique($array);

    foreach ($array as &$myvalue) {
        $myvalue = UnserializeApi::unserialize('not_allowed_clases', $myvalue);
    }

    return $array;
}

/**
 * Sort multidimensional arrays (HTML tags of the values are ignored). The sort is stable.
 *
 * @param array  $array unsorted multidimensional array
 * @param string $id    key to be sorted
 * @param string $order 'asc' (lowest first, default) or 'desc' (highest first), case-insensitive
 *
 * @return array result array, reindexed
 *
 * @author    found in http://php.net/manual/en/function.sort.php
 */
function msort($array, $id = 'id', $order = 'asc')
{
    if (empty($array)) {
        return $array;
    }
    $direction = 'desc' === strtolower((string) $order) ? -1 : 1;
    $array = array_values($array);
    usort(
        $array,
        static fn ($a, $b): int => $direction * (strip_tags((string) ($a[$id] ?? '')) <=> strip_tags((string) ($b[$id] ?? '')))
    );

    return $array;
}

/**
 * @param $array
 *
 * @return mixed
 */
function utf8_sort($array)
{
    $old_locale = setlocale(LC_ALL, 0);
    $code = api_get_language_isocode();
    $locale_list = [$code.'.utf8', 'en.utf8', 'en_US.utf8', 'en_GB.utf8'];
    $try_sort = false;

    foreach ($locale_list as $locale) {
        $my_local = setlocale(LC_COLLATE, $locale);
        if ($my_local) {
            $try_sort = true;
            break;
        }
    }

    if ($try_sort) {
        uasort($array, 'strcoll');
    }
    setlocale(LC_COLLATE, $old_locale);

    return $array;
}

/**
 * @param array  $array
 * @param string $separator
 *
 * @return string
 */
function array_to_string($array, $separator = ',')
{
    if (empty($array)) {
        return '';
    }

    return implode($separator.' ', $array);
}

/**
 * @return array
 */
function array_flatten(array $array)
{
    $flatten = [];
    array_walk_recursive(
        $array,
        function ($value) use (&$flatten) {
            $flatten[] = $value;
        }
    );

    return $flatten;
}

/**
 * Shuffles an array keeping the associations.
 *
 * @param $array
 *
 * @return bool
 */
function shuffle_assoc(&$array)
{
    $keys = array_keys($array);
    shuffle($keys);
    $new = [];
    foreach ($keys as $key) {
        $new[$key] = $array[$key];
    }
    $array = $new;

    return true;
}
