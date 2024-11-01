<?php declare(strict_types=1);

namespace Dgm\UspsSimple\Calc;


class Product
{
    /**
     * Weight in lbs
     * @var int|float >=0
     * @psalm-readonly
     */
    public $weight;

    /**
     * Dimensions in inches
     * @var Dim
     * @psalm-readonly
     */
    public $dim;


    public static function fromWcProduct(\WC_Product $p): self
    {
        $weight = Number::intOrFloat(wc_get_weight($p->get_weight(), 'lbs'));

        $dim = new Dim(
            wc_get_dimension(Number::intOrFloat($p->get_length()), 'in'),
            wc_get_dimension(Number::intOrFloat($p->get_width()), 'in'),
            wc_get_dimension(Number::intOrFloat($p->get_height()), 'in')
        );

        return new self($weight, $dim);
    }

    public function __construct($weight, Dim $dim)
    {
        $this->weight = $weight;
        $this->dim = $dim;
    }
}