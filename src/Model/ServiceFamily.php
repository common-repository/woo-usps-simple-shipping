<?php declare(strict_types=1);

namespace Dgm\UspsSimple\Model;


class ServiceFamily
{
    /**
     * @var string
     * @readonly
     */
    public $id;

    /**
     * @var string
     */
    public $title;

    /**
     * @var int
     */
    public $sort;

    /**
     * @var array<Service>
     * @readonly
     */
    public $services;


    public function __construct(string $id, string $title, array $services)
    {
        $this->id = $id;
        $this->title = $title;
        $this->services = $services;
    }
}