<?php

/**
 * WPGraphQL integration: exposes the field as an `SvgIcon` object when
 * WPGraphQL and the wp-graphql-acf bridge are active.
 *
 * @package Advanced Custom Fields: SVG Icon Picker
 */

namespace SmithfieldStudio\AcfSvgIconPicker;

defined('ABSPATH') || exit();

/**
 * Register the field with WPGraphQL when the wp-graphql-acf bridge is active.
 *
 * Exposes the saved slug as a `SvgIcon` object with the resolved URL and inline
 * SVG markup so headless consumers can render directly without a second round
 * trip. Resolution mirrors the PHP helpers: a missing icon resolves to empty
 * `url`/`svg` strings, never an exception.
 *
 * Registers unconditionally; both hooks only fire if WPGraphQL is active, and
 * the inner function_exists() guards keep us safe if the wp-graphql-acf bridge
 * is missing while WPGraphQL itself is present.
 */
add_action('graphql_register_types', static function (): void {
    if (!function_exists('register_graphql_object_type')) {
        return;
    }

    register_graphql_object_type('SvgIcon', [
        'description' => __('An SVG icon picked from the configured icon set.', 'acf-svg-icon-picker'),
        'fields' => [
            'slug' => [
                'type' => 'String',
                'description' => __(
                    'The saved slug. Bare slug in flat mode, "groupkey.slug" in grouped mode.',
                    'acf-svg-icon-picker',
                ),
            ],
            'url' => [
                'type' => 'String',
                'description' => __('Public URL of the resolved SVG file.', 'acf-svg-icon-picker'),
            ],
            'svg' => [
                'type' => 'String',
                'description' => __('Inline SVG markup for the resolved icon.', 'acf-svg-icon-picker'),
            ],
        ],
    ]);
});

add_action('wpgraphql/acf/registry_init', static function (): void {
    if (!function_exists('register_graphql_acf_field_type')) {
        return;
    }

    register_graphql_acf_field_type('svg_icon_picker', [
        'graphql_type' => 'SvgIcon',
        'resolve' => __NAMESPACE__ . '\\resolve_graphql_field',
    ]);
});

/**
 * wp-graphql-acf `resolve` callback for the `SvgIcon` type.
 *
 * wp-graphql-acf passes the AcfGraphQLFieldType 5th and the FieldConfig 6th
 * (see AcfGraphQLFieldType::get_resolver()). Only the FieldConfig has
 * resolve_field(), so reading the 5th resolves every field to null (#40).
 *
 * @return array{slug: string, url: string, svg: string}|null
 */
function resolve_graphql_field(
    mixed $root,
    mixed $args,
    mixed $context,
    mixed $info,
    mixed $field_type,
    mixed $field_config,
): ?array {
    $slug = is_object($field_config) && method_exists($field_config, 'resolve_field')
        ? $field_config->resolve_field($root, $args, $context, $info)
        : null;

    if (!is_string($slug) || $slug === '') {
        return null;
    }

    return [
        'slug' => $slug,
        'url' => get_svg_icon_uri($slug),
        'svg' => get_svg_icon($slug),
    ];
}
