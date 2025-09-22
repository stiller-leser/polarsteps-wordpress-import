<?php
class Polarsteps_Importer_Plugin {

    public static function activate() {
        if (!wp_next_scheduled('polarsteps_importer_cron_hook')) {
            wp_schedule_event(time(), 'polarsteps_interval', 'polarsteps_importer_cron_hook');
        }
    }

    public static function deactivate() {
        wp_clear_scheduled_hook('polarsteps_importer_cron_hook');
    }

    public function __construct() {
        add_action('polarsteps_importer_cron_hook', [$this, 'import_steps']);
        add_filter('cron_schedules', [$this, 'add_custom_cron_interval']);
        add_action('admin_post_polarsteps_importer_run_now', [$this, 'handle_run_now']);
    }

    public function add_custom_cron_interval($schedules) {
        $options = get_option('polarsteps_importer_settings');
        $interval = $options['polarsteps_update_interval'] ?? 1;
        $schedules['polarsteps_interval'] = [
            'interval' => $interval * HOUR_IN_SECONDS,
            'display'  => __('Alle ' . $interval . ' Stunden'),
        ];
        return $schedules;
    }

    public function import_steps() {
        $options = get_option('polarsteps_importer_settings');
        $trip_id = $options['polarsteps_trip_id'] ?? '';
        $remember_token = $options['polarsteps_remember_token'] ?? '';
        $post_status = $options['polarsteps_post_status'] ?? 'draft';
        $post_type = $options['polarsteps_post_type'] ?? 'post';
        $category_id = $options['polarsteps_post_category'] ?? 0;
        $debug_mode = $options['polarsteps_debug_mode'] ?? false;

        if (empty($trip_id) || empty($remember_token)) {
            Polarsteps_Importer_Settings::log_message('Trip-ID oder Remember Token fehlt.');
            return;
        }

        $remember_token_decrypted = Polarsteps_Importer_Security::decrypt($remember_token);

        $steps = Polarsteps_Importer_API::fetch_steps($trip_id, $remember_token_decrypted, $debug_mode);

        if ($debug_mode || !$steps) {
            return;
        }

        foreach ($steps as $step) {
            if (!empty($step['description']) && !Polarsteps_Importer_API::step_exists($step['id'], $post_type)) {
                $step_date = date('Y-m-d H:i:s', $step['creation_time']);
                $post_id = wp_insert_post([
                    'post_title'   => $step['name'],
                    'post_content' => $step['description'],
                    'post_status'  => $post_status,
                    'post_type'    => $post_type,
                    'post_date'    => $step_date,
                    'post_date_gmt'=> get_gmt_from_date($step_date),
                ]);

                if ($post_id && $category_id > 0) {
                    $taxonomies = get_object_taxonomies($post_type, 'objects');
                    foreach ($taxonomies as $taxonomy) {
                        if ($taxonomy->hierarchical) {
                            wp_set_object_terms($post_id, (int)$category_id, $taxonomy->name);
                            break; 
                        }
                    }
                }

                if ($post_id && !empty($step['media'])) {
                    Polarsteps_Importer_API::import_step_photos($step['media'], $post_id);
                }

                if ($post_id && !empty($step['location'])) {
                    update_post_meta($post_id, '_polarsteps_location', [
                        'lat' => $step['location']['lat'],
                        'lon' => $step['location']['lon'],
                        'name' => $step['location']['name'],
                        'country' => $step['location']['country_code'],
                    ]);
                }

                if ($post_id) {
                    $meta_added = add_post_meta($post_id, '_polarsteps_step_id', $step['id'], true);
                    Polarsteps_Importer_Settings::log_message(
                        sprintf(
                            'Meta-Feld _polarsteps_step_id %d für Post %d gesetzt: %s',
                            $step['id'],
                            $post_id,
                            $meta_added ? 'Erfolgreich' : 'Fehlgeschlagen'
                        )
                    );

                    Polarsteps_Importer_Settings::log_message(
                        sprintf(
                            'Post %d aus step %s erfolgreich importiert.',
                            $step['name'],
                            $step['id']
                        )
                    );
                }
            }
        }
    }

    public function handle_run_now() {
        if (!current_user_can('manage_options')) {
            wp_die('Du hast keine Berechtigung, diese Aktion auszuführen.');
        }
        check_admin_referer('polarsteps_importer_run_now');
        $this->import_steps();
        wp_redirect(admin_url('options-general.php?page=polarsteps-importer&run=1'));
        exit;
    }
}
