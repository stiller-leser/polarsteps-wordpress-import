<?php
class Polarsteps_Importer_Plugin {

    public static function activate() {
        if (!wp_next_scheduled('polarsteps_importer_cron_hook')) {
            // Das Intervall wird über den __construct Hook registriert, also können wir es hier direkt verwenden.
            wp_schedule_event(time(), 'polarsteps_interval', 'polarsteps_importer_cron_hook');
            Polarsteps_Importer_Settings::log_message('Polarsteps Importer aktiviert und Cron-Job geplant.');
        }
    }

    public static function deactivate() {
        Polarsteps_Importer_Settings::log_message('Polarsteps Importer wird deaktiviert...');
        wp_clear_scheduled_hook('polarsteps_importer_cron_hook');
        delete_option('polarsteps_importer_logs');
        delete_option('polarsteps_importer_settings');
    }

    public function __construct() {
        add_action('init', [$this, 'load_plugin_textdomain']);
        add_action('polarsteps_importer_cron_hook', [$this, 'import_steps']);
        add_filter('cron_schedules', [$this, 'add_custom_cron_interval']);
        add_action('admin_post_polarsteps_importer_run_now', [$this, 'handle_run_now']);
    }

    public static function add_custom_cron_interval($schedules) {
        $options = get_option('polarsteps_importer_settings');
        $interval = $options['polarsteps_update_interval'] ?? 1;
        $schedules['polarsteps_interval'] = [
            'interval' => $interval * HOUR_IN_SECONDS,
            'display'  => sprintf(
                /* translators: %d: number of hours */
                _n('Every hour', 'Every %d hours', $interval, 'polarsteps-importer'),
                $interval
            ),
        ];
        return $schedules;
    }

    public static function reschedule_cron_job($new_interval_hours) {
        self::clear_cron_job();
        self::activate();
    }

    private static function clear_cron_job() {
        $timestamp = wp_next_scheduled('polarsteps_importer_cron_hook');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'polarsteps_importer_cron_hook');
        }
    }

    public function handle_run_now() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'polarsteps-importer'));
        }
        check_admin_referer('polarsteps_importer_run_now');

        // Verhindert, dass der gleiche Job mehrfach sofort hintereinander gestartet wird.
        if (!wp_next_scheduled('polarsteps_importer_cron_hook')) {
            // Startet den Import sofort im Hintergrund, anstatt im aktuellen Request.
            // Dies verhindert Timeouts bei großen Importen.
            wp_schedule_single_event(time(), 'polarsteps_importer_cron_hook');
        }
        // Pausiere den wiederkehrenden Job, um Konflikte zu vermeiden.
        self::clear_cron_job();

        // Starte den Import sofort (asynchron im Hintergrund).
        // Dies verhindert Timeouts bei großen Importen.
        wp_schedule_single_event(time(), 'polarsteps_importer_cron_hook');
        Polarsteps_Importer_Settings::log_message(__('Manual import triggered.', 'polarsteps-importer'));

        // Leite den Nutzer zurück. Der wiederkehrende Job wird nach dem Import neu geplant.
        wp_redirect(admin_url('options-general.php?page=polarsteps-importer&manual_run_triggered=1'));
        exit;
    }
    
    public function load_plugin_textdomain() {
        load_plugin_textdomain('polarsteps-importer', false, dirname(plugin_basename(__DIR__)) . '/languages/');
    }

    public function import_steps() {
        $options = get_option('polarsteps_importer_settings');
        $trip_id = $options['polarsteps_trip_id'] ?? '';
        $remember_token = $options['polarsteps_remember_token'] ?? '';
        $post_status = $options['polarsteps_post_status'] ?? 'draft';
        $post_type = $options['polarsteps_post_type'] ?? 'post';
        $category_id = $options['polarsteps_post_category'] ?? 0;
        $debug_mode = $options['polarsteps_debug_mode'] ?? false;
        $ignore_no_title = $options['polarsteps_ignore_no_title'] ?? false;
        $ignored_step_ids = array_map('trim', explode(',', $options['polarsteps_ignored_step_ids'] ?? ''));


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
            // 1. Prüfe, ob der Step ignoriert werden soll
            if (in_array($step['id'], $ignored_step_ids)) {
                Polarsteps_Importer_Settings::log_message("Step-ID {$step['id']} wird ignoriert (in der Ignore-Liste).");
                continue;
            }

            // 2. Prüfe, ob der Step einen Titel hat (falls Option aktiv)
            if ($ignore_no_title && empty($step['name'])) {
                Polarsteps_Importer_Settings::log_message("Step ohne Titel wird ignoriert.");
                continue;
            }
            if (!empty($step['description']) && !Polarsteps_Importer_API::step_exists($step['id'], $post_type)) {
                $step_date = date('Y-m-d H:i:s', $step['creation_time']);
                $post_id = wp_insert_post([
                    'post_title'   => sanitize_text_field($step['name']),
                    'post_content' => wp_kses_post($step['description']),
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
                        'lat' => sanitize_text_field($step['location']['lat']),
                        'lon' => sanitize_text_field($step['location']['lon']),
                        'name' => sanitize_text_field($step['location']['name']),
                        'country' => sanitize_text_field($step['location']['country_code']),
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
                            sanitize_text_field($step['name']),
                            $step['id']
                        )
                    );
                }
            }
        }

        // Nach Abschluss des Imports den wiederkehrenden Cron-Job neu planen.
        self::clear_cron_job(); // Sicherheitshalber vorher aufräumen
        self::activate(); // Plant den Job mit dem in den Einstellungen gespeicherten Intervall neu.
    }
}
