<?php declare(strict_types=1);
namespace Dgm\UspsSimple\Calc;


class Dim
{
    /**
     * @var Dim
     * @psalm-readonly
     */
    public static $ZERO;

    /**
     * @var int|float
     * @psalm-readonly
     */
    public $length;

    /**
     * @var int|float
     * @psalm-readonly
     */
    public $width;

    /**
     * @var int|float
     * @psalm-readonly
     */
    public $height;


    /**
     * @param int|float $d1
     * @param int|float $d2
     * @param int|float $d3
     * @return self
     */
    public static function of($d1, $d2, $d3): self
    {
        return new self($d1, $d2, $d3);
    }

    /**
     * @param int|float $d1
     * @param int|float $d2
     * @param int|float $d3
     */
    public function __construct($d1, $d2, $d3)
    {
        $args = array_map([Number::class, 'intOrFloat'], func_get_args());
        sort($args);
        $this->length = $args[2];
        $this->width = $args[1];
        $this->height = $args[0];
    }

    /**
     * @return int|float
     */
    public function max()
    {
        return $this->length;
    }

    /**
     * @return int|float
     */
    public function min()
    {
        return $this->height;
    }

    /**
     * @return int|float
     */
    public function girth()
    {
        return ($this->width + $this->height) * 2;
    }

    public function fits(Dim $other): bool
    {
        return
            $this->length >= $other->length &&
            $this->width  >= $other->width  &&
            $this->height >= $other->height;
    }

    public function isZero(): bool
    {
        /** @noinspection TypeUnsafeComparisonInspection */
        return $this->max() == 0;
    }
}

Dim::$ZERO = new Dim(0,0,0);