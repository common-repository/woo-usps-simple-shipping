<?php declare(strict_types=1);

namespace Dgm\UspsSimple\Model;

use Dgm\UspsSimple\Calc\Dim;


abstract class Service
{
    /**
     * @var ServiceFamily
     */
    public $family;

    /**
     * @var string
     */
    public $title;

    /**
     * @var bool
     */
    public $alwaysUseCommercialRate;

    /**
     * @var bool
     */
    public $enabled;

    /**
     * @var string
     */
    public $id;


    public function __construct(string $title, string $id)
    {
        $this->id = $id;
        $this->title = $title;
    }

    abstract public function matches(string $uspsCode, string $uspsTitle): bool;

    abstract public function fits(Dim $dim): bool;
}