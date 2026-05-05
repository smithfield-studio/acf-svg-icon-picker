<?php

/**
 * Group-key disambiguation concern for the SVG Icon Picker field.
 *
 * Pulled out of the field class so the rules for "two locations slugify to the
 * same key" live in one place — easy to swap if we ever want a different
 * collision strategy (prefix instead of suffix, hash, etc.).
 *
 * @package Advanced Custom Fields: SVG Icon Picker
 */

namespace SmithfieldStudio\AcfSvgIconPicker\Concerns;

defined('ABSPATH') || exit();

trait BuildsGroups {
    /**
     * Append `-2`, `-3`, … to a group key until it doesn't collide with any
     * already-used key. Empty input is treated as already disambiguated by
     * the caller (which supplies a `group-{$i}` fallback).
     *
     * @param list<string> $used_group_keys
     */
    private function disambiguate_group_key(string $base_key, array $used_group_keys): string {
        if (!in_array($base_key, $used_group_keys, true)) {
            return $base_key;
        }
        $n = 2;
        while (in_array("{$base_key}-{$n}", $used_group_keys, true)) {
            $n++;
        }
        return "{$base_key}-{$n}";
    }
}
