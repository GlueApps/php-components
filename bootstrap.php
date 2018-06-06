<?php

require_once 'vendor/autoload.php';

/**
 * Generate a random float value.
 *
 * @param  integer $min
 * @param  integer $max
 * @return float
 */
function frand($min = 0, $max = 10)
{
    return ($min + lcg_value() * (abs($max - $min)));
}
