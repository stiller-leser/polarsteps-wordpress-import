<?php
class Polarsteps_Importer_Settings {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        $settings_fields = new Polarsteps_Importer_Settings_Fields();
        add_action('admin_init', [$settings_fields, 'init']);
    }

    public function add_admin_menu() {
        add_menu_page(
            __('Polarsteps Importer', 'polarsteps-importer'),
            __('Polarsteps Importer', 'polarsteps-importer'),
            'manage_options', // Capability
            'polarsteps-importer',
            [$this, 'options_page_with_logs'],
            'dashicons-location-alt',
            80
        );
    }

    // Logs in der Datenbank speichern
    public static function log_message($message) {
        $logs = get_option('polarsteps_importer_logs', []);
        $logs[] = '[Polarsteps Importer] ' . current_time('mysql') . ': ' . $message;
        if (count($logs) > 5000) {
            array_shift($logs);
        }
        update_option('polarsteps_importer_logs', $logs);
    }

    // Kombinierte Seite für Einstellungen und Logs
    public function options_page_with_logs() {
        // Init variables for the view
        $logs_cleared = false;
        $manual_run_triggered = false;
        $import_completed = false;
        $posts_deleted = isset($_GET['posts_deleted']) ? intval($_GET['posts_deleted']) : null;
        $posts_converted = isset($_GET['posts_converted']) ? intval($_GET['posts_converted']) : null;
        $posts_converted_images = isset($_GET['posts_converted_images']) ? intval($_GET['posts_converted_images']) : null;

        // Verarbeite das Löschen der Logs
        if (isset($_POST['clear_logs']) && check_admin_referer('polarsteps_importer_clear_logs_nonce', 'polarsteps_importer_clear_logs_nonce')) {
            delete_option('polarsteps_importer_logs');
            $logs_cleared = true;
        }

        // Benachrichtigungen prüfen
        if (isset($_GET['manual_run_triggered']) && $_GET['manual_run_triggered'] === '1') {
            $manual_run_triggered = true;
        }

        if (get_transient('polarsteps_import_completed')) {
            $import_completed = true;
            delete_transient('polarsteps_import_completed');
        }

        // Logs abrufen
        $logs = get_option('polarsteps_importer_logs', []);

        // Pagination and Sorting Parameters
        $paged = max(1, isset($_GET['paged']) ? intval($_GET['paged']) : 1);
        
        $posts_per_page_options = [10, 25, 50, 100, -1];
        $posts_per_page = isset($_GET['posts_per_page']) ? intval($_GET['posts_per_page']) : 10;
        if (!in_array($posts_per_page, $posts_per_page_options)) {
            $posts_per_page = 10; // Default fallback
        }

        $orderby = isset($_GET['orderby']) && in_array($_GET['orderby'], ['title', 'date', 'ID']) ? sanitize_text_field($_GET['orderby']) : 'date';
        $order = isset($_GET['order']) && in_array(strtoupper($_GET['order']), ['ASC', 'DESC']) ? strtoupper($_GET['order']) : 'DESC';

        // Importierte Posts abrufen (Query)
        $args = [
            'post_type'      => 'any',
            'post_status'    => 'any',
            'posts_per_page' => $posts_per_page,
            'paged'          => $paged,
            'meta_key'       => '_polarsteps_step_id',
            'orderby'        => $orderby,
            'order'          => $order,
        ];
        
        $query = new WP_Query($args);
        $posts = $query->posts;
        $total_posts = $query->found_posts;
        $total_pages = $query->max_num_pages;

        // View einbinden
        include dirname(__FILE__) . '/views/html-settings-page.php';
    }
}
