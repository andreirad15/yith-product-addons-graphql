<?php
/**
 * Plugin Name: YITH Add-Ons GraphQL
 * Plugin URI: https://github.com/andreirad15/yith-product-addons-graphql.git
 * Description: Extends GraphQL to include YITH Add-Ons.
 * Version: 1.0
 * Author: Andrei Rad
 * Author URI: mailto:andrei@age-one.com
 */

// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

// Register GraphQL fields
add_action('graphql_register_types', function () {
    global $wpdb;

    // Register the `yithAddOns` field for products
    register_graphql_field('Product', 'yithAddOns', [
        'type' => ['list_of' => 'YITHAddOnBlockType'],
        'description' => 'YITH Product Add-Ons & Extra Options',
        'resolve' => function ($product) use ($wpdb) {
            $product_id = $product->databaseId;

            // Fetch all YITH add-on blocks
            $blocks = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}yith_wapo_blocks");

            $validBlocks = array_filter($blocks, function ($block) use ($product_id, $wpdb) {
                $blockSettings = maybe_unserialize($block->settings);
                $rules = $blockSettings['rules'] ?? [];
                $categories = $rules['show_in_categories'] ?? [];
                $products = $rules['show_in_products'] ?? [];
                $showIn = $rules['show_in'] ?? 'all';

                // Get product categories
                $product_categories = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);

                // Check if the block is valid for this product
                return $showIn === 'all'
                    || ($showIn === 'products' && (in_array($product_id, $products) || array_intersect($product_categories, $categories)));
            });

            // Format blocks and fetch associated add-ons
            $addOnBlocks = array_map(function ($block) use ($wpdb) {
                $addons = $wpdb->get_results(
                    $wpdb->prepare("SELECT * FROM {$wpdb->prefix}yith_wapo_addons WHERE block_id = %d", $block->id)
                );

                $formattedAddOns = array_map(function ($addon) {
                    $settings = maybe_unserialize($addon->settings);
                    $options = maybe_unserialize($addon->options);

                    return [
                        'title' => $settings['title'] ?? '',
                        'description' => $settings['description'] ?? '',
                        'options' => array_map(function ($key) use ($options) {
                            return [
                                'label' => $options['label'][$key] ?? '',
                                'price' => $options['price'][$key] ?? '',
                                'image' => $options['image'][$key] ?? '',
                                'tooltip' => $options['tooltip'][$key] ?? '',
                            ];
                        }, array_keys($options['label'] ?? []))
                    ];
                }, $addons);

                return [
                    'name' => $block->name,
                    'priority' => $block->priority,
                    'addons' => $formattedAddOns
                ];
            }, $validBlocks);

            return $addOnBlocks;
        }
    ]);

    // Register the `YITHAddOnBlockType`
    register_graphql_object_type('YITHAddOnBlockType', [
        'description' => 'YITH Add-On Block',
        'fields' => [
            'name' => ['type' => 'String'],
            'priority' => ['type' => 'Float'],
            'addons' => ['type' => ['list_of' => 'YITHAddOnType']],
        ],
    ]);

    // Register the `YITHAddOnType`
    register_graphql_object_type('YITHAddOnType', [
        'description' => 'YITH Add-On',
        'fields' => [
            'title' => ['type' => 'String'],
            'description' => ['type' => 'String'],
            'options' => ['type' => ['list_of' => 'YITHAddOnOptionType']],
        ],
    ]);

    // Register the `YITHAddOnOptionType`
    register_graphql_object_type('YITHAddOnOptionType', [
        'description' => 'YITH Add-On Option',
        'fields' => [
            'label' => ['type' => 'String'],
            'price' => ['type' => 'String'],
            'image' => ['type' => 'String'],
            'tooltip' => ['type' => 'String'],
        ],
    ]);
});
