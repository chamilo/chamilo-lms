<?php

function tacker_array_map_recursive(array $array, $f) {
    $map = array();

    foreach ($array as $k => $v) {
        $map[$k] = is_array($v) ? tacker_array_map_recursive($v, $f) : call_user_func($f, $v);
    }

    return $map;
}
