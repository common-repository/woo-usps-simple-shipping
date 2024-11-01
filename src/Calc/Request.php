<?php declare(strict_types=1);

namespace Dgm\UspsSimple\Calc;


use Dgm\UspsSimple\Model\Services;


class Request
{
    /**
     * @var string
     * @readonly
     */
    public $apiUserId;

    /**
     * @var Package
     * @readonly
     */
    public $package;

    /**
     * @var Services
     * @readonly
     */
    public $services;

    /**
     * @var bool
     * @readonly
     */
    public $groupByWeight;

    /**
     * @var bool
     * @readonly
     */
    public $commercialRates;


    public function __construct(string $apiUserId, Package $package, Services $services, bool $groupByWeight, bool $commercialRates)
    {
        $this->package = $package;
        $this->services = $services;
        $this->groupByWeight = $groupByWeight;
        $this->commercialRates = $commercialRates;
        $this->apiUserId = $apiUserId;
    }

    public function cacheKey(): string
    {
        return hash('sha256', serialize($this));
    }
}