<?php

namespace Dgm\UspsSimple;


use Dgm\UspsSimple\Model\Services;


class FormFields
{
    public static function build(string $defaultUspsApiUserId): array
    {
        return self::options($defaultUspsApiUserId) + self::uspsServices();
    }

    private static function options(string $defaultUspsApiUserId): array
    {
        return [
            'enabled'         => [
                'title'   => 'Enable/Disable',
                'type'    => 'checkbox',
                'label'   => 'Enable this shipping method',
                'default' => 'yes',
            ],
            'sender'          => [
                'title'       => __('Postcode', 'woo-usps-simple-shipping'),
                'type'        => 'text',
                'description' => __('Enter the origin postcode.', 'woo-usps-simple-shipping'),
                'default'     => '',
                'placeholder' => get_option('woocommerce_store_postcode'),
                'desc_tip'    => true,
            ],
            'user_id'         => [
                'title'       => __('User ID', 'woo-usps-simple-shipping'),
                'type'        => 'text',
                'description' => __('Enter user id, which was provided after registering at USPS website, or use our id.', 'woo-usps-simple-shipping'),
                'default'     => '',
                'placeholder' => $defaultUspsApiUserId,
                'desc_tip'    => true,
            ],
            'commercial_rate' => [
                'title'   => __('Commercial rates', 'woo-usps-simple-shipping'),
                'type'    => 'checkbox',
                'label'   => 'Use USPS Commercial Pricing if available',
                'default' => 'yes',
            ],
            'group_by_weight' => [
                'title'       => __('Packing', 'woo-usps-simple-shipping'),
                'type'        => 'checkbox',
                'label'       => 'Quote regular items by weight',
                'default'     => 'no',
                'desc_tip'    => true,
                'description' => __('
                    If enabled, regular items (less then 12 inches by their longest side) are quoted by their 
                    total weight with zero dimensions. Large items are quoted individually still. Otherwise, 
                    all items are quoted individually.
                ', 'woo-usps-simple-shipping'),
            ],
        ];
    }

    private static function uspsServices(): array
    {
        $services = new Services();

        $helptip = __('This controls the title which the customer sees during checkout.', 'woo-usps-simple-shipping');

        $fields = [];
        foreach ($services->families as $family) {

            $id = strtolower($family->id);

            $fields[$id] = [
                'title' => $family->title,
                'type'  => 'title',
            ];

            $fields["t_$id"] = [
                'title'       => 'Title',
                'type'        => 'text',
                'description' => $helptip,
                'placeholder' => $family->title,
                'desc_tip'    => true,
            ];

            foreach ($family->services as $service) {
                $fields["{$id}_$service->id"] = [
                    'label'   => $service->title,
                    'type'    => 'checkbox',
                    'default' => 'yes',
                    'class'   => 'uspss-subservice-checkbox',
                ];
            }
        }

        return $fields;
    }
}