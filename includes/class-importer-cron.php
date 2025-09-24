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
            wp_schedule_event(time(), 'polarsteps_interval', self::HOOK);
            Polarsteps_Importer_Settings::log_message(__('Recurring cron job scheduled on plugin activation.', 'polarsteps-importer'));
        }
    }

    public static function reschedule_recurring_event() {
        $next_timestamp = wp_next_scheduled(self::HOOK);
        self::clear_job();
        $schedule_time = ($next_timestamp) ? $next_timestamp : time();
        wp_schedule_event($schedule_time, 'polarsteps_interval', self::HOOK);
        Polarsteps_Importer_Settings::log_message(__('Recurring cron job re-scheduled after settings update.', 'polarsteps-importer'));
    }

    public static function schedule_single_event() {
        wp_schedule_single_event(time(), self::HOOK);
    }

    public static function clear_job() {
        wp_clear_scheduled_hook(self::HOOK);
    }

    public static function unschedule_all() {
        self::clear_job();
    }
}