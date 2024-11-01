<?php /** @noinspection PhpMultipleClassesDeclarationsInOneFile */
declare(strict_types=1);

namespace Dgm\UspsSimple\Model;

use Dgm\UspsSimple\Calc\Dim;


// serializing closures requires a heavy lib
interface FitFn
{
    function __invoke(Dim $pkg): bool;
}


class FitMinMax implements FitFn
{
    public function __construct(Dim $min, Dim $max)
    {
        $this->min = $min;
        $this->max = $max;
    }

    public function __invoke(Dim $pkg): bool
    {
        return $this->max->fits($pkg) && $pkg->fits($this->min);
    }

    private $min;
    private $max;
}


class FitGirthLen implements FitFn
{
    /**
     * @param int|float $max
     */
    public function __construct($max)
    {
        $this->max = $max;
    }

    public function __invoke(Dim $pkg): bool
    {
        return $pkg->girth() + $pkg->length <= $this->max;
    }

    private $max;
}


class Fitters
{
    /** @var self */
    public static $i;

    public $POSTCARD;
    public $LETTER;
    public $LARGE_ENVELOPE;
    public $PARCEL;

    public function __construct()
    {
        $this->POSTCARD = new FitMinMax(Dim::of(5, 3.5, 0.007), Dim::of(6, 4.25, 0.016));
        $this->LETTER = new FitMinMax(Dim::of(5, 3.5, 0.007), Dim::of(11.5, 6.125, 0.25));
        $this->LARGE_ENVELOPE = new FitMinMax(Dim::of(11.5, 6, 0.25), Dim::of(15, 12, 0.75));
        $this->PARCEL = new FitGirthLen(108);
    }
}


Fitters::$i = new Fitters();

