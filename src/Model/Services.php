<?php /** @noinspection PhpMultipleClassesDeclarationsInOneFile */
declare(strict_types=1);

namespace Dgm\UspsSimple\Model;

use Dgm\UspsSimple\Calc\Dim;


class Services
{
    public const RETAIL_GROUND_CODE = '4';

    /**
     * @readonly
     * @var array<ServiceFamily>
     */
    public $families;

    /**
     * @var bool
     * @readonly
     */
    public $retailGroundEnabled;

    /**
     * @param ?callable(string $familyId, string $default): string $serviceFamilyTitle
     * @param ?callable(string $familyId, string $serviceId): bool $serviceEnabled
     * @param bool $skipInactive
     */
    public function __construct(callable $serviceFamilyTitle = null, callable $serviceEnabled = null, bool $skipInactive = false)
    {
        $express = new ServiceFamily(
            'express_mail',
            __('Priority Mail Express', 'woo-usps-simple-shipping'),
            [
                new RegularService(
                    __('Priority Mail Express', 'woo-usps-simple-shipping'),
                    '3'
                ),
                new RegularService(
                    __('Priority Mail Express, Hold for Pickup', 'woo-usps-simple-shipping'),
                    '2'
                ),
                new RegularService(
                    __('Priority Mail Express, Sunday/Holiday', 'woo-usps-simple-shipping'),
                    '23'
                ),
            ]
        );

        $priority = new ServiceFamily(
            'priority_mail',
            __('Priority Mail', 'woo-usps-simple-shipping'),
            [
                new RegularService(
                    __('Priority Mail', 'woo-usps-simple-shipping'),
                    '1'
                ),
                new RegularService(
                    __('Priority Mail, Hold For Pickup', 'woo-usps-simple-shipping'),
                    '33'
                ),
                new RegularService(
                    __('Priority Mail Keys and IDs', 'woo-usps-simple-shipping'),
                    '18'
                ),
                new RegularService(
                    __('Priority Mail Regional Rate Box A', 'woo-usps-simple-shipping'),
                    '47'
                ),
                new RegularService(
                    __('Priority Mail Regional Rate Box A, Hold For Pickup', 'woo-usps-simple-shipping'),
                    '48'
                ),
                new RegularService(
                    __('Priority Mail Regional Rate Box B', 'woo-usps-simple-shipping'),
                    '49'
                ),
                new RegularService(
                    __('Priority Mail Regional Rate Box B, Hold For Pickup', 'woo-usps-simple-shipping'),
                    '50'
                ),
            ]
        );

        $sizes = Fitters::$i;
        $firstClass = new ServiceFamily(
            'first_class',
            __('First-Class Mail', 'woo-usps-simple-shipping'),
            [

                ## Postcards

                new FirstClass(
                    __('First-Class Mail Postcards', 'woo-usps-simple-shipping'),
                    '0A', '0',
                    $sizes->POSTCARD,
                    'Postcards'
                ),
                new FirstClass(
                    __('First-Class Mail Stamped Postcards', 'woo-usps-simple-shipping'),
                    '12', '12',
                    $sizes->POSTCARD
                ),
                new FirstClass(
                    __('First-Class Mail Large Postcards', 'woo-usps-simple-shipping'),
                    '15', '15',
                    $sizes->POSTCARD
                ),


                ## Letters

                new FirstClass(
                    __('First-Class Mail Letter', 'woo-usps-simple-shipping'),
                    '0B', '0',
                    $sizes->LETTER,
                    'Letter'
                ),
                new FirstClass(
                    __('First-Class Mail Metered Letter', 'woo-usps-simple-shipping'),
                    '78', '78',
                    $sizes->LETTER
                ),


                ## Large Envelope

                new FirstClass(
                    __('First-Class Mail Large Envelope', 'woo-usps-simple-shipping'),
                    '0C', '0',
                    $sizes->LARGE_ENVELOPE,
                    'Large Envelope'
                ),
            ]
        );


        $groundAdvantage = new ServiceFamily(
            'ground_advantage',
            __('USPS Ground Advantage', 'woo-usps-simple-shipping'),
            [
                new GroundAdvantage(__('USPS Ground Advantage', 'woo-usps-simple-shipping')),
            ]
        );

        $retailGround = new ServiceFamily(
            'standard_post',
            __('USPS Retail Ground', 'woo-usps-simple-shipping'),
            [
                new RegularService(
                    __('USPS Retail Ground', 'woo-usps-simple-shipping'),
                    self::RETAIL_GROUND_CODE
                ),
            ]
        );

        $media = new ServiceFamily(
            'media_mail',
            __('Media Mail', 'woo-usps-simple-shipping'),
            [
                new AlwaysCommercial(
                    __('Media Mail', 'woo-usps-simple-shipping'),
                    '6'
                ),
            ]
        );


        $library = new ServiceFamily(
            'library_mail',
            __('Library Mail', 'woo-usps-simple-shipping'),
            [
                new AlwaysCommercial(
                    __('Library Mail', 'woo-usps-simple-shipping'),
                    '7'
                ),
            ]
        );


        /** @var array<ServiceFamily> $families */
        $families = [$express, $priority, $firstClass, $groundAdvantage, $retailGround, $media, $library];
        foreach ($families as $i => $family) {

            if (isset($serviceFamilyTitle)) {
                $family->title = $serviceFamilyTitle($family->id, $family->title);
            }

            $family->sort = $i;

            foreach ($family->services as $k => $service) {

                $service->family = $family;

                if (isset($serviceEnabled)) {
                    $service->enabled = $serviceEnabled($family->id, $service->id);
                }

                if ($skipInactive && !$service->enabled) {
                    unset($family->services[$k]);
                }
            }

            if (!$family->services) {
                unset($families[$i]);
            }
        }

        $this->families = $families;

        $this->retailGroundEnabled = !empty($retailGround->services) && reset($retailGround->services)->enabled;
    }

