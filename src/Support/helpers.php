<?php

if (!function_exists('size_for_humans')) {
    function size_for_humans($size) {
        $size = intval($size);
        $unit = '';
        $units = ['kB', 'MB', 'GB', 'TB'];
        do {
            $size /= 1000;
            $unit = array_shift($units);
        } while ($size > 1000 && count($units) > 0);

        return round($size, 2).'&nbsp;'.$unit;
    }
}
