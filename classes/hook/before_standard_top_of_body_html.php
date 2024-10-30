<?php
namespace core\hook;

final class before_standard_top_of_body_html implements described_hook, deprecated_callback_replacement {
    public static function get_hook_description(): string {
        return 'Hook used to update grades for MUMIE tasks, whenever a gradebook is opened';
    }

    public static function get_hook_tags(): array {
        return ['gradesync'];
    }

    public static function get_deprecated_plugin_callbacks(): array {
        return ['before_standard_top_of_body_html'];
    }
}