    public function find(string $uspsCode, string $uspsTitle): ?Service
    {
        foreach ($this->families as $family) {
            foreach ($family->services as $service) {
                if ($service->matches($uspsCode, $uspsTitle)) {
                    return $service;
                }
            }
        }

        return null;
    }

    public function empty(): bool
    {
        return empty($this->families);
    }
}


class RegularService extends Service
{
    public function matches(string $uspsCode, string $uspsTitle): bool
    {
        return $uspsCode === $this->id;
    }

    public function fits(Dim $dim): bool
    {
        return true;
    }
}


class FirstClass extends Service
{
    public function __construct(
        string $title, string $id, string $code,
        FitFn $fitFn, string $serviceNamePattern = ''
    ) {
        parent::__construct($title, $id);
        $this->code = $code;
        $this->fitFn = $fitFn;
        $this->serviceNamePattern = $serviceNamePattern;
    }

    public function matches(string $uspsCode, string $uspsTitle): bool
    {
        if ($uspsCode !== $this->code) {
            return false;
        }

        if ($this->serviceNamePattern === '') {
            return true;
        }
        if ($this->serviceNamePattern[0] !== '/') {
            return strpos($uspsTitle, $this->serviceNamePattern) !== false;
        }
        return (bool)preg_match($this->serviceNamePattern, $uspsTitle);
    }

    public function fits(Dim $dim): bool
    {
        return ($this->fitFn)($dim);
    }

    /**
     * @var string
     */
    private $code;

    /**
     * @var FitFn
     */
    private $fitFn;

    /**
     * @var string
     */
    private $serviceNamePattern;
}


class GroundAdvantage extends Service
{
    public function __construct(string $title)
    {
        parent::__construct($title, 'default');
    }

    public function matches(string $uspsCode, string $uspsTitle): bool
    {
        return strpos($uspsTitle, 'USPS Ground Advantage') !== false;
    }

    public function fits(Dim $dim): bool
    {
        return true;
    }
}


class AlwaysCommercial extends RegularService
{
    public function __construct(string $title, string $id)
    {
        parent::__construct($title, $id);
        $this->alwaysUseCommercialRate = true;
    }
}