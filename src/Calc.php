<?php /** @noinspection PhpMultipleClassesDeclarationsInOneFile */
declare(strict_types=1);

namespace Dgm\UspsSimple;

use Dgm\UspsSimple\Calc\Cache;
use Dgm\UspsSimple\Calc\Dim;
use Dgm\UspsSimple\Calc\Error\Error;
use Dgm\UspsSimple\Calc\Error\TransportError;
use Dgm\UspsSimple\Calc\Number;
use Dgm\UspsSimple\Calc\Package;
use Dgm\UspsSimple\Calc\Pair;
use Dgm\UspsSimple\Calc\PairMember;
use Dgm\UspsSimple\Calc\Request;
use Dgm\UspsSimple\Model\ServiceFamily;
use Dgm\UspsSimple\Model\Services;
use Exception;
use SimpleXMLElement;
use SplObjectStorage;


class Calc
{
    public function __construct(string $apiEndpoint, Cache $cache)
    {
        $this->apiEndpoint = $apiEndpoint;
        $this->cache = $cache;
    }

    /**
     * @throws Exception
     */
    public function calc(Request $request, Debug $debug): array
    {
        $pkg = $request->package;

        if (
            !$pkg->dest->isDomestic() ||
            $pkg->dest->zipCode === '' ||
            $pkg->empty() ||
            $request->services->empty()
        ) {
            return [];
        }

        $cacheKey = $request->cacheKey();
        $rates = $this->cache->get($cacheKey);
        if ($rates !== null) {
            return $rates;
        }

        $subRequests = self::buildSubRequests($pkg, $request->groupByWeight, $request->services->retailGroundEnabled);
        if ($subRequests === null) {
            return [];
        }

        $requests = $subRequests->map(function(array $parts) use ($request): string {
            return self::buildRequest($parts, $request->apiUserId);
        });
        $debug->recordRequests($requests);

        /** @noinspection PhpUnusedLocalVariableInspection */
        $response = null;
        {

            $responses = self::call($requests, $this->apiEndpoint);
            $debug->recordResponses($responses);

            $response = self::combine($responses);
            $debug->recordCombinedResponse($response);
        }

        $rates = self::processResponse($response, $request->services, $request->commercialRates);
        $this->cache->set($cacheKey, $rates);

        return $rates;
    }


    /**
     * @var string
     */
    private $apiEndpoint;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @return Pair<array<string>>|null
     */
    private static function buildSubRequests(Package $package, bool $groupByWeight, bool $retailGround): ?Pair
    {
        $requestPairs = [];

        $origZip = $package->orig->zipCode;
        $destZip = $package->dest->zipCode;

        $groupWeight = 0;
        foreach ($package->items as $id => $item) {

            $product = $item->product;

            $weight = $product->weight;
            if (!$weight) {
                $weight = 1; // fallback to 1 lb
            }

            $dim = $product->dim;

            $qty = $item->quantity;

            $large = $dim->max() > 12;
            if ($groupByWeight && !$large) {
                $groupWeight += $weight * $qty;
                continue;
            }

            $requestPairs[] = self::buildPackageRequestPair(
                $retailGround,
                self::buildExtPackageId($id, $qty, $dim, $weight),
                $origZip, $destZip,
                $dim, $weight
            );
        }

        $packageWeights = [];
        if ($groupWeight > 0) {

            $maxPackageWeight = 70;

            $fullPackages = floor($groupWeight / $maxPackageWeight);
            while ($fullPackages--) {
                $packageWeights[] = $maxPackageWeight;
            }

            if ($remainder = fmod($groupWeight, $maxPackageWeight)) {
                $packageWeights[] = $remainder;
            }
        }

        foreach ($packageWeights as $key => $weight) {
            $requestPairs[] = self::buildPackageRequestPair(
                $retailGround,
                self::buildExtPackageId('group_by_weight_'.$key, 1, Dim::$ZERO, 0),
                $origZip, $destZip,
                Dim::$ZERO,
                $weight
            );
        }

        if (!$requestPairs) {
            return null;
        }

        $online = [];
        $standard = null;
        foreach ($requestPairs as $p) {
            $online[] = $p->online;
            if (isset($p->standard)) {
                $standard[] = $p->standard;
            }
        }

        return new Pair($online, $standard);
    }

