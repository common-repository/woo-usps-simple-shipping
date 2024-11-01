<?php declare(strict_types=1);
namespace Dgm\UspsSimple\Calc;


class Area
{
    private const DOMESTIC_COUNTRIES = ["US", "PR", "VI", "MH", "FM", "GU"];

    /**
     * @var string
     */
    public $countryCode;

    /**
     * @var string
     */
    public $zipCode;


    public function __construct(string $countryCode, string $zipCode)
    {
        $this->countryCode = $countryCode;
        $this->zipCode = $zipCode;
    }

    public static function isDomesticStatic(string $countryCode): bool
    {
        return in_array($countryCode, self::DOMESTIC_COUNTRIES, true);
    }

    public function isDomestic(): bool
    {
        return self::isDomesticStatic($this->countryCode);
    }
}