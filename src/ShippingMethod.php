<?php

namespace Dgm\UspsSimple;

use Dgm\UspsSimple\Calc\Area;
use Dgm\UspsSimple\Calc\Cache;
use Dgm\UspsSimple\Calc\Package;
use Dgm\UspsSimple\Calc\Request;
use Dgm\UspsSimple\Model\Services;
use Dgm\UspsSimple\Vendors\Dgm\WcTools\WcTools;


class ShippingMethod extends \WC_Shipping_Method
{

    private const defaultUserId = '891000005749';

    /**
     * @var string
     * @psalm-readonly
     */
    private $sender;

    /**
     * @var bool
     * @psalm-readonly
     */
    private $commercialRates;

    /**
     * @var bool
     * @psalm-readonly
     */
    private $groupByWeight;

    /**
     * @var string
     * @psalm-readonly
     */
    private $apiEndpoint = "https://secure.shippingapis.com/ShippingAPI.dll";

    /**
     * @var string
     * @psalm-readonly
     */
    private $apiUserId;


    public static function handleStorePostcodeChange(): void
    {
        add_action('woocommerce_settings_save_general', function() {

            $m = new self(0, true);
            $serverOverride = (string)($m->settings['sender'] ?? '');
            if ($serverOverride !== '') {
                return;
            }

            $prevStorePostcode = get_option('woocommerce_store_postcode');

            add_action('woocommerce_update_options_general', function() use ($prevStorePostcode) {
                $newStorePostcode = get_option('woocommerce_store_postcode');
                if ($newStorePostcode !== $prevStorePostcode) {
                    WcTools::purgeShippingCache();
                }
            });
        });
    }

    /**
     * @noinspection MagicMethodsValidityInspection No need to call the parent constructor.
     * @noinspection PhpMissingParentConstructorInspection No need to call the parent constructor.
     * @noinspection PhpUnusedParameterInspection
     */
    public function __construct($instanceId = 0, bool $noSideEffects = false)
    {
        $this->id = Plugin::WOO_ID;
        $this->title = 'USPS Simple';
        $this->method_title = $this->title;
        $this->method_description = 'Shows live USPS domestic rates on checkout';

        $this->form_fields = FormFields::build(self::defaultUserId);
        $this->init_settings();
        $s = $this->settings;

        $this->enabled = $s['enabled'] ?? $this->enabled;

        $sender = (string)($s['sender'] ?? '');
        if ($sender === '') {
            $sender = (string)get_option('woocommerce_store_postcode');
        }

        $this->sender = $sender;
        $this->apiUserId = !empty($s['user_id']) ? (string)$s['user_id'] : self::defaultUserId;
        $this->commercialRates = WcTools::yesNo2Bool($s['commercial_rate'] ?? 'yes');
        $this->groupByWeight = WcTools::yesNo2Bool($s['group_by_weight'] ?? 'no');

        if (!$noSideEffects) {
            add_action('woocommerce_update_options_shipping_'.$this->id, [$this, 'process_admin_options']);
        }
    }

    public function calculate_shipping($package = []): void
    {
        $debug = Plugin::instance()->createDebug();

        if ($this->sender === '') {
            $debug->recordError("sender zipcode is not set");
            return;
        }

        if ($debug->enabled()) {
            $debug->recordSettings(get_object_vars($this), $this->settings);
            $debug->recordPackage($package);
        }

        try {
            $rates = $this->calc($package, $debug);
            $debug->recordRates($rates);
        }
        catch (\Exception $e) {
            $msg = $e->getMessage();
            $debug->recordError($msg);
            self::logger()->error($msg);
            return;
        }

        foreach ($rates as $rate) {
            $rate['id'] = "$this->id:".strtoupper($rate['id']);
            $this->add_rate($rate);
        }
    }

    /**
     * @noinspection HtmlUnknownTarget
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    public function admin_options()
    {
        $admin_url = admin_url('admin.php?page=wc-settings&tab=general');

        if (get_woocommerce_currency() !== "USD") {
            echo '<div class="error">
				<p>'.sprintf(__('<a href="%s">Currency</a> must be set in US Dollars.', 'woo-usps-simple-shipping'), $admin_url).'</p>
			</div>';
        }

        if (!Area::isDomesticStatic(WC()->countries->get_base_country())) {
            echo '<div class="error">
				<p>'.sprintf(__('<a href="%s">Store country</a> is expected to be the United States.', 'woo-usps-simple-shipping'), $admin_url).'</p>
			</div>';
        }

        if (!$this->sender && $this->enabled === 'yes') {
            echo '<div class="error">
				<p>'.__('The origin postcode has not been set.', 'woo-usps-simple-shipping').'</p>
			</div>';
        }

        echo '<style>
            .woocommerce table.form-table .uspss-subservice-row th,
            .woocommerce table.form-table .uspss-subservice-row td {
                padding-top: 0;
                padding-bottom: 0;
            }
        </style>';

        parent::admin_options();

        /** @noinspection JSUnresolvedReference */
        echo '<script>jQuery("tr:has(.uspss-subservice-checkbox)").addClass("uspss-subservice-row");</script>';
    }

    /**
     * @throws \Exception
     */
    public function calc(array $package, Debug $debug = null): array
    {
        $debug = $debug ?? Debug::noop();

        $cache = $debug->enabled() ? Cache::noop() : new Cache();

        $calc = new Calc($this->apiEndpoint, $cache);

        $services = new Services(
            function(string $familyId, string $title): string {
                return ($this->settings["t_$familyId"] ?? "") ?: $title;
            },
            function(string $familyId, string $serviceId): bool {
                return WcTools::yesNo2Bool($this->settings["{$familyId}_$serviceId"] ?? 'no');
            },
            true
        );

        $pkg = Package::fromWcPackage(
            $package,
            new Area(WC()->countries->get_base_country(), $this->sender)
        );

        $request = new Request($this->apiUserId, $pkg, $services, $this->groupByWeight, $this->commercialRates);
        $debug->recordTheRequest($request);

        return $calc->calc($request, $debug);
    }

    private static function logger(): \WC_Logger_Interface
    {
        return wc_get_logger();
    }
}