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

add_filter('wpgraphql/acf/field_value', __NAMESPACE__ . '\\restore_raw_graphql_values', 10, 4);

/**
 * wp-graphql-acf `resolve` callback for the `SvgIcon` type.
 *
 * wp-graphql-acf passes the AcfGraphQLFieldType 5th and the FieldConfig 6th
 * (see AcfGraphQLFieldType::get_resolver()). Only the FieldConfig has
 * resolve_field(), so reading the 5th resolves every field to null (#40).
 *
 * The slug comes from format_value() as the `value` return format, so legacy
 * values map to their slug and a value outside allowed_groups resolves to
 * null, the same as get_field().
 *
 * @internal
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
    if (!is_object($field_config) || !method_exists($field_config, 'resolve_field')) {
        return null;
    }

    $value = $field_config->resolve_field($root, $args, $context, $info);

    // Rows restore_raw_graphql_values() can't re-read (ACF blocks, clone
    // fields) arrive formatted. The `array` format still carries the slug;
    // `icon` markup doesn't, and fails validation below.
    if (is_array($value)) {
        $value = $value['slug'] ?? null;
    }

    $acf_field = method_exists($field_config, 'get_acf_field') ? $field_config->get_acf_field() : null;
    if (is_string($value) && is_array($acf_field)) {
        $as_value = ['return_format' => 'value'] + $acf_field;
        $value = apply_filters('acf/format_value/type=svg_icon_picker', $value, null, $as_value);
    }

    if (!is_string($value) || !is_valid_icon_value($value)) {
        return null;
    }

    return [
        'slug' => $value,
        'url' => get_svg_icon_uri($value),
        'svg' => get_svg_icon($value),
    ];
}

/**
 * `wpgraphql/acf/field_value` callback.
 *
 * wp-graphql-acf reads repeater and flexible content fields with ACF
 * formatting on, and ACF drops each sub-field's saved value from the row when
 * it formats it. Icon sub-fields using the `icon` return format would reach
 * resolve_graphql_field() as SVG markup with no slug, so this swaps the saved
 * values back in.
 *
 * Only runs for top-level fields read by ID. ACF block data and sub-fields
 * are read differently by wp-graphql-acf and keep their formatted values.
 *
 * @internal
 */
function restore_raw_graphql_values(mixed $value, mixed $acf_field, mixed $root, mixed $node_id): mixed {
    if (
        !is_array($value)
        || !is_array($acf_field)
        || !in_array($acf_field['type'] ?? null, ['repeater', 'flexible_content'], true)
        || !is_string($acf_field['key'] ?? null)
        || !is_int($node_id) && !is_string($node_id)
        || empty($node_id)
    ) {
        return $value;
    }

    $node = is_array($root) ? $root['node'] ?? null : null;
    if (is_array($node) && isset($node['blockName'])) {
        return $value;
    }

    $parent = $acf_field['parent'] ?? null;
    if ((is_int($parent) || is_string($parent)) && acf_get_field($parent)) {
        return $value;
    }

    return restore_raw_icon_values($acf_field, get_field($acf_field['key'], $node_id, false), $value);
}

/**
 * Replace formatted svg_icon_picker values inside a formatted ACF value with
 * the matching saved values, walking repeater, flexible content and group
 * sub-fields.
 *
 * @internal
 * @param array<mixed> $field ACF field array.
 */
function restore_raw_icon_values(array $field, mixed $raw, mixed $formatted): mixed {
    $type = $field['type'] ?? null;

    if ($type === 'svg_icon_picker') {
        return is_string($raw) ? $raw : $formatted;
    }

    if (!is_array($raw) || !is_array($formatted)) {
        return $formatted;
    }

    if ($type === 'group') {
        return restore_raw_icon_row($field['sub_fields'] ?? null, $raw, $formatted);
    }

    if ($type === 'repeater') {
        foreach ($formatted as $i => $row) {
            $formatted[$i] = restore_raw_icon_row($field['sub_fields'] ?? null, $raw[$i] ?? null, $row);
        }
    }

    if ($type === 'flexible_content') {
        $layouts = is_array($field['layouts'] ?? null) ? array_column($field['layouts'], 'sub_fields', 'name') : [];
        foreach ($formatted as $i => $row) {
            $layout = is_array($row) ? $row['acf_fc_layout'] ?? null : null;
            $sub_fields = is_string($layout) ? $layouts[$layout] ?? null : null;
            $formatted[$i] = restore_raw_icon_row($sub_fields, $raw[$i] ?? null, $row);
        }
    }

    return $formatted;
}

/**
 * Formatted rows are keyed by sub-field name, saved rows by sub-field key.
 *
 * @internal
 */
function restore_raw_icon_row(mixed $sub_fields, mixed $raw_row, mixed $formatted_row): mixed {
    if (!is_array($sub_fields) || !is_array($raw_row) || !is_array($formatted_row)) {
        return $formatted_row;
    }

    foreach ($sub_fields as $sub_field) {
        if (!is_array($sub_field)) {
            continue;
        }

        $key = $sub_field['key'] ?? null;
        $name = $sub_field['_name'] ?? $sub_field['name'] ?? null;
        if (
            !is_string($key)
            || !is_string($name)
            || !array_key_exists($key, $raw_row)
            || !array_key_exists($name, $formatted_row)
        ) {
            continue;
        }

        $formatted_row[$name] = restore_raw_icon_values($sub_field, $raw_row[$key], $formatted_row[$name]);
    }

    return $formatted_row;
}
