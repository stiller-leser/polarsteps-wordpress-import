<?php
class Polarsteps_Importer_Core {

    public function __construct() {
        // Initialisierungs-Hooks
        add_action('init', [$this, 'load_plugin_textdomain']);

        // Cron-Job-Hooks
        add_filter('cron_schedules', ['Polarsteps_Importer_Cron', 'add_custom_cron_interval']);
        add_action('polarsteps_importer_cron_hook', ['Polarsteps_Importer_Process', 'run']);

        // Admin-Hooks
        add_action('admin_post_polarsteps_importer_run_now', [$this, 'handle_run_now']);
    }

    /**
     * Lädt die Text-Domain für Übersetzungen.
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain('polarsteps-importer', false, dirname(plugin_basename(__DIR__)) . '/languages/');
    }

    /**
     * Behandelt den "Jetzt importieren"-Button.
     */
    public function handle_run_now() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'polarsteps-importer'));
        }
        check_admin_referer('polarsteps_importer_run_now');

        Polarsteps_Importer_Cron::clear_job();
        Polarsteps_Importer_Cron::schedule_single_event();

        Polarsteps_Importer_Settings::log_message(__('Manual import triggered.', 'polarsteps-importer'));

        wp_redirect(admin_url('options-general.php?page=polarsteps-importer&manual_run_triggered=1'));
        exit;
    }
}