<?php
/**
 * Uninstall handler for MBR WP Site Detector.
 *
 * Removes all per-plugin lookup transients (mbr_wpsd_plugin_*) from wp_options
 * so no orphan rows remain after the plugin is deleted. Handles multisite by
 * iterating all sites.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

$delete_transients = static function () use ($wpdb) {
    $wpdb->query(
        "DELETE FROM {$wpdb->options}
         WHERE option_name LIKE '_transient_mbr_wpsd_%'
            OR option_name LIKE '_transient_timeout_mbr_wpsd_%'"
    );
};

if (is_multisite()) {
    $site_ids = get_sites(['fields' => 'ids']);
    foreach ($site_ids as $site_id) {
        switch_to_blog($site_id);
        $delete_transients();
        restore_current_blog();
    }
} else {
    $delete_transients();
}
