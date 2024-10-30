<?php
namespace mod_mumie\hook\output;

use core\hook\deprecated_callback_replacement;
use core\hook\described_hook;

final class before_standard_top_of_body_html_generation implements described_hook, deprecated_callback_replacement {
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
