<?php /** @noinspection PhpMultipleClassesDeclarationsInOneFile */
declare(strict_types=1);
namespace Dgm\UspsSimple\Calc;


class Cache
{
    public static function noop(): Cache
    {
        $c = new self();
        $c->noop = true;
        return $c;
    }

    public function get(string $key): ?array
    {
        $res = $this->noop ? false : get_transient(self::key($key));
        return $res === false ? null : $res;
    }

    public function set(string $key, array $rates): void
    {
        if (!$this->noop) {
            set_transient(self::key($key), $rates, self::TTL);
        }
    }

    private $noop = false;

    private const KEY_PREFIX = "usps_simple_quote_";
    private const TTL = 5*DAY_IN_SECONDS;

    private static function key(string $k): string
    {
        return self::KEY_PREFIX.$k;
    }
}