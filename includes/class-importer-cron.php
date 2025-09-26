<?php
class Polarsteps_Importer_Cron {

    const HOOK = 'polarsteps_importer_cron_hook';

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

    public static function schedule_recurring_event() {
        if (!wp_next_scheduled(self::HOOK)) {
            $options = get_option('polarsteps_importer_settings');
            $interval_hours = $options['polarsteps_update_interval'] ?? 1;
            $first_run_time = time() + ($interval_hours * HOUR_IN_SECONDS);
            wp_schedule_event($first_run_time, 'polarsteps_interval', self::HOOK);
            Polarsteps_Importer_Settings::log_message(__('Recurring cron job scheduled.', 'polarsteps-importer'));
        }
    }

    public static function reschedule_after_import() {
        self::schedule_recurring_event();
    }

    public static function schedule_single_event($args = []) {
        wp_schedule_single_event(time(), self::HOOK, $args);
    }

    public static function unschedule_all() {
        wp_clear_scheduled_hook(self::HOOK);
    }
}