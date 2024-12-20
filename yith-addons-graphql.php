<?php
/**
 * Plugin Name: YITH Add-Ons GraphQL Extended
 * Plugin URI: https://github.com/andreirad15/yith-product-addons-graphql.git
 * Description: Extends GraphQL to include YITH Add-Ons with conditions.
 * Version: 1.4
 * Author: Andrei Rad
 * Author URI: mailto:andrei@age-one.com
 */

// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

add_action('graphql_register_types', function () {
    global $wpdb;

    // Register GraphQL types
    register_graphql_object_type('YITHConditionalRuleType', [
        'description' => 'Conditional Logic Rule',
        'fields' => [
            'addonId' => ['type' => 'String'],
            'condition' => ['type' => 'String'],
        ],
    ]);

    register_graphql_object_type('YITHAddOnOptionType', [
        'description' => 'YITH Add-On Option',
        'fields' => [
            'label' => ['type' => 'String'],
            'price' => ['type' => 'String'],
            'image' => ['type' => 'String'],
            'tooltip' => ['type' => 'String'],
        ],
    ]);

    register_graphql_object_type('YITHAddOnType', [
        'description' => 'YITH Add-On',
        'fields' => [
            'type' => ['type' => 'String'],
            'headingText' => ['type' => 'String'],
            'headingType' => ['type' => 'String'],
            'headingColor' => ['type' => 'String'],
            'title' => ['type' => 'String'],
            'description' => ['type' => 'String'],
            'textContent' => ['type' => 'String'],
            'selectionType' => ['type' => 'String'],
            'options' => ['type' => ['list_of' => 'YITHAddOnOptionType']],
            'conditionalLogic' => ['type' => ['list_of' => 'YITHConditionalRuleType']],
        ],
    ]);

    register_graphql_object_type('YITHAddOnBlockType', [
        'description' => 'YITH Add-On Block',
        'fields' => [
            'name' => ['type' => 'String'],
            'priority' => ['type' => 'Float'],
            'addons' => ['type' => ['list_of' => 'YITHAddOnType']],
        ],
    ]);

    // Register the field on the Product type
    register_graphql_field('Product', 'yithAddOns', [
        'type' => ['list_of' => 'YITHAddOnBlockType'],
        'description' => 'YITH Product Add-Ons & Extra Options',
        'resolve' => function ($product) use ($wpdb) {
    $product_id = $product->databaseId;

    // Fetch all YITH add-on blocks
    $blocks = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}yith_wapo_blocks");

    // Filter blocks based on product rules
    $validBlocks = array_filter($blocks, function ($block) use ($product_id) {
        $blockSettings = maybe_unserialize($block->settings);
        if (!is_array($blockSettings)) {
            return false;
        }

        $rules = $blockSettings['rules'] ?? [];
        $categories = $rules['show_in_categories'] ?? [];
        $products = $rules['show_in_products'] ?? [];
        $showIn = $rules['show_in'] ?? 'all';

        // Ensure $categories and $products are arrays
        $categories = is_array($categories) ? $categories : [];
        $products = is_array($products) ? $products : [];

        $product_categories = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);

        if ($showIn === 'all') {
            return true;
        }

        if ($showIn === 'products') {
            return in_array($product_id, $products) || array_intersect($categories, $product_categories);
        }

        return false;
    });

    if (empty($validBlocks)) {
        // If no valid blocks, return an empty array
        return [];
    }

    // Format blocks and fetch associated add-ons
    return array_map(function ($block) use ($wpdb) {
        $addons = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$wpdb->prefix}yith_wapo_addons WHERE block_id = %d", $block->id)
        );

        $formattedAddOns = array_map(function ($addon) {
            $settings = maybe_unserialize($addon->settings);
            $optionsData = maybe_unserialize($addon->options);

            if (!is_array($settings)) {
                $settings = [];
            }

            $conditionalLogic = [];
            if (!empty($settings['conditional_rule_addon'])) {
                $addonIds = $settings['conditional_rule_addon'];
                $conditions = $settings['conditional_rule_addon_is'] ?? [];
                foreach ($addonIds as $index => $addonId) {
                    $conditionalLogic[] = [
                        'addonId' => $addonId,
                        'condition' => $conditions[$index] ?? 'unknown',
                    ];
                }
            }

            $options = [];
            if (!empty($optionsData['label']) && is_array($optionsData['label'])) {
                $options = array_map(function ($key) use ($optionsData) {
                    return [
                        'label' => $optionsData['label'][$key] ?? '',
                        'price' => $optionsData['price'][$key] ?? '',
                        'image' => $optionsData['image'][$key] ?? '',
                        'tooltip' => $optionsData['tooltip'][$key] ?? '',
                    ];
                }, array_keys($optionsData['label']));
            }

            return [
                'type' => $settings['type'] ?? '',
                'headingText' => $settings['heading_text'] ?? '',
                'headingType' => $settings['heading_type'] ?? '',
                'headingColor' => $settings['heading_color'] ?? '',
                'title' => $settings['title'] ?? '',
                'description' => $settings['description'] ?? '',
                'textContent' => $settings['text_content'] ?? '',
                'selectionType' => $settings['selection_type'] ?? '',
                'options' => $options,
                'conditionalLogic' => $conditionalLogic,
            ];
        }, $addons);

        return [
            'name' => $block->name,
            'priority' => (float) $block->priority,
            'addons' => $formattedAddOns,
        ];
    }, $validBlocks);
},

    ]);
});