    /**
     * @param Pair<string> $requests
     * @return Pair<string>
     * @throws TransportError
     * @throws Error
     */
    private static function call(Pair $requests, string $apiEndpoint): Pair
    {
        return $requests->map(function(string $request, PairMember $service) use ($apiEndpoint): string {

            $resp = null;
            $success = function() use (&$resp): bool {
                return is_array($resp) && (int)$resp['response']['code'] === 200;
            };


            $tries = 3;
            while ($tries--) {
                $resp = wp_remote_post($apiEndpoint, [
                    'timeout'   => 15,
                    'sslverify' => true,
                    'body'      => $request,
                ]);
                if ($success()) {
                    break;
                }
                sleep(1);
            }

            $errctx = [
                "service"       => $service->uspsServiceName,
                "response_type" => gettype($resp),
                "response"      => $resp,
            ];
            if (!is_array($resp)) {
                throw new TransportError("API request transport error", $errctx);
            }
            if (!$success()) {
                throw new Error("API request HTTP error", $errctx);
            }

            $resp = (string)$resp["body"];
            if ($resp === '') {
                throw new Error("API response is empty", $errctx);
            }

            return $resp;
        });
    }


    /**
     * @return Pair<string>
     */
    private static function buildPackageRequestPair(bool $retailGroundEnabled, string $extPackageId, string $origZip, string $destZip, Dim $dims, $weight): Pair
    {
        return new Pair(
            self::buildPackageRequest($extPackageId, $origZip, $destZip, $dims, $weight, Pair::$ONLINE),
            $retailGroundEnabled ? self::buildPackageRequest($extPackageId, $origZip, $destZip, $dims, $weight, Pair::$STANDARD) : null
        );
    }

    private static function buildPackageRequest(string $extPackageId, string $origZip, string $destZip, Dim $dim, $weight, PairMember $service): string
    {
        $weight = Number::intOrFloat($weight);

        // The USPS API fails with 'The entered cubic feet value must be greater than 0' if any dimension is less than
        // 0.25 in. Clamping should not affect results for regular-size items since Width/Length/Height/Girth are only
        // used for "large" items to calculate weight- or volumetric weight-based rates according the USPS API doc. It
        // might affect "large" items having a dimension less than 0.25 in, but we don't have an alternative.
        if (!$dim->isZero() && $dim->min() < 0.25) {
            $dim = new Dim(max($dim->length, 0.25), max($dim->width, 0.25), max($dim->height, 0.25));
        }

        $fields = [
            'Service' => $service->uspsServiceName,

            'ZipOrigination' => substr($origZip, 0, 5),
            'ZipDestination' => substr($destZip, 0, 5),

            'Pounds' => floor($weight),
            'Ounces' => number_format(($weight - floor($weight)) * 16, 2),

            'Container' => '',

            'Width'  => $dim->width,
            'Length' => $dim->length,
            'Height' => $dim->height,
            'Girth'  => $dim->girth(),

            'GroundOnly' => $service === Pair::$STANDARD ? 'true' : null,
            'Machinable' => 'true',
            'ShipDate'   => date("d-M-Y", strtotime('tomorrow')),
        ];


        $esc = static function($s) {
            return htmlspecialchars((string)$s, ENT_XML1 | ENT_QUOTES);
        };

        $xmls = ["<Package ID=\"{$esc($extPackageId)}\">"];
        foreach ($fields as $key => $val) {

            if (!isset($val)) {
                continue;
            }

            if ($val === '') {
                $xmls[] = "<$key />";
                continue;
            }

            $xmls[] = "<$key>{$esc($val)}</$key>";
        }
        $xmls[] = '</Package>'."\n";

        return join("\n", $xmls);
    }

