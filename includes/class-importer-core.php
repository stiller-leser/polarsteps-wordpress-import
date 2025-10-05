<?php
class Polarsteps_Importer_Core {

    public function __construct() {
        // Cron-Job-Hooks
        add_filter('cron_schedules', ['Polarsteps_Importer_Cron', 'add_custom_cron_interval']);
        add_action('polarsteps_importer_cron_hook', ['Polarsteps_Importer_Process', 'run'], 10, 1);
        add_action('polarsteps_importer_manual_hook', ['Polarsteps_Importer_Process', 'run'], 10, 1);

        // Admin-Hooks
        add_action('admin_post_polarsteps_importer_run_now', [$this, 'handle_run_now']);
    }

    /**
     * Behandelt den "Jetzt importieren"-Button.
     */
    public function handle_run_now() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'polarsteps-importer'));
        }
        check_admin_referer('polarsteps_importer_run_now');

        // Plane einen dedizierten, einmaligen Job für den manuellen Import.
        wp_schedule_single_event(time(), 'polarsteps_importer_manual_hook', [['manual' => true]]);
        
        Polarsteps_Importer_Settings::log_message(__('Manual import triggered.', 'polarsteps-importer'));

        wp_redirect(admin_url('options-general.php?page=polarsteps-importer&manual_run_triggered=1'));
        exit;
    }
}