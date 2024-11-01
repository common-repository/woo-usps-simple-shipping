<?php declare(strict_types=1);
namespace Dgm\UspsSimple\Calc;


class Number
{
    /**
     * @return int|float
     */
    public static function intOrFloat($x, bool $negative = false)
    {
        if (!is_int($x)) {
            $x = (float)$x;
        }
        if ($x === 0.0) {
            $x = 0;
        }
        if (!$negative && $x < 0) {
            $x = 0;
        }
        return $x;
    }
}