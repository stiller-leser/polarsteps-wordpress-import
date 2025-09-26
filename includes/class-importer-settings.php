<?php
class Polarsteps_Importer_Settings {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'settings_init']);
        add_filter('pre_update_option_polarsteps_importer_settings', [$this, 'save_settings'], 10, 1);
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

    public function settings_init() {
        register_setting('polarsteps_importer', 'polarsteps_importer_settings');

        add_settings_section(
            'polarsteps_importer_section',
            __('Polarsteps Settings', 'polarsteps-importer'),
            [$this, 'settings_section_callback'],
            'polarsteps-importer'
        );

        add_settings_field(
            'polarsteps_trip_id',
            __('Trip ID', 'polarsteps-importer'),
            [$this, 'trip_id_render'],
            'polarsteps-importer',
            'polarsteps_importer_section'
        );

        add_settings_field(
            'polarsteps_remember_token',
            __('Remember Token', 'polarsteps-importer'),
            [$this, 'remember_token_render'],
            'polarsteps-importer',
            'polarsteps_importer_section'
        );

        add_settings_field(
            'polarsteps_update_interval',
            __('Update Interval (in hours)', 'polarsteps-importer'),
            [$this, 'update_interval_render'],
            'polarsteps-importer',
            'polarsteps_importer_section'
        );

        add_settings_field(
            'polarsteps_steps_per_run',
            __('Max Steps per Run', 'polarsteps-importer'),
            [$this, 'steps_per_run_render'],
            'polarsteps-importer',
            'polarsteps_importer_section'
        );

        add_settings_field(
            'polarsteps_ignored_step_ids',
            __('Ignore Step IDs', 'polarsteps-importer'),
            [$this, 'ignored_step_ids_render'],
            'polarsteps-importer',
            'polarsteps_importer_section'
        );

        add_settings_field(
            'polarsteps_ignore_no_title',
            __('Ignore steps without title', 'polarsteps-importer'),
            [$this, 'ignore_no_title_render'],
            'polarsteps-importer',
            'polarsteps_importer_section'
        );

        add_settings_field(
            'polarsteps_disable_image_import',
            __('Disable Image Import', 'polarsteps-importer'),
            [$this, 'disable_image_import_render'],
            'polarsteps-importer',
            'polarsteps_importer_section'
        );

        add_settings_field(
            'polarsteps_image_import_mode',
            __('Image Import Mode', 'polarsteps-importer'),
            [$this, 'image_import_mode_render'],
            'polarsteps-importer',
            'polarsteps_importer_section'
        );

        add_settings_field(
            'polarsteps_post_type',
            __('Post Type', 'polarsteps-importer'),
            [$this, 'post_type_render'], // Neues Feld für Post-Type-Auswahl
            'polarsteps-importer',
            'polarsteps_importer_section'
        );

        add_settings_field(
            'polarsteps_post_status',
            __('Post Status', 'polarsteps-importer'),
            [$this, 'post_status_render'],
            'polarsteps-importer',
            'polarsteps_importer_section'
        );

        add_settings_field(
            'polarsteps_post_category',
            __('Category', 'polarsteps-importer'),
            [$this, 'post_category_render'], // Neues Feld für Kategorie-Auswahl
            'polarsteps-importer',
            'polarsteps_importer_section'
        );

        add_settings_field(
            'polarsteps_use_location_detail_as_category',
            __('Use location detail as category', 'polarsteps-importer'),
            [$this, 'use_location_detail_as_category_render'],
            'polarsteps-importer',
            'polarsteps_importer_section'
        );

        add_settings_field(
            'polarsteps_leaflet_map',
            __('Add Leaflet Map', 'polarsteps-importer'),
            [$this, 'leaflet_map_render'],
            'polarsteps-importer',
            'polarsteps_importer_section'
        );

        add_settings_field(
            'polarsteps_debug_mode',
            __('Enable Debug Mode', 'polarsteps-importer'),
            [$this, 'debug_mode_render'],
            'polarsteps-importer',
            'polarsteps_importer_section'
        );
    }

    // Logs in der Datenbank speichern
    public static function log_message($message) {
        $logs = get_option('polarsteps_importer_logs', []);
        $logs[] = '[Polarsteps Importer] ' . current_time('mysql') . ': ' . $message;
        if (count($logs) > 100) {
            array_shift($logs);
        }
        update_option('polarsteps_importer_logs', $logs);
    }

    // Kombinierte Seite für Einstellungen und Logs
    public function options_page_with_logs() {
        // Speichere Einstellungen, falls Formular gesendet wurde
        // Verarbeite das Löschen der Logs, wenn der entsprechende Button geklickt wurde.
        if (isset($_POST['clear_logs']) && check_admin_referer('polarsteps_importer_clear_logs_nonce', 'polarsteps_importer_clear_logs_nonce')) {
            delete_option('polarsteps_importer_logs');
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Logs have been cleared.', 'polarsteps-importer') . '</p></div>';
        }

        // Benachrichtigung für manuellen Import-Start
        if (isset($_GET['manual_run_triggered']) && $_GET['manual_run_triggered'] === '1') {
            echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__('The import has been started in the background. The logs will be updated shortly.', 'polarsteps-importer') . '</p></div>';
        }

        // Benachrichtigung für erfolgreichen Abschluss des Imports
        if (get_transient('polarsteps_import_completed')) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('The manual import has been successfully completed.', 'polarsteps-importer') . '</p></div>';
            delete_transient('polarsteps_import_completed'); // Transient löschen, damit es nicht erneut angezeigt wird.
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Polarsteps Importer', 'polarsteps-importer'); ?></h1>

            <!-- Formular für Einstellungen -->
            <form method="post" action="options.php">
                <?php
                settings_fields('polarsteps_importer');
                do_settings_sections('polarsteps-importer');
                submit_button(__('Save Settings', 'polarsteps-importer'), 'primary', 'submit', true, ['style' => 'margin-top: 20px;']);
                ?>
            </form>

            <hr style="margin-top: 20px;">

            <!-- Manueller Import -->
            <h3><?php esc_html_e('Manual Import', 'polarsteps-importer'); ?></h3>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="polarsteps_importer_run_now">
                <?php wp_nonce_field('polarsteps_importer_run_now'); ?>
                <?php submit_button(__('Import Now', 'polarsteps-importer'), 'secondary', 'submit', false); ?>
            </form>

            <hr>
            <h3><?php esc_html_e('Debug Logs', 'polarsteps-importer'); ?></h3>
            <?php $this->display_filtered_logs(); ?>
        </div>
        <?php
    }

    // Gefilterte Logs anzeigen (aus der Datenbank)
    private function display_filtered_logs() {
        $logs = get_option('polarsteps_importer_logs', []);

        if (empty($logs)) {
            echo '<p>' . esc_html__('No logs found. Run an import to generate logs.', 'polarsteps-importer') . '</p>';
            return;
        }

        echo '<div style="max-height: 400px; overflow-y: auto; background: #f9f9f9; padding: 10px; border: 1px solid #ddd; font-family: monospace;">';
        echo '<pre style="margin: 0;">';
        echo esc_html(implode("\n", array_reverse($logs))); // Neueste Logs zuerst
        echo '</pre>';

        echo '</div>';
        // Lösch-Button für Logs
        echo '<p class="submit" style="margin-top: 10px; padding: 0;">';
        // Formular für Log-Aktionen
        echo '<form method="post" style="display: inline-block; margin-right: 10px;">';
        wp_nonce_field('polarsteps_importer_clear_logs_nonce', 'polarsteps_importer_clear_logs_nonce');
        submit_button(__('Clear Logs', 'polarsteps-importer'), 'delete', 'clear_logs', false);
        echo '</form>';
        printf('<a href="%s" class="button">%s</a>', esc_url(admin_url('options-general.php?page=polarsteps-importer')), esc_html__('Refresh Logs', 'polarsteps-importer'));
        echo '</p>';
    }

    // Render-Methoden
    public function trip_id_render() {
        $options = get_option('polarsteps_importer_settings');
        echo '<input type="text" name="polarsteps_importer_settings[polarsteps_trip_id]" value="' . esc_attr($options['polarsteps_trip_id'] ?? '') . '">';
    }

    public function remember_token_render() {
        $options = get_option('polarsteps_importer_settings');
        $decrypted_token = Polarsteps_Importer_Security::decrypt($options['polarsteps_remember_token'] ?? '');
        $placeholder = __('No token found', 'polarsteps-importer');
        if (!empty($decrypted_token)) {
            $placeholder = substr($decrypted_token, 0, 3) . str_repeat('*', strlen($decrypted_token) - 3);
        }
        echo '<input type="text" name="polarsteps_importer_remember_token" value="" placeholder="' . esc_attr($placeholder) . '">';
        if (!empty($decrypted_token)) {
            echo '<p class="description">' . esc_html__('A token is already saved. To change it, enter a new one.', 'polarsteps-importer') . '</p>';
        }
        echo '<p class="description">' . esc_html__('Your Remember Token will be stored encrypted and will not be displayed again.', 'polarsteps-importer') . '</p>';
    }

    public function disable_image_import_render() {
        $options = get_option('polarsteps_importer_settings');
        echo '<input type="checkbox" id="polarsteps_disable_image_import" name="polarsteps_importer_settings[polarsteps_disable_image_import]" ' .
             checked($options['polarsteps_disable_image_import'] ?? false, true, false) . ' value="1">';
        echo '<p class="description">' . esc_html__('Check this to prevent any images from being imported.', 'polarsteps-importer') . '</p>';
    }

    public function ignore_no_title_render() {
        $options = get_option('polarsteps_importer_settings');
        echo '<input type="checkbox" name="polarsteps_importer_settings[polarsteps_ignore_no_title]" ' .
             checked($options['polarsteps_ignore_no_title'] ?? false, true, false) . ' value="1">';
        echo '<p class="description">' . esc_html__('Enable to skip steps without a title.', 'polarsteps-importer') . '</p>';
    }

    public function ignored_step_ids_render() {
        $options = get_option('polarsteps_importer_settings');
        echo '<input type="text" name="polarsteps_importer_settings[polarsteps_ignored_step_ids]" ' .
             'value="' . esc_attr($options['polarsteps_ignored_step_ids'] ?? '') . '" ' .
             'placeholder="' . esc_attr__('e.g. 12345,67890,11111', 'polarsteps-importer') . '" style="width: 300px;">';
        echo '<p class="description">' . esc_html__('Step IDs to ignore (comma-separated).', 'polarsteps-importer') . '</p>';
    }

    public function use_location_detail_as_category_render() {
        $options = get_option('polarsteps_importer_settings');
        echo '<input type="checkbox" id="polarsteps_use_location_detail_as_category" name="polarsteps_importer_settings[polarsteps_use_location_detail_as_category]" ' .
             checked($options['polarsteps_use_location_detail_as_category'] ?? false, true, false) . ' value="1">';
        echo '<p class="description">' . esc_html__('If checked, the "detail" field from the location data will be used to set the post category in addition to the manual selection below.', 'polarsteps-importer') . '</p>';
    }

    public function update_interval_render() {
        $options = get_option('polarsteps_importer_settings');
        echo '<input type="number" name="polarsteps_importer_settings[polarsteps_update_interval]" min="1" value="' . esc_attr($options['polarsteps_update_interval'] ?? 1) . '">';
    }

    public function steps_per_run_render() {
        $options = get_option('polarsteps_importer_settings');
        echo '<input type="number" name="polarsteps_importer_settings[polarsteps_steps_per_run]" min="1" value="' . esc_attr($options['polarsteps_steps_per_run'] ?? 10) . '">';
        echo '<p class="description">' . esc_html__('Limits the number of new steps to import in a single run. Helps prevent timeouts on servers with many steps.', 'polarsteps-importer') . '</p>';
    }

    public function image_import_mode_render() {
        $options = get_option('polarsteps_importer_settings');
        $mode = $options['polarsteps_image_import_mode'] ?? 'gallery';
        $disable_image_import = $options['polarsteps_disable_image_import'] ?? false;
        $style = $disable_image_import ? 'style="display:none;"' : '';
        ?>
        <div id="polarsteps_image_import_mode_wrapper" <?php echo $style; ?>>
            <select name="polarsteps_importer_settings[polarsteps_image_import_mode]">
                <option value="gallery" <?php selected($mode, 'gallery'); ?>><?php esc_html_e('Append as gallery shortcode', 'polarsteps-importer'); ?></option>
                <option value="embed" <?php selected($mode, 'embed'); ?>><?php esc_html_e('Embed individually in content', 'polarsteps-importer'); ?></option>
            </select>
            <p class="description"><?php esc_html_e('Choose how images are added to the post.', 'polarsteps-importer'); ?></p>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $('#polarsteps_disable_image_import').on('change', function() {
                    if ($(this).is(':checked')) {
                        // Find the closest tr parent and hide it
                        $('select[name="polarsteps_importer_settings[polarsteps_image_import_mode]"]').closest('tr').hide();
                    } else {
                        // Find the closest tr parent and show it
                        $('select[name="polarsteps_importer_settings[polarsteps_image_import_mode]"]').closest('tr').show();
                    }
                }).trigger('change'); // Trigger on load
            });
        </script>
        <?php
    }

    public function post_status_render() {
        $options = get_option('polarsteps_importer_settings');
        $status = $options['polarsteps_post_status'] ?? 'draft';
        echo '<select name="polarsteps_importer_settings[polarsteps_post_status]">';
        echo '<option value="draft" ' . selected($status, 'draft', false) . '>' . esc_html__('Draft', 'polarsteps-importer') . '</option>';
        echo '<option value="publish" ' . selected($status, 'publish', false) . '>' . esc_html__('Published', 'polarsteps-importer') . '</option>';
        echo '</select>';
    }

    public function post_type_render() {
        $options = get_option('polarsteps_importer_settings');
        $post_type = $options['polarsteps_post_type'] ?? 'post';

        // Hole alle registrierten Post-Types
        $post_types = get_post_types(['public' => true], 'objects');
        ?>
        <select name="polarsteps_importer_settings[polarsteps_post_type]">
            <?php foreach ($post_types as $type => $obj): ?>
                <option value="<?php echo esc_attr($type); ?>" <?php selected($post_type, $type); ?>>
                    <?php echo esc_html($obj->labels->singular_name); ?> (<?php echo esc_html($type); ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php esc_html_e('Select the post type into which the Polarsteps steps should be imported.', 'polarsteps-importer'); ?><br>
            <?php esc_html_e('By default, "post" is used.', 'polarsteps-importer'); ?>
        </p>
        <?php
    }

    public function post_category_render() {
        $options = get_option('polarsteps_importer_settings');
        $post_type = $options['polarsteps_post_type'] ?? 'post';
        $category_id = $options['polarsteps_post_category'] ?? 0;

        $taxonomies = get_object_taxonomies($post_type, 'objects');
        $category_taxonomies = [];

        // Suche nach Kategorien-Taxonomien (z. B. "category" oder benutzerdefiniert)
        foreach ($taxonomies as $taxonomy) {
            if ($taxonomy->hierarchical) {
                $category_taxonomies[$taxonomy->name] = $taxonomy->labels->singular_name;
            }
        }

        if (empty($category_taxonomies)) {
            echo '<p>' . esc_html__('The selected post type has no categories.', 'polarsteps-importer') . '</p>';
            echo '<input type="hidden" name="polarsteps_importer_settings[polarsteps_post_category]" value="0" id="polarsteps_post_category">';
            return;
        }

        // Standardmäßig die erste Taxonomie verwenden
        $taxonomy_name = key($category_taxonomies);
        $terms = get_terms([
            'taxonomy' => $taxonomy_name,
            'hide_empty' => false,
        ]);

        ?>
        <div id="polarsteps_post_category_wrapper">
            <select name="polarsteps_importer_settings[polarsteps_post_category]" id="polarsteps_post_category">
                <option value="0">-- <?php esc_html_e('No Category', 'polarsteps-importer'); ?> --</option>
                <?php foreach ($terms as $term): ?>
                    <option value="<?php echo esc_attr($term->term_id); ?>" <?php selected($category_id, $term->term_id); ?>>
                        <?php echo esc_html($term->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="description">
                <?php esc_html_e('Select a category for the imported posts.', 'polarsteps-importer'); ?>
            </p>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Note: The following script for dynamically updating categories based on post type
            // seems to have some issues (e.g., missing data-taxonomies attribute).
            // This part might need a review in the future.
            $('#polarsteps_post_type').change(function() {
                var postType = $(this).val();
                var taxonomies = JSON.parse($(this).find(':selected').attr('data-taxonomies'));
                var categorySelect = $('#polarsteps_post_category');
                var hasCategories = false;

                // Leere das Kategorie-Dropdown
                categorySelect.empty();
                categorySelect.append('<option value="0">-- <?php esc_html_e('No Category', 'polarsteps-importer'); ?> --</option>');

                // Suche nach hierarchischen Taxonomien (Kategorien)
                for (var taxName in taxonomies) {
                    if (taxonomies[taxName].hierarchical) {
                        hasCategories = true;
                        $.ajax({
                            url: ajaxurl,
                            data: {
                                action: 'polarsteps_get_terms',
                                taxonomy: taxName,
                                post_type: postType
                            },
                            success: function(response) {
                                if (response.success && response.data.terms) {
                                    $.each(response.data.terms, function(index, term) {
                                        categorySelect.append(
                                            $('<option></option>').val(term.term_id).text(term.name)
                                        );
                                    });
                                }
                            }
                        });
                        break; // Nur die erste hierarchische Taxonomie verwenden
                    }
                }

                if (!hasCategories) {
                    categorySelect.append('<option value="0"><?php esc_html_e('The selected post type has no categories', 'polarsteps-importer'); ?></option>');
                }
            });
        });
        </script>
        <?php
    }

    public function leaflet_map_render() {
        $options = get_option('polarsteps_importer_settings');
        $is_leaflet_active = is_plugin_active('leaflet-map/leaflet-map.php');

        echo '<input type="checkbox" name="polarsteps_importer_settings[polarsteps_leaflet_map]" ' .
             checked($options['polarsteps_leaflet_map'] ?? false, true, false) . ' value="1" ' .
             disabled(!$is_leaflet_active, true, false) . '>';

        if ($is_leaflet_active) {
            echo '<p class="description">' . esc_html__('Adds a Leaflet map with the step\'s location to the end of the post.', 'polarsteps-importer') . '</p>';
        } else {
            $install_url = admin_url('plugin-install.php?tab=search&s=leaflet-map');
            echo '<p class="description">' . sprintf(
                wp_kses(__('The <a href="%s" target="_blank">Leaflet Map</a> plugin must be installed and activated to use this feature.', 'polarsteps-importer'), ['a' => ['href' => [], 'target' => []]]),
                esc_url($install_url)
            ) . '</p>';
        }
    }

    public function debug_mode_render() {
        $options = get_option('polarsteps_importer_settings');
        echo '<input type="checkbox" name="polarsteps_importer_settings[polarsteps_debug_mode]" ' . checked($options['polarsteps_debug_mode'] ?? false, true, false) . ' value="1">';
        echo '<p class="description">' . esc_html__('Enables debug mode. No posts will be created, but the API response will be logged.', 'polarsteps-importer') . '</p>';
    }

    public function settings_section_callback() {
        echo '<p>' . esc_html__('Enter your Polarsteps Trip ID and Remember Token to import steps as WordPress posts.', 'polarsteps-importer') . '</p>';
    }

    public function save_settings($settings) {
        $current_options = get_option('polarsteps_importer_settings');

        if (isset($_POST['polarsteps_importer_remember_token']) && !empty($_POST['polarsteps_importer_remember_token'])) {
            $settings['polarsteps_remember_token'] = Polarsteps_Importer_Security::encrypt($_POST['polarsteps_importer_remember_token']);
        } else {
            $settings['polarsteps_remember_token'] = $current_options['polarsteps_remember_token'] ?? '';
        }

        $current_interval = $current_options['polarsteps_update_interval'] ?? 1;
        $new_interval = $settings['polarsteps_update_interval'] ?? 1;
        $next_scheduled = wp_next_scheduled(Polarsteps_Importer_Cron::HOOK);

        // Fall 1: Noch kein Cron-Job geplant, aber alle Daten sind jetzt da.
        if (!$next_scheduled && !empty($settings['polarsteps_trip_id']) && !empty($settings['polarsteps_remember_token'])) {
            Polarsteps_Importer_Cron::schedule_recurring_event();
            self::log_message(__('Trip ID and Token set. Recurring cron job scheduled.', 'polarsteps-importer'));
        } elseif ($new_interval != $current_interval && $next_scheduled) {
            // Fall 2: Das Intervall hat sich geändert, plane den Job neu.
            wp_clear_scheduled_hook(Polarsteps_Importer_Cron::HOOK);
            Polarsteps_Importer_Cron::schedule_recurring_event();
            self::log_message(__('Update interval changed. Cron job has been rescheduled.', 'polarsteps-importer'));
        }

        return $settings;
    }
}
