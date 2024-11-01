<?php declare(strict_types=1);

namespace Dgm\UspsSimple\Calc;


class Package
{
    /**
     * @var Area
     */
    public $orig;

    /**
     * @var Area
     */
    public $dest;

    /**
     * Only shippable items
     * @var array<string, Item>
     */
    public $items;


    public static function fromWcPackage(array $p, Area $sender): Package
    {
        $pd = $p['destination'];
        $dest = new Area($pd['country'], $pd['postcode']);

        $items = [];
        foreach ($p['contents'] as $id => $item) {

            /** @var \WC_Product $pr */
            $pr = $item['data'];
            if (!$pr->needs_shipping()) {
                continue;
            }

            $items[$id] = new Item(Product::fromWcProduct($pr), $item['quantity']);
        }

        return new Package($sender, $dest, $items);
    }

    public function __construct(Area $orig, Area $dest, array $items)
    {
        $this->orig = $orig;
        $this->dest = $dest;
        $this->items = $items;
    }

    public function empty(): bool
    {
        return empty($this->items);
    }
}