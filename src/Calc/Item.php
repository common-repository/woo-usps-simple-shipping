<?php declare(strict_types=1);
namespace Dgm\UspsSimple\Calc;


class Item
{
    /**
     * @var Product
     * @psalm-readonly
     */
    public $product;

    /**
     * @var int|float >=0
     * @psalm-readonly 
     */
    public $quantity;

    
    /**
     * @param int|float $quantity
     */
    public function __construct(Product $product, $quantity)
    {
        $this->product = $product;
        $this->quantity = Number::intOrFloat($quantity);
    }
}