    /**
     * @param int|string $id
     * @param int|float $qty
     * @param int|float $weight
     */
    private static function buildExtPackageId($id, $qty, Dim $dims, $weight): string
    {
        return join(':', [$id, $qty, $dims->length, $dims->width, $dims->height, $weight]);
    }

    /**
     * @return array<array{
     *     id: string,
     *     label: string,
     *     cost: float,
     * }>
     * @throws Exception
     */
    private static function processResponse(string $xml, Services $services, bool $commercialRates): array
    {
        if (strpos($xml, '<Error>') !== false) {
            throw new Error("API reports an error", ['response' => $xml]);
        }

        // null | SplObjectStorage: ServiceFamily => price
        $rates = null;

        $resp = self::parseXml($xml);
        foreach ($resp->{'Package'} as $uspsPackage) {

            $cartItemQty = null;
            $dim = null;
            {
                $extPkgId = (string)$uspsPackage->attributes()->ID;
                [, $cartItemQty, $packageLength, $packageWidth, $packageHeight] = explode(':', $extPkgId);
                if ($packageLength && $packageWidth && $packageHeight) {
                    $dim = Dim::of($packageLength, $packageWidth, $packageHeight);
                }
            }

            // SplObjectStorage: ServiceFamily => price
            $pkgRates = new SplObjectStorage();
            foreach ($uspsPackage->{'Postage'} as $uspsPostage) {

                $service = null;
                {
                    $code = (string)$uspsPostage->attributes()->CLASSID;
                    $title = strip_tags(htmlspecialchars_decode((string)$uspsPostage->{'MailService'}));
                    $service = $services->find($code, $title);
                    if (!isset($service)) {
                        continue;
                    }
                }

                if ($dim && !$service->fits($dim)) {
                    continue;
                }

                $effective = null;
                {
                    $prices = new Arr();
                    $prices->add($uspsPostage->{'Rate'});
                    if ($service->alwaysUseCommercialRate || $commercialRates) {
                        $prices->add($uspsPostage->{'CommercialRate'});
                    }

                    $prices = $prices
                        ->map(function($x) { return max(0, (float)$x); })
                        ->filter(function($x) { return $x > 0; });

                    if ($prices->empty()) {
                        continue;
                    }

                    $effective = $prices->min();

                    $effective *= $cartItemQty;
                }

                $pkgRates[$service->family] = min($pkgRates[$service->family] ?? PHP_INT_MAX, $effective);
            }

            if (!isset($rates)) {
                $rates = $pkgRates;
                continue;
            }

            // a new object is required since SplObjectStorage iteration breaks on current item removal
            $newRates = new SplObjectStorage();
            foreach ($rates as $f) {
                $price = $pkgRates[$f] ?? null;
                if (!isset($price)) {
                    continue; // keep only rates applicable to all packages
                }
                $newRates[$f] = $rates[$f] + $price;
            }
            $rates = $newRates;
        }

        $fams = iterator_to_array($rates, false);
        usort($fams, function(ServiceFamily $a, ServiceFamily $b): int {
            return $a->sort - $b->sort;
        });

        $extRates = [];
        foreach ($fams as $f) {
            $extRates[$f->id] = [
                'id'    => $f->id,
                'label' => $f->title,
                'cost'  => $rates[$f],
            ];
        }

        return $extRates;
    }

    private static function buildRequest(array $subRequests, string $userId): string
    {
        return 'API=RateV4&XML='.str_replace(["\n", "\r"], '', join('', [
                '<RateV4Request USERID="'.$userId.'">',
                '<Revision>2</Revision>',
                join('', $subRequests),
                '</RateV4Request>',
            ]));
    }

