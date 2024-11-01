<?php /** @noinspection PhpMultipleClassesDeclarationsInOneFile */
declare(strict_types=1);
namespace Dgm\UspsSimple\Calc;


/**
 * @template T
 */
class Pair
{
    /**
     * @var PairMember
     * @psalm-readonly
     */
    public static $ONLINE;

    /**
     * @var PairMember
     * @psalm-readonly
     */
    public static $STANDARD;


    /**
     * @var T
     * @psalm-readonly
     */
    public $online;

    /**
     * @var T|null
     * @psalm-readonly
     */
    public $standard;


    public function __construct($online, $standard = null)
    {
        $this->online = $online;
        $this->standard = $standard;
    }

    /**
     * @template R
     * @param callable(T, PairMember): R $f
     * @return self<R>
     */
    public function map(callable $f): self
    {
        return new self(
            $f($this->online, self::$ONLINE),
            isset($this->standard) ? $f($this->standard, self::$STANDARD) : null
        );
    }
}


class PairMember {
    /**
     * @var string
     * @psalm-readonly
     */
    public $uspsServiceName;

    public function __construct(string $uspsServiceName)
    {
        $this->uspsServiceName = $uspsServiceName;
    }
}

Pair::$ONLINE = new PairMember('ONLINE');
Pair::$STANDARD = new PairMember('STANDARD POST');