    /**
     * @param Pair<string> $responses
     * @throws Exception
     */
    private static function combine(Pair $responses): string
    {
        $responses = $responses->map(function($x) { return self::parseXml($x); });

        $combined = isset($responses->standard) ? self::transplantRetailGround($responses) : $responses->online;

        $xml = $combined->asXML();
        if (!is_string($xml)) {
            $type = gettype($xml);
            throw new Exception("string expected from \$combined->asXML(), $type given");
        }

        return $xml;
    }

    /**
     * @param Pair<SimpleXMLElement> $responses
     */
    private static function transplantRetailGround(Pair $responses): SimpleXMLElement
    {
        $attr = function(SimpleXMLElement $node, string $aname): string {
            return (string)$node->attributes()->{$aname};
        };

        foreach ($responses->online->{'Package'} as $onlinePackage) {

            // skip the standard-post response if the online response already has a retail ground rate
            foreach ($onlinePackage->{'Postage'} as $postage) {
                if ($attr($postage, 'CLASSID') === Services::RETAIL_GROUND_CODE) {
                    continue 2;
                }
            }

            $onlinePackageId = $attr($onlinePackage, 'ID');

            foreach ($responses->standard->{'Package'} as $standardPackage) {

                $standardPackageId = $attr($standardPackage, 'ID');
                if ($standardPackageId !== $onlinePackageId) {
                    continue;
                }

                foreach ($standardPackage->{'Postage'} as $postage) {

                    if ($attr($postage, 'CLASSID') !== Services::RETAIL_GROUND_CODE) {
                        continue;
                    }

                    $postageCopy = $onlinePackage->addChild('Postage');
                    $postageCopy->addAttribute('CLASSID', Services::RETAIL_GROUND_CODE);
                    $postageCopy->addChild('MailService', (string)$postage->{'MailService'});
                    $postageCopy->addChild('Rate', (string)$postage->{'Rate'});
                }
            }
        }

        return $responses->online;
    }

    /**
     * @throws Exception
     */
    private static function parseXml(string $xml): SimpleXMLElement
    {
        libxml_use_internal_errors(true);

        $r = simplexml_load_string($xml);
        if ($r === false) {
            throw new Error("failed to parse response", [
                'libxml_last_error' => libxml_get_last_error(),
                'xml'               => $xml,
            ]);
        }

        /** @noinspection IsEmptyFunctionUsageInspection */
        if (empty($r)) {
            throw new Error("parsed response is empty", [
                'xml' => $xml,
            ]);
        }

        return $r;
    }
}


/**
 * @template T
 */
class Arr
{
    /**
     * @param array<T> $a
     */
    public function __construct(array $a = [])
    {
        $this->a = $a;
    }

    /**
     * @param T $item
     */
    public function add($item): self
    {
        $this->a[] = $item;
        return $this;
    }

    public function map(callable $f): self
    {
        return new self(array_map($f, $this->a));
    }

    public function filter(callable $f): self
    {
        return new self(array_filter($this->a, $f));
    }

    /**
     * @return T
     */
    public function min()
    {
        return min($this->a);
    }

    public function empty(): bool
    {
        return empty($this->a);
    }

    public static function wrap(array $a): self
    {
        return new self($a);
    }

    public function unwrap(): array
    {
        return $this->a;
    }

    private $a;
}

// Regional rate boxes need additonal checks to deal with USPS's complex API
/*case "47" :
    if ( ( $packageLength > 10 || $packageWidth > 7 || $packageHeight > 4.75 ) && ( $packageLength > 12.875 || $packageWidth > 10.9375 || $packageHeight > 2.365 ) ) {
        continue 2;
    } else {
        // Valid
        break;
    }
    break;
case "49" :
    if ( ( $packageLength > 12 || $packageWidth > 10.25 || $packageHeight > 5 ) && ( $packageLength > 15.875 || $packageWidth > 14.375 || $packageHeight > 2.875 ) ) {
        continue 2;
    } else {
        // Valid
        break;
    }
    break;
case "58" :
    if ( $packageLength > 14.75 || $packageWidth > 11.75 || $packageHeight > 11.5 ) {
        continue 2;
    } else {
        // Valid
        break;
    }
    break;*/